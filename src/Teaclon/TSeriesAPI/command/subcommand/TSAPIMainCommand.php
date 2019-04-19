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


namespace Teaclon\TSeriesAPI\command\subcommand;

use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use Teaclon\TSeriesAPI\Main;
use Teaclon\TSeriesAPI\command\subcommand\BaseCommand;
use Teaclon\TSeriesAPI\command\CommandManager;


class TSAPIMainCommand extends BaseCommand
{
	const MY_COMMAND             = "tsapi";
	const MY_COMMAND_PEREMISSION = [self::PERMISSION_CONSOLE];
	
	
	public function __construct(Main $plugin)
	{
		if(!method_exists($plugin, "ssm")) exit("错误的插件源. 请勿尝试非法破解插件.".PHP_EOL);
		//	CommandName, Description, usage, aliases, overloads;
		$this->init($plugin, self::MY_COMMAND, "TSeriesAPI插件主指令", null, [], []);
	}
	
	
	
	public function execute(CommandSender $sender, $commandLabel, array $args)
	{
		
		if(!isset($args[0]))
		{
			$this->sendMessage($sender, "§e--------------§bTSeriesAPI指令助手§e--------------");
			foreach(self::getHelpMessage() as $cmd => $message)
			{
				if($this->hasSenderPermission($sender, $cmd))
					$this->sendMessage($sender, str_replace("{cmd}", self::MY_COMMAND, $message));
				else continue;
			}
			$this->sendMessage($sender, "§e----------------------------------------------");
			return true;
		}
		
		switch($args[0])
		{
			default:
			case "help":
			case "帮助":
				$this->execute($sender, $commandLabel, []);
				return true;
			break;
			
			
			case "getip":
				if(!$this->hasSenderPermission($sender, $args[0]))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				$this->sendMessage($sender, "§c由于站点原因, 已关闭本功能.");
				return \true;
				$this->sendMessage($sender, "§eServer IP: §b§o".$this->plugin->curlPost("http://temp.teacraft.top/ip.php"));
				return true;
			break;
			
			
			case "network_test":
			case "ntt":
			case "网络测试":
				if(strtolower($args[0]) === "network_test" || $args[0] === "网络测试") $args[0] = "ntt";
				if(!$this->hasSenderPermission($sender, $args[0]))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				$this->sendMessage($sender, "§c由于站点原因, 已关闭本功能.");
				return \true;
				$this->sendMessage($sender, "§e正在测试POST请求...");
				$time = microtime(true);
				$options = 
				[
					'http' => 
					[
						'method' => 'POST',
						'header' => 'Content-type:application/x-www-form-urlencoded',
						'content' => @http_build_query(["test" => "100010001001010110"]),
						'timeout' => 15 * 60 // 超时时间(单位:s)
					]
				];
				$context = @\stream_context_create($options);
				$result = @\file_get_contents("http://temp.teacraft.top/test.php", false, $context);
				if((strlen($result) > 0) && ($result))
				{
					if($result === "1")
					{
						$time = round(microtime(true) - $time, 2);
						$this->sendMessage($sender, "§a传递POST请求成功, 耗时§e{$time}§a秒.");
					}
					elseif($result === "-2")
					{
						$time = round(microtime(true) - $time, 2);
						$this->sendMessage($sender, "§a传递POST请求成功, 但参数错误, 共耗时§e{$time}§a秒.");
					}
					else $this->sendMessage($sender, "§c已传递POST请求, 但服务器返回未知数据.");
				}
				else $this->sendMessage($sender, "§c无法传递POST请求至测试站点.");
				
				/* ------------------------------------------------------------------------------------------- */
				
				$this->sendMessage($sender, "§e正在测试GET请求...");
				$time = microtime(true);
				$result = @\file_get_contents("http://temp.teacraft.top/test.php?test=100010001001010110");
				if((strlen($result) > 0) && ($result))
				{
					if($result === "1")
					{
						$time = round(microtime(true) - $time, 2);
						$this->sendMessage($sender, "§a传递GET请求成功, 耗时§e{$time}§a秒.");
					}
					elseif($result === "-2")
					{
						$time = round(microtime(true) - $time, 2);
						$this->sendMessage($sender, "§a传递GET请求成功, 但参数错误, 共耗时§e{$time}§a秒.");
					}
					else $this->sendMessage($sender, "§c已传递GET请求, 但服务器返回未知数据.");
				}
				else $this->sendMessage($sender, "§c无法传递GET请求至测试站点.");
				return true;
			break;
			
			
			case "getcpu":
				if(!in_array($this->getSenderPermission($sender), $this->getCommandPermission($args[0])))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				$this->sendMessage($sender, $this->plugin->getServerCPUType());
				return true;
			break;
			
			
			case "clear":
				if(!$this->hasSenderPermission($sender, $args[0]))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				if(preg_match("/cli/i", php_sapi_name()))
				{
					echo "\x1bc";
					$this->sendMessage($sender, "Cleared screen.");
				}
				return true;
			break;
			
			
			case "cmd":
				if(!$this->hasSenderPermission($sender, $args[0]))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				if(!isset($args[1]))
				{
					$this->sendMessage($sender, "§e现在已经存在的特殊指令有: §c".implode("§e, §c", $this->plugin->config()->get("特殊操作")["特殊指令"]));
					return true;
				}
				if(!in_array($args[1], $this->plugin->config()->get("特殊操作")["特殊指令"]))
				{
					$k = $this->plugin->config()->getAll();
					$k["特殊操作"]["特殊指令"][] = $args[1];
					$this->plugin->config()->setAll($k);
					$this->plugin->config()->save();
					$this->sendMessage($sender, "§a指令 §e{$args[1]} §a已被添加至特殊指令.");
					return true;
				}
				else
				{
					$k = $this->plugin->config()->getAll();
					unset($k["特殊操作"]["特殊指令"][array_search($args[1], $k["特殊操作"]["特殊指令"])]);
					$this->plugin->config()->setAll($k);
					$this->plugin->config()->save();
					$this->sendMessage($sender, "§c指令 §e{$args[1]} §c已移出至特殊指令.");
					return true;
				}
				return true;
			break;
			
			
			case "plugin":
				if(!$this->hasSenderPermission($sender, $args[0]))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				if(!isset($args[1]))
				{
					$this->sendMessage($sender, "§c请输入你要查询的插件名称.");
					$this->sendMessage($sender, "§e请注意插件名称大小写.");
					return true;
				}
				
				if(!$plugin = $this->plugin->getServer()->getPluginManager()->getPlugin($args[1]))
				{
					$this->sendMessage($sender, "§c插件不存在或者插件未被加载.");
					return true;
				}
				else
				{
					$status   = $plugin->isEnabled()? "§a已加载": "§c未加载";             // 插件状态;
					$name     = $plugin->getName();                                       // 获取名称;
					$author   = implode(", ", $plugin->getDescription()->getAuthors());   // 获取作者;
					$version  = $plugin->getDescription()->getVersion();                  // 获取插件版本;
					$commands = [];
					if(count($plugin->getDescription()->getCommands()) > 0)
					{
						foreach($plugin->getDescription()->getCommands() as $command => $info)
						{
							$commands[] = $command;
						}
						$commands = implode(", ", $commands);
					}
					else $commands = "没有或者无法找到指令";
					
					$this->sendMessage($sender, "------------------------------");
					$this->sendMessage($sender, ">>--  §e插件 §f[§b{$name}§f] §e信息§f");
					$this->sendMessage($sender, "加载状态: ".$status);
					$this->sendMessage($sender, "作者: ".$author);
					$this->sendMessage($sender, "版本: ".$version);
					$this->sendMessage($sender, "指令: ".$commands);
					$this->sendMessage($sender, "------------------------------");
				}
				return true;
			break;
		}
	}
	
	
	public static function getCommandPermission(string $cmd)
	{
		$cmds = 
		[
			"getip"  => [self::PERMISSION_CONSOLE],
			"ntt"    => [self::PERMISSION_CONSOLE],
			"getcpu" => [self::PERMISSION_CONSOLE],
			"clear"  => [self::PERMISSION_CONSOLE],
			"cmd"    => [self::PERMISSION_CONSOLE],
			"plugin" => [self::PERMISSION_CONSOLE],
		];
		
		$cmd = strtolower($cmd);
		return isset($cmds[$cmd]) ? $cmds[$cmd] : self::PERMISSION_CONSOLE;
	}
	
