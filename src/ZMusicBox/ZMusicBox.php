<?php

namespace ZMusicBox;

use pocketmine\block\BlockLegacyIds;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\permission\PermissionManager;
use pocketmine\permission\Permission;
use pocketmine\utils\TextFormat;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\player\Player;
use pocketmine\math\Vector3;
use ZMusicBox\command\MusicCommand;
use ZMusicBox\task\MusicPlayer;
use ZMusicBox\NoteBoxAPI;

class ZMusicBox extends PluginBase implements Listener {

    public $name;
    public $taskId = 0;
    private static $instance = null;
    public static function getInstance() : ZMusicBox {
        return self::$instance;
    }

    public function onEnable() {
        self::$instance = $this;
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder() . "/songs");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if (!$this->checkMusic()) {
            $this->getLogger()->info(TextFormat::BLUE . "Please put in .nbs files");
        }
        $this->registerPermissions();
        $this->getServer()->getCommandMap()->register("music", new MusicCommand($this));
    }

    public function registerPermissions() {
        $permissionManager = PermissionManager::getInstance();
        $permissionManager->addPermission(new Permission("ZMusicBox.music", "ZMusicBox Commands", Permission::DEFAULT_TRUE));
        $permissionManager->addPermission(new Permission("ZMusicBox.stop", "Stops music", Permission::DEFAULT_OP));
        $permissionManager->addPermission(new Permission("ZMusicBox.start", "Starts music", Permission::DEFAULT_OP));
    }

    public function checkMusic() {
        if ($this->getDirCount($this->getPluginDir()) > 0 && $this->getRandomFile($this->getPluginDir(), "nbs")) {
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
        $file = $this->getRandomFile($this->getDataFolder() . "/songs/", ".nbs");
        if ($file) {
            $api = new NoteBoxAPI($this, $file);
            return $api;
        }
        return null;
    }

    public function getRandomFile($folder, $extension) {
        $files = glob($folder . "/*" . $extension);
        $index = array_rand($files);
        $this->name = explode(".nbs", $files[$index])[0];
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
                    if ($block->getId() === BlockLegacyIds::NOTEBLOCK) {
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
  
    public function play($song, $sound, $type = 0, $blo = 0) {
        if (is_numeric($sound) && $sound > 0) {
            foreach ($this->getServer()->getOnlinePlayers() as $player) {
                $noteblock = $this->getNearbyNoteBlock($player->x, $player->y, $player->z, $player->getLevel());
                $noteblock1 = $noteblock;
                if (!empty($noteblock)) {
                    if ($song->name != "") {
                        $player->sendPopup(TextFormat::BLUE . "|->" . TextFormat::GOLD . "Now Playing: " . TextFormat::GREEN . $song->name . TextFormat::BLUE . "<-|");
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
                        $pk->position = new Vector3($block->x, $block->y, $block->z);
                        $pk->extraData = $type;
                        $player->dataPacket($pk);
                    }
                }
            }
        }
    }

    public function startTask() {
        if (!$this->checkMusic()) {
            return;
        }
        if ($this->taskId !== 0) {
            $this->getScheduler()->cancelTask($this->taskId);
        }
        $song = $this->getRandomMusic();
        if ($song !== null) {
            $task = new MusicPlayer($this, $song);
            $this->taskId = $task->getTaskId();
            $this->getScheduler()->scheduleRepeatingTask($task, 2990 / $song->speed);
        } else {
            $this->getLogger()->error("Failed to play current song. Skipping to next song.");
            $this->startTask();
        }
    }

    public function hasSong(string $name) {
        foreach (glob($this->getDataFolder() . "/songs/*.nbs") as $file) {
            if (strtolower(explode(".nbs", basename($file, ".nbs"))[0]) === strtolower($name)) {
                return true;
            }
        }
        return false;
    }

    public function selectSong(string $name) {
        if (!$this->checkMusic()) {
            return;
        }
        foreach (glob($this->getDataFolder() . "/songs/*.nbs") as $file) {
            if (strtolower(explode(".nbs", basename($file, ".nbs"))[0]) === strtolower($name)) {
                $this->getScheduler()->cancelTask($this->taskId);
                $song = new NoteBoxAPI($this, $file);
                $task = new MusicPlayer($this, $song);
                $this->taskId = $task->getTaskId();
                $this->getScheduler()->scheduleRepeatingTask($task, 2990 / $song);
            }
        }
    }

}
