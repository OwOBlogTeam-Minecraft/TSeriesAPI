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


namespace Teaclon\TSeriesAPI;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\utils\Utils;
use pocketmine\utils\Config;
use pocketmine\plugin\Plugin;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\event\EventPriority;
use pocketmine\event\HandlerList;
use pocketmine\plugin\MethodEventExecutor;

use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\protocol\TextPacket as MTextPacket;
use pocketmine\network\protocol\LoginPacket as MLoginPacket;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;

use Teaclon\TSeriesAPI\task\TaskManager;
use Teaclon\TSeriesAPI\command\CommandManager;
use Teaclon\TSeriesAPI\plugin\PluginManager;
use Teaclon\TSeriesAPI\event\EventManager;

final class Main extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener,\pocketmine\command\CommandExecutor
{
	
	const DEFAULT_COMPATIBLE_KERNELS = ["TC-TeaTech", "GP-TeaTech", "PocketMine-MP", "Altay", "GenisysPro", "Nebzz", "Nebzz-PMMP"];
	const NORMAL_PRE     = "TSeriesAPI";
	const PLUGIN_PREFIX  = "§cTSeriesAPI §f> ";
	const API_VERSION    = "1.11.0";
	const CORE_VERSION   = "2.4.5";
	// const CODENAME       = "NeliliyCa";
	const CODENAME       = "HunsFuLiy";
	
	const CONFIG_ADMIN_LIST = "管理员名单";
	const CONFIG_SPECIAL_DO = "特殊操作";
	// const CONFIG_SAFETY_EVENT_LISTENER = "安全监听事件者";
	
	private $server = null, $mypath = null, $config = null, $playerlog = null;
	private static $instance      = null;
	private $taskManager          = null; // Task管理;
	private $commandManager       = null; // Command管理;
	private $pluginManager        = null; // Plugin管理;
	private $eventManager         = null; // Event管理;
	private $interface_plugin     = [];   // 与本插件对接的插件;
	private static $player_packet = [];
	private $default_interface_plugin_data = 
	[
		"name"      => "",
		"author"    => "",
		"version"   => "",
		"api"       => [],
		"commands"  => [],
		"object"    => null
	];
	
	
	public function onLoad()
	{
		if(version_compare($this->getDescription()->getVersion(), self::CORE_VERSION) != 0)
		{
			self::stopThread($this->getName(), "私自更改插件版本导致服务器崩溃");
		}
		else
		{
			self::$instance = $this;
			$this->server = $this->getServer();
			$this->ssm(self::PLUGIN_PREFIX."§e成功读取 ".self::NORMAL_PRE, "info", "server");
			$this->ssm(self::PLUGIN_PREFIX."§e检测到本服务器核心: §c{$this->server->getName()}", "info", "server");
			$this->checkKernelCompatibility("TSeriesAPI");
			// $this->getServer()->getPluginManager()->enablePlugin($this);
			return \null;
		}
		exit("Thread overflow".PHP_EOL);
	}
	
