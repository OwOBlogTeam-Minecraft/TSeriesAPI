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

use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use Teaclon\TSeriesAPI\Main;
use Teaclon\TSeriesAPI\plugin\PluginManager;
use Teaclon\TSeriesAPI\command\subcommand\BaseCommand;
use Teaclon\TSeriesAPI\command\CommandManager;


class PluginManagerCommand extends BaseCommand
{
	const MY_COMMAND             = "tsplugin";
	const MY_COMMAND_PEREMISSION = [self::PERMISSION_CONSOLE];
	protected $myprefix = PluginManager::MY_PREFIX;
	
	
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
			$this->sendMessage($sender, "§e--------------------§bTSeriesAPI指令助手§e--------------------");
			foreach(self::getHelpMessage() as $cmd => $message)
			{
				if($this->hasSenderPermission($sender, $cmd))
					$this->sendMessage($sender, str_replace("{cmd}", self::MY_COMMAND, $message));
				else continue;
			}
			$this->sendMessage($sender, "§e----------------------------------------------------------");
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
			
			
			case "makeplugin":
			case "mp":
			case "打包":
				if(($args[0] === "打包") || ($args[0] === "mp")) $args[0] = "makeplugin";
				if(!$this->hasSenderPermission($sender, $args[0]))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				
				if(!isset($args[1]))
				{
					$this->sendMessage($sender, "§c请输入一个有效的插件名称.");
					return true;
				}
				if(!$this->plugin->getPluginManager())
				{
					$this->sendMessage($sender, "§c存在DevTools, 无法使用这个功能.");
					return true;
				}
				$this->plugin->getPluginManager()->makePlugin($args[1]);
				return true;
			break;
			
			
			case "extractplugin":
			case "ep":
			case "解包":
				if(($args[0] === "解包") || ($args[0] === "ep")) $args[0] = "extractplugin";
				if(!$this->hasSenderPermission($sender, $args[0]))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				
				if(!isset($args[1]))
				{
					$this->sendMessage($sender, "§c请输入一个有效的插件名称.");
					return true;
				}
				if(!$this->plugin->getPluginManager())
				{
					$this->sendMessage($sender, "§c存在DevTools, 无法使用这个功能.");
					return true;
				}
				$this->plugin->getPluginManager()->extractPlugin($args[1]);
				return true;
			break;
		}
	}
	
	
	public static function getCommandPermission(string $cmd)
	{
		$cmds = 
		[
			"makeplugin" => [self::PERMISSION_CONSOLE],
			"extractplugin" => [self::PERMISSION_CONSOLE],
		];
		
		$cmd = strtolower($cmd);
		return isset($cmds[$cmd]) ? $cmds[$cmd] : self::PERMISSION_CONSOLE;
	}
	
	public static function getHelpMessage() : array
	{
		return 
		[
			"makeplugin" => "用法: §d/§6{cmd} makeplugin §f<§ePlugin Name§f>      §f打包一个插件",
			"extractplugin" => "用法: §d/§6{cmd} extractplugin §f<§ePlugin Name§f>   §f解包一个插件",
		];
	}
	
}
?>