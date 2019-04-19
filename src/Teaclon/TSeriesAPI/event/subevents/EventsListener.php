<?php

/*                             Copyright (c) 2017-2018 TeaTech All right Reserved.
 *
 *      ████████████  ██████████           ██         ████████  ██           ██████████    ██          ██
 *           ██       ██                 ██  ██       ██        ██          ██        ██   ████        ██
 *           ██       ██                ██    ██      ██        ██          ██        ██   ██  ██      ██
 *           ██       ██████████       ██      ██     ██        ██          ██        ██   ██    ██    ██
 *           ██       ██              ████████████    ██        ██          ██        ██   ██      ██  ██
 *           ██       ██             ██          ██   ██        ██          ██        ██   ██        ████
 *           ██       ██████████    ██            ██  ████████  ██████████   ██████████    ██          ██
**/

namespace Teaclon\TSeriesAPI\event\subevents;

// Basic;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

use pocketmine\event\EventPriority as EP;
// Event;
// use pocketmine\event\block\BlockBreakEvent;
// use pocketmine\event\block\BlockPlaceEvent;
// use pocketmine\event\block\BlockUpdateEvent;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;
// use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

// use pocketmine\event\entity\EntityExplodeEvent;

// Level/Particle;
// use pocketmine\block\TNT;
// use pocketmine\block\BlockIds;
// use pocketmine\block\Block;
// use pocketmine\math\Vector3;
// use pocketmine\level\Level;
// use pocketmine\level\Position;
// use pocketmine\level\particle\FloatingTextParticle;


class EventsListener implements \pocketmine\event\Listener
{
	const LISTEN_HANDLER = EP::HIGHEST;
	const MY_PREFIX = \Teaclon\TSeriesAPI\Main::PLUGIN_PREFIX."§bEventsListener §f> ";
	
	// EVENT BEHAVIOR CONSTANTS;
	// const EVB_ONPLACE     = "放置";
	// const EVB_ONBREAK     = "破坏";
	// const EVB_ONUPDATE    = "更新";
	// const EVB_ONEXPLODE   = "爆炸";
	// const EVB_ONTAKEWATER = "舀水";
	// const EVB_ONPULLWATER = "倒水";
	// const EVB_ONTAKELAVA  = "舀岩浆";
	// const EVB_ONPULLLAVA  = "倒岩浆";
	
	
	private $server    = null;
	private $logger    = null;
	private $block     = null;
	private $cmdlog    = null;
	private $chatlog   = null;
	
	
	/*
		本类主要作用: 
		// [√] - 玩家放置/破坏方块记录
		[√] - 玩家发言记录
		[√] - 玩家使用指令记录
		[√] - 玩家登陆/退出时间+IP信息+设备记录
	*/
	
	
	public function __construct(\Teaclon\TSeriesAPI\Main $plugin, \Teaclon\TSeriesAPI\event\EventManager $eventManager)
	{
		if(!method_exists($plugin, "ssm")) exit("错误的插件源. 请勿尝试非法破解插件.".PHP_EOL);
		$this->plugin = $plugin;
		$this->server = $plugin->getServer();
		$this->logger = $plugin->getServer()->getLogger();
		
		// $this->block     = new Config($plugin->getDataFolder()."BlockLogs.yml", Config::YAML);
		$this->cmdlog    = new Config($plugin->getDataFolder()."CommandLogs.yml", Config::YAML);
		$this->chatlog   = new Config($plugin->getDataFolder()."ChatLogs.yml", Config::YAML);
		
		
		
		$eventManager->registerEvent($this, $plugin, PlayerChatEvent::class,     "onPlayerChat",    $eventManager->getEPLevel("highest"));
		// $eventManager->registerEvent($this, $plugin, BlockPlaceEvent::class,     "onBlockPlace",    $eventManager->getEPLevel("highest"));
		// $eventManager->registerEvent($this, $plugin, BlockBreakEvent::class,     "onBlockBreak",    $eventManager->getEPLevel("highest"));
		// $eventManager->registerEvent($this, $plugin, PlayerInteractEvent::class, "onPlayerTouch",   $eventManager->getEPLevel("highest"));
		// $eventManager->registerEvent($this, $plugin, EntityExplodeEvent::class,  "onEntityExplode", $eventManager->getEPLevel("highest"));
	}
	
	
	
	
	
	
	
	
	
#---[EVENT FUNCTIONS]--------------------------------------------------------------------------------------------#
	/* public function onBlockPlace(BlockPlaceEvent $e)
	{
		$this->addRecord(self::EVB_ONPLACE, $e->getBlock(), $e->getPlayer());
	}
	
	public function onBlockBreak(BlockBreakEvent $e)
	{
		$this->addRecord(self::EVB_ONBREAK, $e->getBlock(), $e->getPlayer());
	}
	
	public function onPlayerTouch(PlayerInteractEvent $e)
	{
		$player = $e->getPlayer();
		$block  = $e->getBlock();
		$level  = $block->getLevel();
		$inv    = $player->getInventory();
		
		$iteminhand = $inv->getItemInHand();
		$i = $iteminhand->getID();
		$d = $iteminhand->getDamage();
		
		if($e->isCancelled()) return null;
		switch($e->getFace())
		{
			default:
				$v = new Vector3($block->getX(), $block->getY(), $block->getZ());
			break;
				
			case 0:
				$v = new Vector3($block->getX(), $block->getY() - 1, $block->getZ());
			break;
			
			case 1:
				$v = new Vector3($block->getX(), $block->getY() + 1, $block->getZ());
			break;
			
			case 2:
				$v = new Vector3($block->getX(), $block->getY(), $block->getZ() - 1);
			break;
			
			case 3:
				$v = new Vector3($block->getX(), $block->getY(), $block->getZ() + 1);
			break;
			
			case 4:
				$v = new Vector3($block->getX() - 1, $block->getY(), $block->getZ());
			break;
			
			case 5:
				$v = new Vector3($block->getX() + 1, $block->getY(), $block->getZ());
			break;
		}
		$replaceBlock = $level->getBlock($v);
		
		if(($i == 325) && ($d >= 8) && ($d <= 9))                                 $this->addRecord(self::EVB_ONPULLWATER, $replaceBlock, $player);
		elseif(($i == 325) && ($block->getID() >= 8) && ($block->getID() <= 9))   $this->addRecord(self::EVB_ONTAKEWATER, $block,        $player);
		elseif(($i == 325) && ($d >= 10 && $d <= 11))                             $this->addRecord(self::EVB_ONPULLLAVA,  $replaceBlock, $player);
		elseif(($i == 325) && ($block->getID() >= 10) && ($block->getID() <= 11)) $this->addRecord(self::EVB_ONTAKELAVA,  $block,        $player);
	} */
	
	
	/* public function onPlayerJoin(PlayerJoinEvent $e)
	{
		$this->updatePlayerConfig($e->getPlayer());
	}
	
	public function onPlayerQuit(PlayerQuitEvent $e)
	{
		$this->updatePlayerConfig($e->getPlayer());
	} */
	