	public function onEnable()
	{
		$this->logger = $this->getServer()->getLogger();
		$this->mypath = $this->getDataFolder();
		if(!is_dir($this->mypath)) mkdir($this->mypath, 0777, true);
		$this->config  = new Config($this->mypath."config.yml", Config::YAML, 
		[
			self::CONFIG_ADMIN_LIST => [],
			self::CONFIG_SPECIAL_DO => 
			[
				"操作密码" => mt_rand(100000, 999999),
				"特殊指令" => ["op", "deop", "stop", "plugins", "version", "status", "mp", "ep", "ms", "extractplugin", "makeplugin", "makeserver"]
			],
			// self::CONFIG_SAFETY_EVENT_LISTENER => \true,
		]);
		$this->playerlog = new Config($this->mypath."playerLogs.yml", Config::YAML);
		
		$this->ssm(self::PLUGIN_PREFIX."§6-----------------------------------------------------", "info", "server");
		$this->ssm(self::PLUGIN_PREFIX."§bTSAPI§f(API_VERSION=§dv§e".self::API_VERSION."§f, CODENAME=§e".self::CODENAME."§f)", "info", "server");
		$this->ssm(self::PLUGIN_PREFIX."§aCopyright (c) §e2016-".date("Y")." §aTeaTech All right Reserved.", "info", "server");
		$this->ssm(self::PLUGIN_PREFIX."§6-----------------------------------------------------", "info", "server");
		$this->ssm(self::PLUGIN_PREFIX."§bTSAPI §a已成功启动 §f(内核版本§dv§a".self::CORE_VERSION."§f).", "info", "server");
		$this->ssm(self::PLUGIN_PREFIX."§e插件作者:   Teaclon§f(§b锤子§f)", "info", "server");
		$this->ssm(self::PLUGIN_PREFIX."§6-----------------------------------------------------", "info", "server");
		$this->taskManager    = new TaskManager($this);
		$this->commandManager = new CommandManager($this);
		$this->eventManager   = new EventManager($this);
		$this->pluginManager  = new PluginManager($this);
		// $this->server->getPluginManager()->registerEvents($this, $this);
		$this->eventManager->registerEvent($this, $this, PlayerPreLoginEvent::class, "onPlayerPreLogin", $this->eventManager->getEPLevel("highest"));
		$this->eventManager->registerEvent($this, $this, PlayerQuitEvent::class, "onPlayerQuit", $this->eventManager->getEPLevel("highest"));
		// $this->taskManager->registerTask("scheduleRepeatingTask", new \Teaclon\TSeriesAPI\task\CallbackTask([$this, "createTocken"]), 20 * 60 * 1);
	}
	
	public function onDisable()
	{
		if(($this->taskManager != null) && ($this->commandManager != null) && ($this->eventManager != null))
		{
			$this->taskManager->cancelAllTasks();
			$this->commandManager->unregisterAllCommands();
			$this->eventManager->unregisterEvents();
		}
		$this->ssm(self::PLUGIN_PREFIX."§c本插件已卸载.");
	}
	
	
	
	
	
	
	
	
	
	private function updatePlayerConfig(Player $player)
	{
		$n = strtolower($player->getName());
		if(!$this->playerlog->exists($n)) $this->playerlog->set($n, $this->getDefaultConfig($player));
		else
		{
			$this->playerlog->setNested($n.".last-login-time", time());
			$this->playerlog->setNested($n.".address", $player->getAddress());
			$this->playerlog->setNested($n.".DeviceModel", $this->getPlayerClientData($n)["DeviceModel"]);
			$this->playerlog->setNested($n.".DeviceOS", $this->getPlayerClientData($n)["DeviceOS"]);
			$this->playerlog->setNested($n.".GameVersion", $this->getPlayerClientData($n)["GameVersion"]);
		}
		$this->playerlog->save();
	}
	
	public function onPlayerPreLogin(PlayerPreLoginEvent $e)
	{
		$this->updatePlayerConfig($e->getPlayer());
	}
	
	public function onPlayerQuit(PlayerQuitEvent $e)
	{
		$this->updatePlayerConfig($e->getPlayer());
	}
	
	public function onDataPacketReceive(\pocketmine\event\server\DataPacketReceiveEvent $e)
	{
		$packet = $e->getPacket();
		$Confirm_LoginPacket = (\class_exists('\pocketmine\network\protocol\LoginPacket', \false))
		? (($packet instanceof LoginPacket) || ($packet instanceof MLoginPacket)) : ($packet instanceof LoginPacket);
		if($Confirm_LoginPacket)
		{
			self::$player_packet[$packet->username] = $packet;
			// $this->ssm("[§eDataReceiver§f] Received a packet with: ".get_class($packet), "info", "server");
		}
	}
	
