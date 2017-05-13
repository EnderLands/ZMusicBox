# ZMusicBox
Just play music in your server~


_Requirements:_

The server software you use must play the noteblock sound when you tap it


_How to use:_

1) You have to add .nbs files in the songs folder inside your plugins folder
2) Place a noteblock


_Commands:_

/music <start|stop|next>


ZMusicBox is also accessible from its API:

$this->ZMusicBox->StartNewTask();   -   Switch to the Next Song

$this->getServer()->ZMusicBox->getScheduler()->cancelTasks($this);   -   Stop the Music


_Tips:_

You should use Minecraft Note Block Studio to convert midi files into nbs files.
Website: http://www.stuffbydavid.com/mcnbs


Please do not use the internal code and algorithms to other plugins
