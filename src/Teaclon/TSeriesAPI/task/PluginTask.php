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


namespace Teaclon\TSeriesAPI\task;

use Teaclon\TSeriesAPI\Main;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\scheduler\Task;

/**
 * Base class for plugin tasks. Allows the Server to delete them easily when needed
 */

// var_dump(explode(",",Server::getInstance()->getVersion())[0]);
// var_dump(version_compare(explode(",",Server::getInstance()->getVersion())[0], "v1.1.0"));

$server_name    = Server::getInstance()->getName();
$server_version = explode(",", Server::getInstance()->getVersion());
if(in_array($server_name, Main::DEFAULT_COMPATIBLE_KERNELS) && is_array($server_version))
{
	abstract class PluginTask extends Task
	{
		protected $owner;
		
		public function __construct(Plugin $owner)
		{
			$this->owner = $owner;
		}
		
		public final function getOwner() : Plugin
		{
			return $this->owner;
		}
		
		public function onRun($currentTick)
		{
			$this->me($currentTick);
		}
	}
}
else
{
	abstract class PluginTask extends Task
	{
		protected $owner;
		
		public function __construct(Plugin $owner)
		{
			$this->owner = $owner;
		}
		
		public final function getOwner() : Plugin
		{
			return $this->owner;
		}
		
		public function onRun(int $currentTick)
		{
			$this->me($currentTick);
		}
		
	}
}
?>