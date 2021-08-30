<?php

namespace ZMusicBox\task;

use pocketmine\scheduler\Task;
use ZMusicBox\NoteBoxAPI;
use ZMusicBox\ZMusicBox;

class MusicPlayer extends Task {

    private $plugin;
    private $api;

    public function __construct(ZMusicBox $plugin, NoteBoxAPI $api) {
        $this->plugin = $plugin;
        $this->api = $api;
    }

    public function onRun(int $currentTick) {
        if (isset($this->api->sounds[$this->api->tick])) {
            $i = 0;
            foreach ($this->api->sounds[$this->api->tick] as $data) {
                $this->plugin->play($this->api, $data[0], $data[1], $i);
                $i++;
            }
        }
        $this->api->tick++;
        if ($this->api->getTick() > $this->api->length) {
            $this->plugin->startTask();
        }
    }

}
