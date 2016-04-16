<?php

namespace ZMusicBox;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
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
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Binary;
use ZMusicBox\NoteBoxAPI;

class ZMusicBox extends PluginBase implements Listener{
	public $song;
	public $MusicPlayer;
	public $name;
	
	public function onEnable(){
		$this->getLogger()->info("ZMusicBox is loading!");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		if(!is_dir($this->getPluginDir())){
			@mkdir($this->getServer()->getDataPath()."plugins/songs");
		}
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
		if(!$this->CheckMusic()){
			$this->getLogger()->info("§bPlease put in nbs files!!!");
		}else{
			$this->StartNewTask();
		}
		$this->getLogger()->info("ZMusicBox loaded!!!!!");
	} 

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
		switch($cmd->getName()) {
			case "music":
				if(isset($args[0])){
					switch($args[0]){
						case "next":
							$this->StartNewTask();
							$sender->sendMessage(TextFormat::GREEN."Switched to next song");
							return true;
							break;
					}
				}else{
					$sender->sendMessage(TextFormat::RED."Usage:/music <next>");
				}
			break;		
		}
	}
	
	public function CheckMusic(){
		if($this->getDirCount($this->getPluginDir()) > 0 and $this->RandomFile($this->getPluginDir(),"nbs")){
			return true;
		}
		return false;
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
		$dir = $this->RandomFile($this->getPluginDir(),"nbs");
		if($dir){
			$api = new NoteBoxAPI($this,$dir);
			return $api;
		}
		return false;
	}
	
	Public function RandomFile($folder='', $extensions='.*'){
		$folder = trim($folder);
		$folder = ($folder == '') ? './' : $folder;
		if (!is_dir($folder)){
			return false;
		}
		$files = array();
		if ($dir = @opendir($folder)){
			while($file = readdir($dir)){
				if (!preg_match('/^\.+$/', $file) and
					preg_match('/\.('.$extensions.')$/', $file)){
					$files[] = $file;        
				}      
			}   
			closedir($dir);  
		}else{
			return false;
		}
		if (count($files) == 0){
			return false;
		}
		mt_srand((double)microtime()*1000000);
		$rand = mt_rand(0, count($files)-1);
		if (!isset($files[$rand])){
			return false;
		}
		$this->name = str_replace('.nbs', '', $files[$rand]);
		return $folder . $files[$rand];
	}
	
	public function getNearbyNoteBlock($x,$y,$z,$world){
        $nearby = [];
		$minX = $x - 5;
        $maxX = $x + 5;	
        $minY = $y - 5;
        $maxY = $y + 5;
        $minZ = $z - 2;
        $maxZ = $z + 2;
        
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
  
	public function Play($sound,$type = 0){
		foreach($this->getServer()->getOnlinePlayers() as $p){
			$noteblock = $this->getNearbyNoteBlock($p->x,$p->y,$p->z,$p->getLevel());
			if(!empty($noteblock)){
				if($this->song->name != ""){
					$p->sendPopup("§b|->§6Now Playing: §a".$this->song->name."§b<-|");
				}else{	
					$p->sendPopup("§b|->§6Now Playing: §a".$this->name."§b<-|");
				}
				$i = 0;
				while ($i <= 3){
					$i ++;
					$block = current($noteblock);
					next($noteblock);
					if($block){
						$pk = new BlockEventPacket();
						$pk->x = $block->x;
						$pk->y = $block->y;
						$pk->z = $block->z;
						$pk->case1 = $type;
						if(is_numeric($sound) and $sound > 0){
							$pk->case2 = $sound;
							$p->dataPacket($pk);
						}
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
		$this->getServer()->getScheduler()->cancelTasks($this);
		$this->MusicPlayer = new MusicPlayer($this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask($this->MusicPlayer, 2000 / $this->song->speed );
	}
	
}

class MusicPlayer extends PluginTask{

    public function __construct(ZMusicBox $plugin){
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }
	
	public function onRun($CT){
		if(isset($this->plugin->song->sounds[$this->plugin->song->tick])){
			foreach($this->plugin->song->sounds[$this->plugin->song->tick] as $data){
				$this->plugin->Play(...$data);
			}
		}
		$this->plugin->song->tick++;
		if($this->plugin->song->tick > $this->plugin->song->length){
			$this->plugin->StartNewTask();
		}
	}

}
