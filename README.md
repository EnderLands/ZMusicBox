# ZMusicBox
Play music in your PocketMine-MP server using noteblocks!


## Requirements
 - The server software that you use must have working Noteblock functionality
 - Noteblocks must be placed in the server
 - Songs must be in .nbs format in order to be played

## Usage
1) Place the `.phar` file in your plugins folder of the server
2) Run the server
3) Stop the server
4) Place .nbs files in the /plugins/songs directory of the server
5) Run the server
6) Place a noteblock

## Commands

 - `/music <start|stop|next>`

## API
ZMusicBox is also accessible from its API:
 - Switch to the Next Song
```php
$this->getServer()->getPluginBase()->getPlugin("ZMusicBox")->StartNewTask();
```
 - Stop the music
```php
$this->getServer()->getPluginBase()->getPlugin("ZMusicBox")->getScheduler()->cancelAllTasks($this->getServer()->getPluginBase()->getPlugin("ZMusicBox"));
```

## Other Information
 - Use Minecraft Note Block Studio to convert midi files into nbs files.
Website: http://www.stuffbydavid.com/mcnbs
 - Please do not use this code nor these algorithms for other plugins
