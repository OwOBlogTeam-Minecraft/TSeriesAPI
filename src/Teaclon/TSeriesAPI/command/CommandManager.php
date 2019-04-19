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

namespace Teaclon\TSeriesAPI\command;

use pocketmine\command\Command;

use Teaclon\TSeriesAPI\command\subcommand\TSAPIMainCommand;
use Teaclon\TSeriesAPI\command\subcommand\PluginManagerCommand;

final class CommandManager
{
	const MY_PREFIX = "§bCommandManager §f> ";
	
	
	
	private $cmdreg;
	private $plugin = null;
	private static $instance = null;
	private static $temp_command_class = [];    // 已注册指令的缓存数组;
	private $default_command_class_arr =   // 默认指令;
	[
		TSAPIMainCommand::class,
		PluginManagerCommand::class,
	];
	
	
	public function __construct(\Teaclon\TSeriesAPI\Main $plugin)
	{
		if(!method_exists($plugin, "ssm")) exit("错误的插件源. 请勿尝试非法破解插件.".PHP_EOL);
		self::$instance = $this;
		$this->plugin   = $plugin;
		$this->cmdreg   = $plugin->getServer()->getCommandMap();
		$plugin->ssm($plugin::PLUGIN_PREFIX.self::MY_PREFIX."CommandManager loaded.", "info", "server");
		
		if(count($this->default_command_class_arr) == 0) return null;
		foreach($this->default_command_class_arr as $class) $this->registerCommand(new $class($plugin));
	}
	
	
	
	
	
	
	
	
	// 获取指令权限;
	public final function getCommandPermission($command, bool $getAll = \false)
	{
		if($command instanceof Command)
		{
			$a = array_flip($this->getTempCommandClass());
			/* if(!isset($a[get_class($command)]))
			{
				throw new \Exception("Command class \"".get_class($command)."\" does not register in TSeriesAPI");
				return \false;
			} */
			$cmd_permission = (new \ReflectionClass(get_class($command)))->getConstant("MY_COMMAND_PEREMISSION");
			if(\is_bool($cmd_permission) && !$cmd_permission)
			{
				throw new \Exception("Class \"".get_class($command)."\" undefined constant \"MY_COMMAND_PEREMISSION\"");
				return \false;
			}
			if(!is_array($cmd_permission))
			{
				throw new \Exception("Class constant \"MY_COMMAND_PEREMISSION\" must be an Array");
				return \false;
			}
			return $cmd_permission;
		}
		elseif(is_string($command))
		{
			/* if(!$this->isRegisteredCommand($command))
			{
				throw new \Exception("Command \"".get_class($command)."\" does not register in TSeriesAPI");
				return \false;
			} */
			$command = $this->getRegisteredClass($command);
			$cmd_permission = (new \ReflectionClass($command))->getConstant("MY_COMMAND_PEREMISSION");
			if(\is_bool($cmd_permission) && !$cmd_permission)
			{
				throw new \Exception("Class \"{$command}\" undefined constant \"MY_COMMAND_PEREMISSION\"");
				return \false;
			}
			if(!is_array($cmd_permission))
			{
				throw new \Exception("Class constant \"MY_COMMAND_PEREMISSION\" must be an Array");
				return \false;
			}
			return $cmd_permission;
		}
		else
		{
			throw new \Exception("Incoming type error.");
			return \false;
		}
	}
	
	
	
	// 获取默认的CommandClass;
	public final function getDefaultCommandClass() : array
	{
		return $this->default_command_class_arr;
	}
	
	// 获取已经注册的缓存CommandClass;
	public final function getTempCommandClass() : array
	{
		return $this->default_command_class_arr;
	}
	
	// 注册一个Command;
	public final function registerCommand(Command $class)
	{
		$command = (new \ReflectionClass(get_class($class)))->getConstant("MY_COMMAND");
		if(\is_bool($command) && !$command)
		{
			throw new \Exception("Undefined class constant \"MY_COMMAND\", Cannot register command class with \"".get_class($class)."\"");
			return \false;
		}
		$this->getCommandPermission($class);
		self::$temp_command_class[$command] = $class;   // 将需要注册的指令存入缓存数组;
		// $this->plugin->ssm("Command \"{$command}\"'s Permission: ".implode(", ", $this->getCommandPermission($class)));
		return $this->cmdreg->register($command, $class);
	}
	
	// 删除一个Command;
	public final function unregisterCommand(Command $class)
	{
		$command = (new \ReflectionClass(get_class($class)))->getConstant("MY_COMMAND");
		if(\is_bool($command) && !$command)
		{
			throw new \Exception("Undefined class constant \"MY_COMMAND\", Cannot register command class with \"".get_class($class)."\"");
			return \false;
		}
		if(!isset(self::$temp_command_class[$command]))
		{
			throw new \Exception("Command class \"".get_class($class)."\" has not register in TSeriesAPI");
			return \false;
		}
		unset(self::$temp_command_class[$command]);
		return $this->cmdreg->unregister($class);
	}
	
	// 删除所有Command;
	public final function unregisterAllCommands()
	{
		foreach(self::$temp_command_class as $command => $class)
		{
			if(\method_exists($this->cmdreg, "unregister")) $this->cmdreg->unregister($class);
		}
		self::$temp_command_class = [];
		return \true;
	}
	
	// 获取指令的类;
	public final function getRegisteredClass(string $command)
	{
		return $this->isRegisteredCommand($command) ? $this->default_command_class_arr[$command] : \false;
	}
	
	// 检查一个指令是否注册在了本类;
	public final function isRegisteredCommand(string $command)
	{
		return isset($this->default_command_class_arr[$command]);
	}
	
	public static final function getInstance()
	{
		return self::$instance;
	}
}
?>