	// public function onDataPacketSend(\pocketmine\event\server\DataPacketSendEvent $e)
	private static final function nothing()
	{
		return \false;
		$packet = $e->getPacket();
		/* if(($packet instanceof \pocketmine\network\mcpe\protocol\BatchPacket) || ($packet instanceof \pocketmine\network\protocol\BatchPacket) || 
		   ($packet instanceof \pocketmine\network\mcpe\protocol\SetTimePacket) || ($packet instanceof \pocketmine\network\protocol\SetTimePacket) || 
		   ($packet instanceof \pocketmine\network\mcpe\protocol\FullChunkDataPacket) || ($packet instanceof \pocketmine\network\protocol\FullChunkDataPacket)
		  )
		{
			$this->ssm("§f[§eDataSender§f] Sent a packet with: ".get_class($packet), "info", "server");
		}
		else
		{
			$this->ssm("§f[§bDataSender§f]§b Sent a packet with: §6".get_class($packet), "notice", "server");
			var_dump($packet);
		} */
		$Confirm_TextPacket = (\class_exists('\pocketmine\network\protocol\TextPacket', false))
		? (($packet instanceof TextPacket) || ($packet instanceof MTextPacket)) : ($packet instanceof TextPacket);
		if($Confirm_TextPacket)
		{
			$original_character = "EasyAuth";   // 需要修改的内容;
			$msg = $packet->message;                                    // 原始消息(未修改);
			
			foreach(["0", "1", "2", "3", "4","5", "6", "7", "8", "9", "a", "b", "c", "d", "e", "f", "k", "l", "m", "n", "o", "r"] as $character)
			{$msg = \str_replace(\pocketmine\utils\TextFormat::ESCAPE.$character, "", $msg);} // 删除颜色标识;
			
			if((bool) \preg_match_all("/{$original_character}/i", $msg, $instead)) // 查找内容"[Server]";
			{
				$instead = "数字的小可爱"; // 最终替换的内容;
				$msg = \str_replace($original_character, $instead, $packet->message); // 替换内容;
				$packet->message = $msg;
				// $this->ssm("[§eDataSenderer§f] §c已检测到需要替换的字符 §f\"§e{$original_character}§f\"§c, 已将该字符篡改为 §f\"{$instead}§f\"§c.", "info", "server");
			}
		}
	}
	
	
	
#---[PACKETS FUNCTIONS]---------------------------------------------------------------------------------------#
	public final function getDeviceOS(string $player)
	{
		if($this->server->getPlayer($player) && \method_exists('\pocketmine\Player', "getDeviceOS")) return $this->server->getPlayer($player)->getDeviceOS();
		else return $this->getPlayerClientData($player)["DeviceOS"];
	}
	
	
	public final function getDefaultConfig($player = "") : array
	{
		$isPlayer   = ($player instanceof Player);
		$name       = $isPlayer ? $player->getName() : $player;
		$time       = date("Y-m-d H:i:s");
		$clientData = $this->getPlayerClientData($name);
		return 
		[
			"name"                   => $name,
			"create-time"            => $time,
			"create-strtime"         => (int) strtotime($time),
			"last-login-time"        => (int) strtotime($time),
			// "group"                  => (array) ["none"],
			"qq-number"              => (string) "",
			"email"                  => (string) "",
			// "level"                  => (int) 0,
			// "recentUsedCommand"      => (array) [],
			"language"               => $isPlayer ? $clientData["LanguageCode"] : (string) "",
			"identification-number"  => $isPlayer ? md5(strtotime($time).$name) : (string) "",
			"Xuid"                   => $isPlayer ? ((method_exists($player, "getXuid")) ? $player->getXuid() : "none")          : (string) "",
			// "Uuid"                   => $isPlayer ? $player->getUniqueId()      : (string) "",
			"address"                => $isPlayer ? $player->getAddress()       : (string) "",
			"DeviceModel"            => $isPlayer ? $clientData["DeviceModel"]  : (string) "",
			"DeviceOS"               => $isPlayer ? $clientData["DeviceOS"]     : (string) "",
			"GameVersion"            => $isPlayer ? $clientData["GameVersion"]  : (string) "",
		];
	}
	
	public final function getPlayerClientData(string $player) : array
	{
		return isset(self::$player_packet[$player]) ? self::$player_packet[$player]->clientData : 
		// 防止下面getDefaultConfig函数调用时报错(数组元素不存在);
		[
			"CapeData"          => (string) "UNKNOWN",
			"ClientRandomId"    => (int) 0,
			"CurrentInputMode"  => (int) 0,
			"DefaultInputMode"  => (int) 0,
			"DeviceModel"       => (string) "UNKNOWN",
			"DeviceOS"          => (int) 0,
			"GameVersion"       => (int) 0,
			"GuiScale"          => (int) 0,
			"LanguageCode"      => (string) "UNKNOWN",
			"PlatformChatId"    => (string) "UNKNOWN",
			"ServerAddress"     => (string) "UNKNOWN",
			"SkinData"          => (string) "UNKNOWN",
			"SkinGeometry"      => (string) "UNKNOWN",
			"SkinGeometryName"  => (string) "UNKNOWN",
			"SkinId"            => (string) "UNKNOWN",
			"ThirdPartyName"    => (string) "UNKNOWN",
			"UIProfile"         => (int) 1
		];
	}
	