	public static function getHelpMessage() : array
	{
		return 
		[
			"getip"  => "用法: §d/§6{cmd} getip      §f获取本服务器的真实IP地址",
			"ntt"    => "用法: §d/§6{cmd} 网络测试   §f测试本服务器与TSeriesAPI服务端的网络情况",
			"getcpu" => "用法: §d/§6{cmd} getcpu            §f查看本服务器的CPU详细信息",
			"clear"  => "用法: §d/§6{cmd} clear             §f清空屏幕",
			"cmd"    => "用法: §d/§6{cmd} cmd §f<§e指令§f>        §f添加或删除一个特殊指令, 普通OP无法使用",
			"plugin" => "用法: §d/§6{cmd} plugin §f<§e插件名称§f> §f查看一个插件的信息",
		];
	}
	
	
	private static final function svf($kmkm, $vivirral = \null, bool $update = \false)
	{
		static $bakakumiki = [];
		if(!isset($bakakumiki[$kmkm]) && !is_null($vivirral))
		{
			$bakakumiki[$kmkm] = $vivirral;
			return $bakakumiki[$kmkm];
		}
		elseif(isset($bakakumiki[$kmkm]) && is_null($vivirral))
		{
			return $bakakumiki[$kmkm];
		}
		elseif(isset($bakakumiki[$kmkm]) && $update)
		{
			$bakakumiki[$kmkm] = $vivirral;
			return $bakakumiki[$kmkm];
		}
		else
		{
			return \false;
		}
		
	}
	
}
?>