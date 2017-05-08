# ZMusicBox
Just play music in your server~

Requirements:

The server software you use must play the noteblock sound when you tap it


How to use:

1) You have to add .nbs files in the songs folder inside your plugins folder
2) Place a noteblock


Commands:

/music <start|stop|next>


ZMusicBox is also accessible from it's API:

$this->ZMusicBox->StartNewTask();   -   Switch to the Next Song

$this->getServer()->ZMusicBox->getScheduler()->cancelTasks($this);   -   Stop the Music


Tips:
You should use Minecraft Note Block Studio to change midi files into nbs files.
Website: http://www.stuffbydavid.com/mcnbs


Please do not use the internal code and algorithms to other plugins