	public final function getStringGamemode(int $mode, bool $all = \false) : string
	{
		$modes = [0 => "生存模式", 1 => "创造模式", 2 => "冒险模式", 3 => "旁观者模式"];
		if($all == true) return "§f(§60§f)§e生存模式§f, (§61§f)§e创造模式§f, (§62§f)§e冒险模式§f, (§63§f)§e旁观者模式";
		return (!isset($modes[$mode])) ? "Mode {$mode} not found" : $modes[$mode];
	}
	
	public final function getStringDeviceOS(int $os_n, bool $getall = \false) : string
	{
		$os = 
		[
			0  => "Unknown",
			1  => "Android",   // Android 设备;
			2  => "IOS",       // ios 设备, iPad, iPod, iPhone;
			3  => "Mac-OS-X",  // Apple Mac OS X (MacBook);
			4  => "Fire-OS",   // Amazon平板的操作系统, 基于 Android 5.0 开发;
			5  => "Gear-VR",   // 三星的VR设备;
			6  => "HoloLens",  // 微软的VR设备;
			7  => "Win10",     // Windows 10 专用设备;
			8  => "Win32",     // Windows x86 设备;
			9  => "Dedicated", // 未知, 貌似是某一种专用设备;
			10 => "Orbis-OS",  // PlayStation 4 的内核操作系统;
			11 => "NX-OS",     // https://www.jianshu.com/p/5a388afe7687 ;
		];
		
		return (!$getall) ? ((!isset($os[$os_n])) ? \false : $os[$os_n]) : $os;
	}
	
	public final function CompareDeviceOS(int $os_n, string $os_sn) : string
	{
		return $this->getStringDeviceOS($os_n) ? ($this->getStringDeviceOS($os_n) === $os_sn) : "Mode {$os_n} not found";
	}
	
	public final function setPlayerQQNumber(string $n, int $qq_num) : void
	{
		$this->playerlog->setNested($n.".qq-number", $qq_num);
		$this->playerlog->save();
	}
	