	public function onPlayerChat(PlayerChatEvent $e)
	{
		$p = $e->getPlayer();
		$n = $p->getName();
		
		$this->chatlog->set(time(), 
		[
			"player" => $n,
			"msg"    => $e->getMessage()
		]);
		$this->chatlog->save();
	}
	
	public function CommandCheck(PlayerCommandPreprocessEvent $e)
	{
		$p = $e->getPlayer();
		$n = $p->getName();
		$level = $p->getLevel()->getName();
		
		// array_shift(): 删除数组的第一个元素, 并且返回被删除元素的值;
		// substr(): 返回字符串中的部分字符串;
		$commandInfo = explode(" ", $e->getMessage());
		$command = substr(array_shift($commandInfo), 1);
		
		if($this->server->getCommandMap()->getCommand($command) === null) return \null;
		
		if(($command_special = in_array($command, $this->config->get("特殊操作")["特殊指令"])) && !$this->plugin->isPlayerAdmin($n))
		{
			if(!$e->isCancelled()) $e->setCancelled(true);
			$p->sendMessage("§c非法使用, 已禁止. 指令: §f/".$command);
			$this->getLogger()->notice("§f地图名称: §e{$level}§f; 玩家名称: §e{$n}§f; 指令: §c{$command}§f; 放行状态: §c被拦截");
		}
		$this->cmdlog->set(time(), 
		[
			"player"     => $n,
			"time"       => time(),
			"str_time"   => date("Y-m-d H:i:s"),
			"command"    => $command,
			"type"       => ($command_special ? "特殊指令" : "普通指令"),
			"level"      => $level,
			"permission" => ($this->plugin->isPlayerAdmin($n) ? "服主信任成员" : ($p->isOp() ? "OP" : "普通玩家")),
		]);
		$this->cmdlog->save();
	}
	
	
#---[SQL FUNCTIONS]--------------------------------------------------------------------------------------------#
	/* public function onEntityExplode(EntityExplodeEvent $e)
	{
		// TODO: 增加爆炸物判断;
		if ($e->isCancelled()) return null;
		$bx = $e->getPosition()->getFloorX();
		$by = $e->getPosition()->getFloorY();
		$bz = $e->getPosition()->getFloorZ();
		$level = $e->getPosition()->getLevel()->getName();
		
		$this->block->set("#TNT{".$level."-".$bx."-".$by."-".$bz."}", 
		[
			"type"       => "TNT爆炸",
			"player"     => $this->block->getNested($level."-".$bx."-".$by."-".$bz.".player"),
			"time"       => time(),
			"str_time"   => date("Y-m-d H:i:s"),
			"level"      => $level,
			"vector"     => $bx."-".$by."-".$bz,
			"block"      => BlockIds::TNT,
			"block_name" => "TNT",
			"missingBlocks" => [],
		]);
		
		var_dump($e->getBlockList());
		// foreach($e->getBlockList() as $b)
		// {
			// $x = $b->getX();
			// $y = $b->getY();
			// $z = $b->getZ();
			// // $this->block->setNested("#TNT{".$level."-".$bx."-".$by."-".$bz."}.explodeBlocks", 
		// }
		
		$this->block->save();
		$this->plugin->ssm(self::MY_PREFIX."§c世界 §b{$e->getPosition()->getLevel()->getName()} §c发生了TNT爆炸. 爆炸点坐标为: §e{$bx}§f-§e{$by}§f-§e{$bz}", "warning", "server");
	} */
	
	private function addRecord(string $type, Block $block, Player $player)
	{
		$x  = $block->getX();
		$y  = $block->getY();
		$z  = $block->getZ();
		$level = $block->getLevel()->getName();
		
		$this->block->set($level."-".$x."-".$y."-".$z, 
		[
			"type"       => $type,
			"player"     => $player->getName(),
			"time"       => time(),
			"str_time"   => date("Y-m-d H:i:s"),
			"level"      => $level,
			"vector"     => $x."-".$y."-".$z,
			"block"      => $block->getID().":".$block->getDamage(),
			"block_name" => $block->getName(),
		]);
		$this->block->save();
		return \true;
	}
	
	
	
	
	
	
	public final function stopListening()
	{
		
	}
	
	
}
?>