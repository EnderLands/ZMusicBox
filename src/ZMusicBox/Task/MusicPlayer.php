<?php

namespace ZMusicBox\task;

use pocketmine\scheduler\Task;
use ZMusicBox\ZMusicBox;

class MusicPlayer extends Task {

    public function __construct(ZMusicBox $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick) {
        if (isset($this->plugin->song->sounds[$this->plugin->song->tick])) {
            $i = 0;
            foreach ($this->plugin->song->sounds[$this->plugin->song->tick] as $data) {
                $this->plugin->Play($data[0], $data[1], $i);
                $i++;
            }
        }
        $this->plugin->song->tick++;
        if ($this->plugin->song->tick > $this->plugin->song->length) {
            $this->plugin->StartNewTask();
        }
    }

}
