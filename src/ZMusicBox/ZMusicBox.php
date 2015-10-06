<?php

namespace ZMusicBox;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\level;
use pocketmine\Server;
use pocketmine\scheduler\CallbackTask;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\network\protocol\LevelEventPacket;

class ZMusicBox extends PluginBase implements Listener{
	private $switchr = false;
	private $song;
	private $p;
	private $p1;
	private $t = 0;
	
	public function onEnable(){ 
		$this->getLogger()->info("ZMusicBox IS Loading!");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		
		if(!is_dir($this->getPluginDir())){
			@mkdir($this->getServer()->getDataPath()."plugins/songs");
			mkdir($this->getPluginDir());
		}
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
		$this->song = new Config($this->getPluginDir() . "music.yml", Config::YAML, array());
		if(!$this->song->exists("name")){
            $this->switchr = false;
			$this->song->set("name", "");
            $this->song->save();
		}else{
			if($this->song->get("name") == ""){
				$this->switchr = false;
				$this->getLogger()->info(TextFormat::RED."No Song");
			}else{
				$this->getLogger()->info(TextFormat::GREEN."Song Name:".TextFormat::YELLOW.$this->song->get("name"));
			}			
		}
		if(!$this->song->exists("tick")){
            $this->switchr = false;
			$this->song->set("tick", "");
            $this->song->save();
		}else{
			if($this->song->get("tick") == ""){
				$this->switchr = false;
				$this->getLogger()->info(TextFormat::RED."Tick Check Failed");
			}else{
				$this->getLogger()->info(TextFormat::GREEN."Tick Check OK(".$this->song->get("tick").")");
			}			
		}
		if(!$this->song->exists("opern")){
            $this->switchr = false;
			$this->song->set("opern", "");
            $this->song->save();
		}else{
			if($this->song->get("opern") == ""){
				$this->switchr = false;
				$this->getLogger()->info(TextFormat::RED."Opern Check Failed");
			}else{
				$this->getLogger()->info(TextFormat::GREEN."Opern Check OK");
			}			
		}
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this,
		"Run"]),1 * 20 / $this->song->get("tick"));
		$this->p = "";
		$this->getLogger()->info("Loaded!!!!!");
	}
	
	public function getPluginDir(){
		return $this->getServer()->getDataPath()."plugins/songs/";
	}
	
	public function Run(){
		if($this->switchr == true){
			if($this->p == $this->song->get("opern")){
				$this->p = "";
			}
			if($this->song->exists("opern1") and $this->p1 == $this->song->get("opern1")){
				$this->p1 = "";
			}
			$hasplayed = strlen($this->p);
			$hasnotplayed = substr($this->song->get("opern"),$hasplayed);
			$shouldplay = substr($hasnotplayed,0,1);
			$this->p = $this->p.$shouldplay;
			$this->Play($shouldplay);
			if($this->song->exists("opern1")){
				$hasplayed = strlen($this->p1);
				$hasnotplayed = substr($this->song->get("opern1"),$hasplayed);
				$shouldplay = substr($hasnotplayed,0,1);
				$this->p1 = $this->p1.$shouldplay;
				$this->Play($shouldplay);
			}
		}
	}
	
	public function PlaySound($data){
		$pk = new LevelEventPacket;	
		$pk->evid = 1000;
		$pk->data = $data;
		foreach($this->getServer()->getOnlinePlayers() as $p){
			$pk->x = $p->getX();
			$pk->y = $p->getY();
			$pk->z = $p->getZ();
			$p->dataPacket($pk);
		}
	}
	
	
	public function Play($aound){
			switch($aound){
				case "a":
					$this->PlaySound(262);
					break;
				case "b":
					$this->PlaySound(277);
					break;
				case "c":
					$this->PlaySound(294);
					break;
				case "d":
					$this->PlaySound(311);
					break;
				case "e":
					$this->PlaySound(330);
					break;
				case "f":
					$this->PlaySound(349);
					break;
				case "g":
					$this->PlaySound(370);
					break;
				case "h":
					$this->PlaySound(392);
					break;
				case "i":
					$this->PlaySound(415);
					break;
				case "j":
					$this->PlaySound(440);
					break;
				case "k":
					$this->PlaySound(466);
					break;
				case "l":
					$this->PlaySound(494);
					break;
				case "m":
					$this->PlaySound(523);
					break;
				case "n":
					$this->PlaySound(554);
					break;
				case "o":
					$this->PlaySound(587);
					break;
				case "p":
					$this->PlaySound(622);
					break;
				case "q":
					$this->PlaySound(659);
					break;
				case "r":
					$this->PlaySound(698);
					break;
				case "s":
					$this->PlaySound(740);
					break;
				case "t":
					$this->PlaySound(784);
					break;
				case "u":
					$this->PlaySound(831);
					break;
				case "v":
					$this->PlaySound(880);
					break;
				case "w":
					$this->PlaySound(932);
					break;
				case "x":
					$this->PlaySound(988);
					break;
				case "y":
					$this->PlaySound(1046);
					break;	
				case "z":
					$this->PlaySound(1109);
					break;	
				case "A":
					$this->PlaySound(1175);
					break;	
				case "B":
					$this->PlaySound(1245);
					break;	
				case "C":
					$this->PlaySound(1318);
					break;
				case "D":
					$this->PlaySound(1397);
					break;
				case "E":
					$this->PlaySound(1480);
					break;
				case "F":
					$this->PlaySound(1568);
					break;
				case "G":
					$this->PlaySound(1661);
					break;
				case "H":
					$this->PlaySound(1760);
					break;
				case "I":
					$this->PlaySound(1865);
					break;
				case "J":
					$this->PlaySound(1976);
					break;
				case 0:
					break;
			}
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if($this->switchr == false){
			$this->switchr = true;
		}else{
			$this->switchr = false;
		}
	}
		
	public function onDisable(){
		$this->getLogger()->info("ZMusicBox Unload Success!");
	}
	
}
