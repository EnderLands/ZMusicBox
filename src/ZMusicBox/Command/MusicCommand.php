<?php

namespace ZMusicBox\Command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use ZMusicBox\ZMusicBox;

class MusicCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(ZMusicBox $plugin) {
        $this->plugin = $plugin;
        parent::__construct(
            "music",
            "Manage the music in your server",
            "/music <start|stop|next>"
        );
        $this->setPermission("ZMusicBox.music");
    }

    public function getPlugin() : Plugin {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args) {
        if (!$this->testPermission($sender)) {
            return;
        }

        if (isset($args[0])) {
            switch ($args[0]) {
                case "next":
                case "skip":
                    if ($sender->hasPermission("ZMusicBox.skip")) {
                        $this->plugin->startTask();
                        $sender->sendMessage(TextFormat::GREEN . "Switched to next song");
                    } else {
                        $sender->sendMessage(TextFormat::RED . "No Permission");
                    }
                    break;
                case "stop":
                case "pause":
                    if ($sender->hasPermission("ZMusicBox.stop")) {
                        $this->plugin->getScheduler()->cancelAllTasks($this);
                        $sender->sendMessage(TextFormat::GREEN . "Song Stopped");
                    } else {
                        $sender->sendMessage(TextFormat::RED . "No Permission");
                    }
                    break;
                case "start":
                case "begin":
                case "resume":
                    if ($sender->hasPermission("ZMusicBox.start")) {
                        $this->plugin->startTask();
                        $sender->sendMessage(TextFormat::GREEN . "Song Started");
                    } else {
                        $sender->sendMessage(TextFormat::RED . "No Permission");
                    }
                    break;
            }
        }
    }

}
