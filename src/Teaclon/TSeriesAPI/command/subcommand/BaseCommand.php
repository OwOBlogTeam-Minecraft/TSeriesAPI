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

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

use Teaclon\TSeriesAPI\Main as TSAPI;
use Teaclon\TSeriesAPI\command\CommandManager;

abstract class BaseCommand extends Command
{
	const PERMISSION_ALL        = 9; // 全部权限;
	// const PERMISSION_ENTWICKLER = 6; // 开发者等级权限;
	const PERMISSION_CONSOLE    = 5; // 服务端控制台;
	const PERMISSION_HIGHEST    = 5; // 服务端控制台;
	const PERMISSION_ADMINO     = 4; // 插件白名单管理员(有OP权限);
	const PERMISSION_ADMINT     = 3; // 插件白名单管理员(无OP权限);
	const PERMISSION_OP         = 2; // OP权限;
	const PERMISSION_LOWEST     = 1; // 最低权限;
	const PERMISSION_NONE       = 0; // 没有权限;
	
	protected $myprefix = null;
	protected $plugin = null, $logger = null, $usePluginAdminList = null;
	protected static $ifCommandPermissionNotFound = self::PERMISSION_ADMINO;
	
	public function init(\pocketmine\plugin\Plugin $plugin, string $name, $description = "", $usageMessage = null, array $aliases = [], array $overloads = [])
	{
		$this->plugin = $plugin;
		$this->logger = \pocketmine\Server::getInstance()->getLogger();
		
		parent::__construct($name, $description, $usageMessage, $aliases, $overloads);
	}
	
	
	
	
	
	
	
	
	
	
	
	public final function hasSenderPermission(CommandSender $sender, string $command) : bool
	{
		$cmdPer = $this->getCommandPermission($command);
		if(in_array(self::PERMISSION_ALL, $cmdPer)) return \true;
		elseif(in_array($this->getSenderPermission($sender), $cmdPer)) return \true;
		else return \false;
	}
	
	public final function getSenderPermission(CommandSender $sender)
	{
		$isInWhiteList = ($this->usePluginAdminList === \true) ? $this->plugin->isPlayerAdmin($sender->getName()) : TSAPI::getInstance()->isPlayerAdmin($sender->getName());
		$isOp          = $sender->isOp();
		
		// if((!$sender instanceof Player) && (strtolower($sender->getName()) === "console") && (TSAPI::getInstance()->Bing19shfewlkddwey9rhefwl你好())) return self::PERMISSION_ENTWICKLER;
		if((!$sender instanceof Player) && (strtolower($sender->getName()) === "console")) return self::PERMISSION_CONSOLE;
		elseif($isOp  && $isInWhiteList)  return self::PERMISSION_ADMINO;
		elseif(!$isOp  && $isInWhiteList) return self::PERMISSION_ADMINT;
		elseif($isOp  && !$isInWhiteList) return self::PERMISSION_OP;
		elseif(!$isOp && !$isInWhiteList) return self::PERMISSION_LOWEST;
		else return self::PERMISSION_NONE;
	}
	
	
	protected final function sendMessage(CommandSender $sender, string $msg, string $prefix = TSAPI::PLUGIN_PREFIX)
	{
		$prefix = (isset($this->myprefix)) ? $this->myprefix : $prefix;
		$sender->sendMessage($prefix.$msg);
	}
	
	public abstract static function getCommandPermission(string $cmd);
	public abstract static function getHelpMessage() : array;
}
?>