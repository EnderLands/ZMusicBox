# ZMusicBox
Play music in your PocketMine-MP server using noteblocks!

This plugin is forked from **[SirGamer/ZMusicBox](https://github.com/SirGamer/ZMusicBox)**.

## Requirements
 - The server software that you use must have working Noteblock functionality
 - Noteblocks must be placed in the server
 - Songs must be in .nbs format in order to be played

## Usage
1) Place the `.phar` file in your plugins folder of the server
2) Run the server
3) Stop the server
4) Place `.nbs` files in the `/plugins/songs` directory of the server
5) Run the server
6) Place a noteblock

## Commands

 - `/music <start|stop|next>`

## API
ZMusicBox is also accessible from its API:
- Get Instance (both of the following would work)
```php
$instance = ZMusicBox\ZMusicBox::getInstance();

$instance = $this->getServer()->getPluginBase()->getPlugin("ZMusicBox");
```
 - Switch to the Next Song
```php
$instance->startTask();
```
 - Stop the music
```php
$instance->getScheduler()->cancelAllTasks();
```
- Select songs
```php
$instance->selectSong("Exmaple song");
```

## Other Information
 - Use Minecraft Note Block Studio to convert `.midi` files into `.nbs` files.
Website: https://opennbs.org/
 - Please do not use this code nor these algorithms for other plugins
