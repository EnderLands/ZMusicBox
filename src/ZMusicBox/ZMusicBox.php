<?php

namespace ZMusicBox;

use pocketmine\block\BlockIds;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\level;
use pocketmine\Server;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\permission\Permission;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\math\Math;
use pocketmine\level\format\Chunk;
use pocketmine\level\format\FullChunk;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Binary;
use ZMusicBox\command\MusicCommand;
use ZMusicBox\task\MusicPlayer;
use ZMusicBox\NoteBoxAPI;

class ZMusicBox extends PluginBase implements Listener {

    public $song;
    public $name;
    // public $musicPlayer;

    public function onEnable() {
        // $this->getLogger()->info("ZMusicBox is loading!");
        // $this->getLogger()->info("ZMusicBox loaded");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getPluginManager()->addPermission(new Permission("ZMusicBox.music", "ZMusicBox Commands", Permission::DEFAULT_TRUE));
        $this->getServer()->getPluginManager()->addPermission(new Permission("ZMusicBox.skip", "Skips music", Permission::DEFAULT_TRUE));
        $this->getServer()->getPluginManager()->addPermission(new Permission("ZMusicBox.stop", "Stops music", Permission::DEFAULT_OP));
        $this->getServer()->getPluginManager()->addPermission(new Permission("ZMusicBox.start", "Starts music", Permission::DEFAULT_OP));
        $this->getServer()->getCommandMap()->register("music", new MusicCommand($this));
        @mkdir($this->getDataFolder() . "/songs");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if (!$this->checkMusic()) {
            $this->getLogger()->info(TextFormat::BLUE . "Please put in nbs files");
        } else {
            $this->startTask();
        }
    }

    public function checkMusic() {
        if ($this->getDirCount($this->getPluginDir()) > 0 && $this->randomFile($this->getPluginDir(), "nbs")) {
            return true;
        }
        return false;
    }

    public function getDirCount($path) {
        $num = sizeof(scandir($path));
        $num = ($num > 2) ? $num - 2 : 0;
        return $num;
    }

    public function getPluginDir() {
        return $this->getDataFolder() . "/songs/";
    }

    public function getRandomMusic() {
        $dir = $this->randomFile($this->getDataFolder() . "/songs/", ".nbs");
        if ($dir) {
            $api = new NoteBoxAPI($this, $dir);
            return $api;
        }
        return null;
    }

    public function randomFile($folder, $extension) {
        $files = glob($folder . "/*" . $extension);
        $index = array_rand($files);
        return $files[$index];
    }

    public function getNearbyNoteBlock($x, $y, $z, $world) {
        $nearby = [];
        $minX = $x - 5;
        $maxX = $x + 5;    
        $minY = $y - 5;
        $maxY = $y + 5;
        $minZ = $z - 2;
        $maxZ = $z + 2;
        for ($x = $minX; $x <= $maxX; ++$x) {
            for ($y = $minY; $y <= $maxY; ++$y) {
                for ($z = $minZ; $z <= $maxZ; ++$z) {
                    $vector = new Vector3($x, $y, $z);
                    $block = $world->getBlock($vector);
                    if ($block->getId() == BlockIds::NOTEBLOCK) {
                        $nearby[] = $block;
                    }
                }
            }
        }
        return $nearby;
    }

    public function getFullBlock($x, $y, $z, $level) {
        return $level->getChunk($x >> 4, $z >> 4, false)->getFullBlock($x & 0x0f, $y & 0x7f, $z & 0x0f);
    }
  
    public function play($sound, $type = 0, $blo = 0) {
        if (is_numeric($sound) && $sound > 0) {
            foreach ($this->getServer()->getOnlinePlayers() as $player) {
                $noteblock = $this->getNearbyNoteBlock($player->x, $player->y, $player->z, $player->getLevel());
                $noteblock1 = $noteblock;
                if (!empty($noteblock)) {
                    if ($this->song->name != "") {
                        $player->sendPopup(TextFormat::BLUE . "|->" . TextFormat::GOLD . "Now Playing: " . TextFormat::GREEN . $this->song->name . TextFormat::BLUE . "<-|");
                    } else {    
                        $player->sendPopup(TextFormat::BLUE . "|->" . TextFormat::GOLD . "Now Playing: " . TextFormat::GREEN . $this->name . TextFormat::BLUE . "<-|");
                    }
                    $i = 0;
                    while ($i < $blo) {
                        if (current($noteblock)) {
                            next($noteblock);
                            $i ++;
                        } else {
                            $noteblock = $noteblock1;
                            $i ++;
                        }
                    }
                    $block = current($noteblock);
                    if ($block) {
                        $pk = new BlockEventPacket();
                        $pk->x = $block->x;
                        $pk->y = $block->y;
                        $pk->z = $block->z;
                        $pk->eventType = $type;
                        $pk->eventData = $sound;
                        $player->dataPacket($pk);
                        $pk = new LevelSoundEventPacket();
                        $pk->sound = LevelSoundEventPacket::SOUND_NOTE;
                        // $pk->x = $block->x;
                        // $pk->y = $block->y;
                        // $pk->z = $block->z;
                        $pk->position = new Vector3($block->x, $block->y, $block->z);
                        $pk->extraData = $type;
                        $player->dataPacket($pk);
                    }
                }
            }
        }
    }

    public function onDisable() {
        // $this->getLogger()->info("ZMusicBox Unload Success");
    }

    public function startTask() {
        $this->song = $this->getRandomMusic();
        $this->getScheduler()->cancelAllTasks();
        // $this->musicPlayer = new MusicPlayer($this);
        if ($this->song !== null) {
            $this->getScheduler()->scheduleRepeatingTask(new MusicPlayer($this), 2990 / $this->song->speed);
        } else {
            $this->getLogger()->error("Failed to play current song. Skipping to next song.");
            $this->startTask();
        }
    }

}
