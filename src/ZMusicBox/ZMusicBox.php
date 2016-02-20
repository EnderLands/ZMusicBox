<?php

namespace ZMusicBox;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\level;
use pocketmine\Server;
use pocketmine\scheduler\PluginTask;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\network\protocol\BlockEventPacket;
use pocketmine\Player;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\math\Math;
use pocketmine\level\format\Chunk;
use pocketmine\level\format\FullChunk;

class ZMusicBox extends PluginBase implements Listener{
	public $song;
	public $MusicPlayer;
	public $opern;
	
	public function onEnable(){
		$this->getLogger()->info("ZMusicBox Is Loading!");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);	
		if(!is_dir($this->getPluginDir())){
			@mkdir($this->getServer()->getDataPath()."plugins/songs");
		}
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
		$this->song = $this->getRandomMusic();
		$this->opern = $this->song->get("opern");
		$this->MusicPlayer = new MusicPlayer($this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask($this->MusicPlayer, 20 / $this->song->get("tick"));
		$this->getLogger()->info("ZMusicBox Loaded!!!!!");
	} 
	
	public function getDirCount($PATH){
      $num = sizeof(scandir($PATH));
      $num = ($num>2)?$num-2:0;
	  return $num;
	}
	
	public function getPluginDir(){
		return $this->getServer()->getDataPath()."plugins/songs/";
	}
	
	public function getRandomMusic(){
		return new Config($this->getPluginDir().mt_rand(1,$this->getDirCount($this->getPluginDir())).".yml", Config::YAML, array());
	}
	
	public function getNearbyNoteBlock($x,$y,$z,$world){
        $nearby = [];
		$minX = $x - 5;
        $maxX = $x + 5;	
        $minY = $y - 5;
        $maxY = $y + 5;
        $minZ = $z - 5;
        $maxZ = $z + 5;
        
        for($x = $minX; $x <= $maxX; ++$x){
			for($y = $minY; $y <= $maxY; ++$y){
				for($z = $minZ; $z <= $maxZ; ++$z){
					$v3 = new Vector3($x, $y, $z);
					$block = $world->getBlock($v3);
					if($block->getID() == 25){
						$nearby[] = $block;
					}
				}
			}
		}
		return $nearby;
	}
	
	public function getFullBlock($x, $y, $z, $level){
		return $level->getChunk($x >> 4, $z >> 4, false)->getFullBlock($x & 0x0f, $y & 0x7f, $z & 0x0f);
	}
  
	public function Play($sound,$ins = null){
		foreach($this->getServer()->getOnlinePlayers() as $p){
			$noteblock = $this->getNearbyNoteBlock($p->x,$p->y,$p->z,$p->getLevel());
			if(!empty($noteblock)){
				$p->sendPopup("§b|->§6Now Playing§a:".$this->song->get("name")."§b<-|");
				foreach($noteblock as $block){
					$pk = new BlockEventPacket();
					$pk->x = $block->x;
					$pk->y = $block->y;
					$pk->z = $block->z;
					$pk->case1 = $this->song->get("instrument");
					if(!$ins and is_numeric($sound) and $sound > 0){//0 1 2 3 4 5 6 7
						$pk->case2 = $sound - 1;
						$p->dataPacket($pk);
					}elseif($ins == "["){//8 9 10 11 12 13 14 15
						$pk->case2 = $sound + 8 - 1;
						$p->dataPacket($pk);
					}elseif($ins == "]"){//16 17 18 19 20 21 22 23
						$pk->case2 = $sound + 16 - 1;
						$p->dataPacket($pk);
					}
				}
			}
		}
	}
		
	public function onDisable(){
		$this->getLogger()->info("ZMusicBox Unload Success!");
	}
	
	public function StartNewTask(){
		$this->song = $this->getRandomMusic();
		$this->opern = "";
		$this->getServer()->getScheduler()->cancelTasks($this);
		$this->MusicPlayer = new MusicPlayer($this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask($this->MusicPlayer, 20 / $this->song->get("tick"));
	}
	
}

class MusicPlayer extends PluginTask{

    public function __construct(ZMusicBox $plugin){
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }
	
	public function onRun($CT){
			if(($this->plugin->opern == $this->plugin->song->get("opern")) or (strlen($this->plugin->opern) >= strlen($this->plugin->song->get("opern")))){
				$this->plugin->StartNewTask();
			}
			$hasplayed = strlen($this->plugin->opern);
			$hasnotplayed = substr($this->plugin->song->get("opern"),$hasplayed);
			$shouldplay = substr($hasnotplayed,0,1);
			$this->plugin->opern = $this->plugin->opern.$shouldplay;
			if($shouldplay == "[" or $shouldplay == "]"){
				$hasplayed = strlen($this->plugin->opern);
				$hasnotplayed = substr($this->plugin->song->get("opern"),$hasplayed);
				$nshouldplay = substr($hasnotplayed,0,1);
				$this->plugin->opern = $this->plugin->opern.$nshouldplay;
				$this->plugin->Play($nshouldplay,$shouldplay);
			}else{
				$this->plugin->Play($shouldplay);
			}
	}

}