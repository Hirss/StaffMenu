<?php

namespace Hirss\StaffUI;

use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\plugin\PluginBase as PL;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use jojoe77777\FormAPI;
use pocketmine\utils\TextFormat as TF;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\event\player\{PlayerInteractEvent, PlayerDropItemEvent, PlayerItemHeldEvent, PlayerPreLoginEvent};
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\inventory\Inventory;

class Main extends PL implements Listener {
	// Thx SonsaYt lol
	public $staffList = [];
	public $targetPlayer = [];
	
    public function onEnable(){
		$this->getServer()->getLogger()->info(TF::GREEN . "v1.0.0 Enabled!");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->db = new \SQLite3($this->getDataFolder() . "TempBanUI.db");
		$this->db->exec("CREATE TABLE IF NOT EXISTS banPlayers(player TEXT PRIMARY KEY, banTime INT, reason TEXT, staff TEXT);");
				$this->message = (new Config($this->getDataFolder() . "Message.yml", Config::YAML, array(
		
		"BroadcastBanMessage" => "§b{player} has been banned for §b{day} §dday/s, §b{hour} §dhour/s, §b{minute} §dminute/s by a staff. §dReason: §b{reason}",
		"KickBanMessage" => "§dYou are banned for §b{day} §dday/s, §b{hour} §dhour/s, §b{minute} §dminute/s. \n§dReason: §b{reason}",
		"LoginBanMessage" => "§dYou are still banned for §b{day} §dday/s, §b{hour} §dhour/s, §b{minute} §dminute/s, §b{second} §dsecond/s. \n§dReason: §b{reason}",
		
		"BanMyself" => "§cYou can't ban yourself",
		"NoBanPlayers" => "§bNo banned players",
		"UnBanPlayer" => "§b{player} has been unbanned",
		"BanListTitle" => "§lBANNED PLAYER LIST",
		"BanListContent" => "Choose a player",
		"InfoUIContent" => "§dInformation: \nDay: §b{day} \n§dHour: §b{hour} \n§dMinute: §b{minute} \n§dSecond: §b{second} \n§dReason: §b{reason} \n§dBanned by: §b{punisher} \n\n\n",
		"InfoUIUnBanButton" => "Unban Player",
		
		)))->getAll();
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool{

        switch($cmd->getName()){
        	case "tban":
				if($sender instanceof Player) {
					if($sender->hasPermission("use.tban")){
						if(count($args) == 0){
							$this->openPlayerListUI($sender);
						}
						if(count($args) == 1){
							if($args[0] == "on"){
								if(!isset($this->staffList[$sender->getName()])){
									$this->staffList[$sender->getName()] = $sender;
									$sender->sendMessage($this->message["BanModeOn"]);
								}
							} else if ($args[0] == "off"){
								if(isset($this->staffList[$sender->getName()])){
									unset($this->staffList[$sender->getName()]);
									$sender->sendMessage($this->message["BanModeOff"]);
								}
							} else {
								$this->targetPlayer[$sender->getName()] = $args[0];
								$this->openTbanUI($sender);
							}
						}
					}
				}
				else{
					$sender->sendMessage(TextFormat::RED . "Use this Command in-game.");
					return true;
				}
			break;
			case "tcheck":
				if($sender instanceof Player) {
					if($sender->hasPermission("use.tcheck")){
						$this->openTcheckUI($sender);
					}
				}
			break;
		
        
		case "sinfo":
		
			if(!($sender instanceof Player)){
			}
		
			$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
			$form = $api->createSimpleForm(function (Player $sender, $data){
            $result = $data;
            if ($result == null) {
            }
            switch($result) {
                case 0:
                    $command = "smenu";
					$this->getServer()->getCommandMap()->dispatch($sender, $command);
				break;
            }
			});
			$form->setTitle("§4Info Staff");
			$form->setContent("§d Staff of the server:\n -");
			$form->addButton("§cRetour au Menu", 0);
			$form->sendToPlayer($sender);
			break;
        case "smenu":
		
			if(!($sender instanceof Player)){
			}
			$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
			$form = $api->createSimpleForm(function (Player $sender, $data){
            $result = $data;
            if ($result == null) {
            }
            switch($result) {
                case 0:
                    $this->kickMenu($sender);
				break;
                case 1:
         			$command = "tban";
					$this->getServer()->getCommandMap()->dispatch($sender, $command);
                break;
                case 2:
                    $this->muteMenu($sender);
				break;
                case 3:
                    $command = "sinfo";
					$this->getServer()->getCommandMap()->dispatch($sender, $command);
				break;
              case 4:
                    $command = "brush";
					$this->getServer()->getCommandMap()->dispatch($sender, $command);
				break;
              case 5:
                       $command = "gamemode c";
       			    $this->getServer()->getCommandMap()->dispatch($sender, $command) ;
   			   break;
              case 6:
                       $command = "gamemode s";
        			   $this->getServer()->getCommandMap()->dispatch($sender, $command) ;
 			     break;
              case 7:
        	        $command = "tcheck";
					$this->getServer()->getCommandMap()->dispatch($sender, $command);
				break;
            }
			});
			$form->setTitle("§5Global StaffUI");
			$form->setContent("§7StaffUI by Mev");
			$form->addButton("§cKickUI", 0);
			$form->addButton("§cBanUI", 1);
			$form->addButton("§cMuteUI", 2);
			$form->addButton("§cStaff Info", 3);
            $form->addButton("§c BrushUI", 4);
            $form->addButton("§c GameMode\n Creative", 5);
            $form->addButton("§c GameMode\n Survival", 6);
			$form->addButton("§c Ban list", 7);
			$form->sendToPlayer($sender);
			break;
 } 
 return true;
} 
public function kickMenu($sender){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createCustomForm(function (Player $sender, array $data = null){
			$result = $data[0];
			if($result === null){
				return true;
			}
		});
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$player = $player->getPlayer();
			$this->playerList[strtolower($player->getName())] = $player;
			$list[] = $player->getName();
		}
		$server = $this->getServer();
		$formapi = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $formapi->createCustomForm(function (Player $sender, array $data) use ($server){

          $result = $data[0];
     //     $reason = $data[1];

          if($result === null){
                return;
          } else {
                $server->dispatchCommand($sender, "kick ".$result);
                var_dump($data);
          }
});
		$form->setTitle(TF::BOLD . "§5Staff - Kick Player");
		$form->addDropdown("\nChoose player", $list, 1);
		$form->addInput("Reason");
		$form->sendToPlayer($sender);
		return $form;
	}

public function banMenu($sender) {
	$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createCustomForm(function (Player $sender, array $data = null){
			$result = $data[0];
			if($result === null){
				return true;
			}
			$c = 0;
			foreach($this->playerList as $player){
				if($result == $c){
					$target = $player->getPlayer();	
					if($target instanceof Player){
						if($target->getName() == $sender->getName()){
							$player->sendMessage($this->message["BanMyself"]);
							return true;
						}
						$now = time();
						$day = ($data[1] * 17280000);
						$hour = ($data[2] * 3600);
						$min = ($data[3] * 60);
						$banTime = $now + $day + $hour + $min;
						$banInfo = $this->db->prepare("INSERT OR REPLACE INTO banPlayers (player, banTime, reason) VALUES (:player, :banTime, :reason);");
						$banInfo->bindValue(":player", $target->getName());
						$banInfo->bindValue(":banTime", $banTime);
						$banInfo->bindValue(":reason", $data[4]);
						// $banInfo->bindValue(":punisher", $sender->getName());
						$result = $banInfo->execute();
						$target->kick(str_replace(["{day}", "{hour}", "{minute}", "{reason}"], [$data[1], $data[2], $data[3], $data[4]], $this->message["KickBanMessage"]));
						$this->getServer()->broadcastMessage(str_replace(["{player}", "{day}", "{hour}", "{minute}", "{reason}"], [$sender->getName(), $target->getName(), $data[1], $data[2], $data[3], $data[4]], $this->message["BroadcastBanMessage"]));
						foreach($this->playerList as $player){
							unset($this->playerList[strtolower($player->getName())]);
						}
					}
				}
				$c++;
			}
		});
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$player = $player->getPlayer();
			$this->playerList[strtolower($player->getName())] = $player;
			$list[] = $player->getName();
		}
		$form->setTitle("§5Staff - Ban Hammer");
		$form->addDropdown("\nChoose player", $list);
		$form->addSlider("Day/s", 0, 200, 1);
		$form->addSlider("Hour/s", 0, 24, 1);
		$form->addSlider("Minut/s", 1, 60, 5);
		$form->addInput("Reason");
		$form->addInput("The player is disconnected?");
		$form->sendToPlayer($sender);
		return $form;
	} 
	
	public function muteMenu($sender) {
	$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createCustomForm(function (Player $sender, array $data = null){
			$result = $data[0];
			if($result === null){
				return true;
			}
			$c = 0;
			foreach($this->playerList as $player){
				if($result == $c){
					$target = $player->getPlayer();	
					if($target instanceof Player){
						if($target->getName() == $sender->getName()){
							$player->sendMessage("Mute player");
							return true;
						}
						$now = time();
						$day = ($data[1] * 86400);
						$hour = ($data[2] * 3600);
						$min = ($data[3] * 60);
						$banTime = $now + $day + $hour + $min;
						$banInfo = $this->db->prepare("INSERT OR REPLACE INTO mutePlayers (player, muteTime, reason, punisher) VALUES (:player, :muteTime, :reason, :punisher);");
						$banInfo->bindValue(":player", $target->getName());
						$banInfo->bindValue(":muteTime", $banTime);
						$banInfo->bindValue(":reason", $data[4]);
						$banInfo->bindValue(":punisher", $sender->getName());
						$result = $banInfo->execute();
						$target->kick(str_replace(["{day}", "{hour}", "{minute}", "{reason}"], [$data[1], $data[2], $data[3], $data[4]], $this->message["KickBanMessage"]));
						$this->getServer()->broadcastMessage(str_replace(["{punisher}", "{player}", "{day}", "{hour}", "{minute}", "{reason}"], [$sender->getName(), $target->getName(), $data[1], $data[2], $data[3], $data[4]], $this->message["BroadcastBanMessage"]));
						foreach($this->playerList as $player){
							unset($this->playerList[strtolower($player->getName())]);
						}
					}
				}
				$c++;
			}
		});
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$player = $player->getPlayer();
			$this->playerList[strtolower($player->getName())] = $player;
			$list[] = $player->getName();
		}
		$form->setTitle("§5Staff - Mute");
		$form->addDropdown("\nChoose player", $list);
		$form->addSlider("Day/s", 0, 30, 1);
		$form->addSlider("Hour/s", 0, 24, 1);
		$form->addSlider("Minute/s", 1, 60, 5);
		$form->addInput("Reason");
		$form->sendToPlayer($sender);
		return $form;
	} 
	public function onInteract(PlayerInteractEvent $event){
    $item = $event->getItem();
	$player = $event->getPlayer();
	$itemname = $item->getCustomName();
    if ($itemname === "§l§2StaffUI"){
		$player->getServer()->dispatchCommand($player, "smenu");
		}
	}
	
	public function openTcheckUI($player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, $data = null){
			if($data === null){
				return true;
			}
			$this->targetPlayer[$player->getName()] = $data;
			$this->openInfoUI($player);
		});
		$banInfo = $this->db->query("SELECT * FROM banPlayers;");
		$array = $banInfo->fetchArray(SQLITE3_ASSOC);	
		if (empty($array)) {
			$player->sendMessage($this->message["NoBanPlayers"]);
			return true;
		}
		$form->setTitle($this->message["BanListTitle"]);
		$form->setContent($this->message["BanListContent"]);
		$banInfo = $this->db->query("SELECT * FROM banPlayers;");
		$i = -1;
		while ($resultArr = $banInfo->fetchArray(SQLITE3_ASSOC)) {
			$j = $i + 1;
			$banPlayer = $resultArr['player'];
			$form->addButton(TextFormat::BOLD . "$banPlayer", -1, "", $banPlayer);
			$i = $i + 1;
		}
		$form->sendToPlayer($player);
		return $form;
	}
	public function hitBan(EntityDamageEvent $event){
		if($event instanceof EntityDamageByEntityEvent) {
			$damager = $event->getDamager();
			$victim = $event->getEntity();
			if($damager instanceof Player && $victim instanceof Player){
				if(isset($this->staffList[$damager->getName()])){
					$event->setCancelled(true);
					$this->targetPlayer[$damager->getName()] = $victim->getName();
					$this->openTbanUI($damager);
				}
			}
		}
	}
	public function openPlayerListUI($player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, $data = null){
			$target = $data;
			if($target === null){
				return true;
			}
			$this->targetPlayer[$player->getName()] = $target;
			$this->banMenu($player);
		});
		$form->setTitle("BanUI");
		$form->setContent("List Player");
		foreach($this->getServer()->getOnlinePlayers() as $online){
			$form->addButton($online->getName(), -1, "", $online->getName());
		}
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function openInfoUI($player){
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, int $data = null){
		$result = $data;
		if($result === null){
			return true;
		}
			switch($result){
				case 0:
					$banplayer = $this->targetPlayer[$player->getName()];
					$banInfo = $this->db->query("SELECT * FROM banPlayers WHERE player = '$banplayer';");
					$array = $banInfo->fetchArray(SQLITE3_ASSOC);
					if (!empty($array)) {
						$this->db->query("DELETE FROM banPlayers WHERE player = '$banplayer';");
						$player->sendMessage("Player unbanned");
					}
					unset($this->targetPlayer[$player->getName()]);
				break;
			}
		});
		$banPlayer = $this->targetPlayer[$player->getName()];
		$banInfo = $this->db->query("SELECT * FROM banPlayers WHERE player = '$banPlayer';");
		$array = $banInfo->fetchArray(SQLITE3_ASSOC);
		if (!empty($array)) {
			$banTime = $array['banTime'];
			$reason = $array['reason'];
			$staff = $array['staff'];
			$now = time();
			if($banTime < $now){
				$banplayer = $this->targetPlayer[$player->getName()];
				$banInfo = $this->db->query("SELECT * FROM banPlayers WHERE player = '$banplayer';");
				$array = $banInfo->fetchArray(SQLITE3_ASSOC);
				if (!empty($array)) {
					$this->db->query("DELETE FROM banPlayers WHERE player = '$banplayer';");
					$player->sendMessage(str_replace(["{player}"], [$banplayer], $this->message["AutoUnBanPlayer"]));
				}
				unset($this->targetPlayer[$player->getName()]);
				return true;
			}
			$remainingTime = $banTime - $now;
			$day = floor($remainingTime / 86400);
			$hourSeconds = $remainingTime % 86400;
			$hour = floor($hourSeconds / 3600);
			$minuteSec = $hourSeconds % 3600;
			$minute = floor($minuteSec / 60);
			$remainingSec = $minuteSec % 60;
			$second = ceil($remainingSec);
		}
		$form->setTitle(TextFormat::BOLD . $banPlayer);
		$form->setContent(str_replace(["{day}", "{hour}", "{minute}", "{second}", "{reason}", "{staff}"], [$day, $hour, $minute, $second, $reason, $staff], $this->message["InfoUIContent"]));
		$form->addButton($this->message["InfoUIUnBanButton"]);
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function onPlayerLogin(PlayerPreLoginEvent $event){
		$player = $event->getPlayer();
		$banplayer = $player->getName();
		$banInfo = $this->db->query("SELECT * FROM banPlayers WHERE player = '$banplayer';");
		$array = $banInfo->fetchArray(SQLITE3_ASSOC);
		if (!empty($array)) {
			$banTime = $array['banTime'];
			$reason = $array['reason'];
			$staff = $array['staff'];
			$now = time();
			if($banTime > $now){
				$remainingTime = $banTime - $now;
				$day = floor($remainingTime / 17280000);
				$hourSeconds = $remainingTime % 17280000;
				$hour = floor($hourSeconds / 3600);
				$minuteSec = $hourSeconds % 3600;
				$minute = floor($minuteSec / 60);
				$remainingSec = $minuteSec % 60;
				$second = ceil($remainingSec);
				$player->close("", str_replace(["{day}", "{hour}", "{minute}", "{second}", "{reason}", "{staff}"], [$day, $hour, $minute, $second, $reason, $staff], $this->message["LoginBanMessage"]));
			} else {
				$this->db->query("DELETE FROM banPlayers WHERE player = '$banplayer';");
			}
		}
		if(isset($this->staffList[$player->getName()])){
			unset($this->staffList[$player->getName()]);
		}
	}
	
    public function onDisable(){
        $this->getServer()->getLogger()->info(TF::GREEN . "v1.0.0 Disabled!");
    }
}
