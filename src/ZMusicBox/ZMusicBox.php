<?php

namespace ZMusicBox;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\level;
use pocketmine\Server;
use pocketmine\scheduler\TaskScheduler;
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
use ZMusicBox\Task\MusicPlayer;
use ZMusicBox\NoteBoxAPI;

class ZMusicBox extends PluginBase implements Listener {

    public $song;
    public $musicPlayer;
    public $name;

    public function onEnable() {
        // $this->getLogger()->info("ZMusicBox is loading!");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if (!is_dir($this->getPluginDir())) {
            @mkdir($this->getServer()->getDataPath() . "plugins/songs");
        }
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if (!$this->checkMusic()) {
            $this->getLogger()->info(TextFormat::BLUE . "Please put in nbs files");
        } else {
            $this->startTask();
        }
        // $this->getLogger()->info("ZMusicBox loaded");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
        switch ($command->getName()) {
            case "music":
                if (isset($args[0])) {
                    switch ($args[0]) {
                        case "next":
                        case "skip":
                            $this->startTask();
                            $sender->sendMessage(TextFormat::GREEN."Switched to next song");
                            return true;
                        case "stop":
                        case "pause":
                            if ($sender->isOp()) {
                                $this->getScheduler()->cancelAllTasks($this);
                                $sender->sendMessage(TextFormat::GREEN."Song Stopped");
                            } else {
                                $sender->sendMessage(TextFormat::RED."No Permission");
                            }
                            return true;
                        case "start":
                        case "begin":
                        case "resume":
                            if ($sender->isOp()) {
                                $this->startTask();
                                $sender->sendMessage(TextFormat::GREEN."Song Started");
                            } else {
                                $sender->sendMessage(TextFormat::RED."No Permission");
                            }
                            return true;
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Usage:/music <start|stop|next>");
                    return false;
                }
            break;
        }
        return false;
    }

    public function checkMusic() {
        if ($this->getDirCount($this->getPluginDir()) > 0 && $this->randomFile($this->getPluginDir(), "nbs")) {
            return true;
        }
        return false;
    }

    public function getDirCount($path) {
              $num = sizeof(scandir($path));
              $num = ($num>2)?$num-2:0;
        return $num;
    }

    public function getPluginDir() {
        return $this->getServer()->getDataPath() . "plugins/songs/";
    }

    public function getRandomMusic() {
        $dir = $this->randomFile($this->getPluginDir(), "nbs");
        if ($dir) {
            $api = new NoteBoxAPI($this, $dir);
            return $api;
        }
        return false;
    }

    public function randomFile($folder = "", $extensions = ".*") {
        $folder = trim($folder);
        $folder = ($folder == '') ? './' : $folder;
        if (!is_dir($folder)) {
            return false;
        }
        $files = [];
        if ($dir = @opendir($folder)) {
            while ($file = readdir($dir)) {
                if (!preg_match('/^\.+$/', $file) && preg_match('/\.(' . $extensions . ')$/', $file)) {
                    $files[] = $file;        
                }
            }
            closedir($dir);  
        } else {
            return false;
        }
        if (count($files) == 0) {
            return false;
        }
        mt_srand((double) microtime() * 1000000);
        $rand = mt_rand(0, count($files) - 1);
        if (!isset($files[$rand])) {
            return false;
        }
        if (function_exists("iconv")) {
            $rname = iconv('gbk','UTF-8', $files[$rand]);
        } else {
            $rname = $files[$rand];
        }
        $this->name = str_replace('.nbs', "", $rname);
        return $folder . $files[$rand];
    }

    public function getNearbyNoteBlock($x,$y,$z,$world) {
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
                    if ($block->getID() == 25) {
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
                        $pk->volume = $type;
                        $pk->pitch = $sound;
                        $pk->unknownBool = true;
                        $pk->unknownBool2 = true;
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
        $this->getScheduler()->cancelAllTasks($this);
        $this->musicPlayer = new MusicPlayer($this);
        $this->getScheduler()->scheduleRepeatingTask($this->musicPlayer, 2990 / $this->song->speed);
    }

}