	public final function setPlayerEMail(string $n, int $email) : void
	{
		$this->playerlog->setNested($n.".email", $email);
		$this->playerlog->save();
	}
	
#---[核心兼容性检测函数]--------------------------------------------------------------------------------------------#
	// 简单检测核心的兼容性;
	public static final function checkKernelCompatibility(string $kill_plugin_name, array $compatible_Kernel = self::DEFAULT_COMPATIBLE_KERNELS)
	{
		if(!in_array(Server::getInstance()->getName(), $compatible_Kernel))
		{
			Server::getInstance()->forceShutdown();
				$pid = getmygid();
				if($pid != 0)
				{
					switch(Utils::getOS())
					{
						case "win":
							exec("taskkill.exe /F /PID " . ((int) $pid) . " > NUL");
						break;
						case "mac":
						case "linux":
						default:
							(function_exists("posix_kill")) ?  posix_kill($pid, SIGKILL) : exec("kill -9 " . ((int) $pid) . " > /dev/null 2>&1");
						break;
					}
				}
				exit(str_replace("{kill_plugin_name}", $kill_plugin_name, base64_decode('Cgo+PiBQTFVHSU4gIntraWxsX3BsdWdpbl9uYW1lfSIgSVMgTk9UIENPTVBBVElCTEUgV0lUSCBUSElTIFBvY2tldE1pbmUKLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tClNvcnJ5LCB0aGlzIHNlcnZlciBkb2Vzbid0IHdvcmsgd2l0aCBwbHVnaW4gIntraWxsX3BsdWdpbl9uYW1lfSIhClBsZWFzZSBKT0lOIFRlbmNlbnQgUVEgR3JvdXAgOTgzMzE0NjMgdG8gZ2V0IGEgZGVkaWNhdGVkIFBvY2tldE1pbmUtS2VybmVsLgpTZXJ2ZXIgd2lsbCBjbG9zZSBzb21lIHNlY29uZHMgbGF0ZXIgYW5kIHdpbGwgYXV0b21hdGljYWxseSBkZWxldGUgdGhpcyBwbHVnaW4uCi0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQ==')).PHP_EOL);
		}
	}
	
	
	// 获取核心"Protocol"Class;
	public static final function getKernelNetWorkPath()
	{
		
		$old_path = "\\pocketmine\\network\\protocol\\Info";                    // (int) 10; OLD(旧的目录);
		$new_path = "\\pocketmine\\network\\mcpe\\protocol\\ProtocolInfo";      // (int) 11; NEW(新的目录);
		
		$class = \interface_exists($old_path, \false) ? 10 : (\interface_exists($new_path, \false) ? 11: \false);
		return (!\is_int($class)) ? \false : $class;
	}
	
	
	// 获取核心协议版本;
	public static final function getCurrentProtocol()
	{
		$path = self::getKernelNetWorkPath();
		$path = (!\is_bool($path) && $path == 10)
		? "\\pocketmine\\network\\protocol\\Info"
		: ((!\is_bool($path) && $path == 11) ? "\\pocketmine\\network\\mcpe\\protocol\\ProtocolInfo" : \false);
		return (\is_string($path)) ? $path::CURRENT_PROTOCOL : 'error';
	}
	
	
	public function isSafetyEventListenerEnabled() : bool
	{
		return ($this->config()->exists(self::CONFIG_SAFETY_EVENT_LISTENER)) ? (bool) $this->config()->get(self::CONFIG_SAFETY_EVENT_LISTENER) : \false;
	}
	
	
#---[OTHER FUNCTIONS]--------------------------------------------------------------------------------------------#
	public final function setMeEnable(Plugin $plugin) // 插件对接的时候需要调用的函数;
	{
		$plugin_name = $plugin->getName();
		if(!isset($this->interface_plugin[$plugin_name]))
		{
			$this->interface_plugin[$plugin_name] = $this->default_interface_plugin_data;
			$this->interface_plugin[$plugin_name]["name"]     = $plugin_name;
			$this->interface_plugin[$plugin_name]["author"]   = $plugin->getDescription()->getAuthors();
			$this->interface_plugin[$plugin_name]["version"]  = $plugin->getDescription()->getVersion();
			$this->interface_plugin[$plugin_name]["api"]      = $plugin->getDescription()->getCompatibleApis();
			$this->interface_plugin[$plugin_name]["commands"] = $plugin->getDescription()->getCommands();
			$this->interface_plugin[$plugin_name]["object"]   = $plugin;
			
			$this->ssm(self::PLUGIN_PREFIX."§aInterfaced plugin §e{$plugin_name} §ato §bTS§dA§dP§6I§a.", "info", "server");
			return $this;
		}
		else return \false;
	}
	
	
	// 卸载一个插件;
	public static function disableMeFromServer(Plugin $plugin)
	{
		Server::getInstance()->getPluginManager()->disablePlugin($plugin);
		return \true;
	}
	
	
	// 返回玩家的配置文件的路径;
	public final function getPlayerDataFileInString(string $player)
	{
		return $this->getServer()->getDataPath()."players".DIRECTORY_SEPARATOR .strtolower($player).".dat";
	}
	
	
	// 获取变量名;                          ↓ 地址传递, 即被定义的变量变化该变量也会同时发生变化;
	public final function get_variable_name(&$var, $scope = null)
	{
		$scope = ($scope == null) ? $GLOBALS : $scope; // 如果没有范围则在globals中寻找
		
		// 因有可能有相同值的变量, 因此先将当前变量的值保存到一个临时变量中;
		// 然后再对原变量赋唯一值, 以便查找出变量的名称, 找到名字后, 将临时变量的值重新赋值到原变量;
		$tmp = $var;
		
		$var = 'tmp_value_'.mt_rand();
		$name = array_search($var, $scope, true); // 根据值查找变量名称;
		
		$var = $tmp;
		return $name;
	}
	
	
	public final function getServerCPUType()
	{
		// FreeBSD;
		function get_key($keyName)
		{
			$buffer = "";
			
			$path = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin', '/usr/local/sbin');
			foreach($path as $p) $command = (@\is_executable("$p/sysctl")) ? "$p/sysctl" : \false;
			
			if($command === \false) return '';
			if($fp = @popen("$command -n {$keyName}", 'r'))
			{
				while(!@\feof($fp)) $buffer .= @\fgets($fp, 4096);
				return \trim($buffer);
			}
			else return '';
		}
		
		// Windows;
		function GetWMI()
		{
			if(PHP_VERSION >= 5)
			{
				$wmi = (new \COM("WbemScripting.SWbemLocator"))->ConnectServer();
				// $prop = $wmi->get("Win32_PnPEntity");
			}
			else return '';
			
			$strValue = ["Name","L2CacheSize","NumberOfCores"];
			
			$arrData    = [];
			$objWEBM    = $wmi->Get("Win32_Processor");
			$arrProp    = $objWEBM->Properties_;
			$arrWEBMCol = $objWEBM->Instances_();
			foreach($arrWEBMCol as $objItem) 
			{
				@\reset($arrProp);
				$arrInstance = [];
				foreach($arrProp as $propItem) 
				{
					@eval("\$value = \$objItem->" . $propItem->Name . ";");
					if(empty($strValue)) $arrInstance[$propItem->Name] = \trim($value);
					else
					{
						if(\in_array($propItem->Name, $strValue)) $arrInstance[$propItem->Name] = \trim($value);
					}
				}
				$arrData[] = $arrInstance;
			}
			
			$cpu_num = $arrData[0]['NumberOfCores'];
			if($cpu_num == null) $cpu_num = 1;
			$L2CacheSize = ' ('.$arrData[0]['L2CacheSize'].')';
			$x1 = ($cpu_num == 1) ? '' : ' ×'.$cpu_num;
			$res['model'] = $arrData[0]['Name'].$L2CacheSize.$x1;
			return $res;
		}
		
		switch(PHP_OS)
		{
			case "Linux":
				if(($str = @file("/proc/cpuinfo")) === \false) return \false;
				$str = implode("", $str);
				@\preg_match_all("/model\s+name\s{0,}\:+\s{0,}([\w\s\)\(\@.-]+)([\r\n]+)/s", $str, $model);
				@\preg_match_all("/cpu\s+MHz\s{0,}\:+\s{0,}([\d\.]+)[\r\n]+/", $str, $mhz);
				@\preg_match_all("/cache\s+size\s{0,}\:+\s{0,}([\d\.]+\s{0,}[A-Z]+[\r\n]+)/", $str, $cache);
				@\preg_match_all("/bogomips\s{0,}\:+\s{0,}([\d\.]+)[\r\n]+/", $str, $bogomips);
				if(\is_array($model[1]) !== \false)
				{
					$num      = sizeof($model[1]);
					$cpu      = $model[1][0];
					$mhz      = ' | 频率: '.$mhz[1][0];
					$cache    = ' | 二级缓存: '.$cache[1][0];
					$bogomips = ' | Bogomips: '.$bogomips[1][0];
					return "核心数: {$num}; 详细信息: ".$cpu.$mhz.$cache.$bogomips;
				}
				else return '';
			break;
			
			case "FreeBSD":
				return get_key("hw.model")." x".get_key("hw.ncpu");
			break;
			
			case "WINNT":
				return 'WINDOWS NT Cannot use this Method.';
			break;
			
			default:
				return 'Unknown System';
			break;
			
		}
	}
	
	
	// 通过CURL POST请求一个网址;
	public final function curlPost($url, $post = '', $autoFollow = 0, string $ip = "")
	{
		$ch = curl_init();
		$user_agent = 'Safari Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_1) AppleWebKit/537.73.11 (KHTML, like Gecko) Version/7.0.1 Safari/5';
		curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
		// 2. 设置选项, 包括URL
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		if($ip !== "") curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR: {$ip}', 'CLIENT-IP: {$ip}'));  // 构造IP
		curl_setopt($ch, CURLOPT_REFERER, "http://www.baidu.com/");   // 构造来路
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		if($autoFollow)
		{
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // 启动跳转链接
			curl_setopt($ch, CURLOPT_AUTOREFERER, true);  // 多级自动跳转
		}
		if($post != '')
		{
			curl_setopt($ch, CURLOPT_POST, 1);  // POST提交方式
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}   
		// 3. 执行并获取HTML文档内容
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}
	
	
	public function randomString(int $length = 10, string $type)
	{
		$type_arr = ["IntOnly", "StringSmallLettersOnly", "StringBigLettersOnly", "StringMixedLettersOnly", "Mixed"];
		if(!in_array($type, $type_arr)) return "Type incorrect! You must input type like ".implode(", ", $type_arr);
		$randomString = $characters = null;
		switch($type)
		{
			case "IntOnly":
				$characters = '0123456789';
			break;
			
			case "StringSmallLettersOnly":
				$characters = 'abcdefghijklmnopqrstuvwxyz';
			break;
			
			case "StringBigLettersOnly":
				$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			break;
			
			case "StringMixedLettersOnly":
				$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			break;
			
			case "Mixed":
				$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			break;
		}
		$charactersLength = strlen($characters);
		for($i = 0; $i < $length; $i++)
		{
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
	
	
	// 将整型时间转换为时间段;
	public function onTimeChange(int $n)
	{
		$h = time() - $n;
		// $h -= ;
		if($h < 60)                            $r = $h . '秒前';
		elseif($h >= 60 && $h < 3600)          $r = floor($h / 60)      . '分钟前';
		elseif($h >= 3600 && $h < 86400)       $r = floor($h / 3600)    . '小时前';
		elseif($h >= 86400 && $h < 2592000)    $r = floor($h / 86400)   . '天前';
		elseif($h >= 2592000 && $h < 15552000) $r = floor($h / 2592000) . '个月前';
		return $r;
	}
	
	
	
#---[LOGGER FUNCTIONS]--------------------------------------------------------------------------------------------#
	/**
		用法: self::ssm(信息, 日志记录等级, 发送形式)
	**/
	public final function ssm($msg, $level = "info", $type = "logger")
	{
		if(($msg === "") || ($level === "") || ($type === ""))
		{
			Server::getInstance()->getLogger()->error(self::NORMAL_PRE."[LOGGER] Error Usage(0010)");
		}
		elseif(!\in_array($level, ["info", "warning", "error", "notice", "debug", "alert", "critical", "emergency"]))
		{
			Server::getInstance()->getLogger()->error(self::NORMAL_PRE."[LOGGER] Error Usage(0015)");
		}
		elseif(!\in_array($type, ["server", "logger"]))
		{
			Server::getInstance()->getLogger()->error(self::NORMAL_PRE."[LOGGER] Error Usage(0020)");
		}
		else
		{
			$color = ($level === "notice") ? "§r§b" : null;
			if($type === "server") Server::getInstance()->getLogger()->$level($color.$msg);
			elseif($type === "logger") $this->getLogger()->$level($color.$msg);
		}
	}
	
	public final function addAdmin(string $player_name)
	{
		$list = $this->config()->get(self::CONFIG_ADMIN_LIST);
		if(!$this->isPlayerAdmin($player_name)) ($list[] = $player_name);
		else unset($list[array_search($player_name, $list)]);
		$this->config()->set(self::CONFIG_ADMIN_LIST, $list);
		return $this->config()->save();
	}
	
	public final function isPlayerAdmin(string $player_name)
	{
		return in_array($player_name, $this->config()->get(self::CONFIG_ADMIN_LIST));
	}
	
	public static final function stopThread($plugin_name, $msg, $error_code = "")
	{
		Server::getInstance()->getLogger()->error("§c§l服务器已崩溃, 正在关闭服务器.");
		Server::getInstance()->getLogger()->error("§c§l服务器已崩溃, 正在关闭服务器.");
		Server::getInstance()->forceshutdown();
		if($error_code === "") $error_code = "NULL";
		exit("ERROR: >> Plugin: {$plugin_name}; Cause: {$msg}; Code: {$error_code}".PHP_EOL);
	}
	
	public static final function getInstance()
	{
		return self::$instance;
	}
	
	public final function getTaskManager() : TaskManager
	{
		return $this->taskManager;
	}
	
	public final function getCommandManager() : CommandManager
	{
		return $this->commandManager;
	}
	
	public final function getPluginManager() : PluginManager
	{
		return $this->pluginManager;
	}
	
	public final function getEventManager() : EventManager
	{
		return $this->eventManager;
	}
	
	public final function config() : Config
	{
		return $this->config;
	}
}

?>