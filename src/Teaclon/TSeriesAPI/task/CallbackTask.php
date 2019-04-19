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
use pocketmine\Server;
use pocketmine\scheduler\Task;

$server_name    = Server::getInstance()->getName();
$server_version = explode(",", Server::getInstance()->getVersion());
if(in_array($server_name, Main::DEFAULT_COMPATIBLE_KERNELS) && is_array($server_version))
{
	class CallbackTask extends Task
	{
		protected $callable;
		protected $args;
		
		public function __construct(callable $callable, array $args = [])
		{
			$this->callable = $callable;
			$this->args = $args;
			$this->args[] = $this;
		}
		
		
		public function getCallable()
		{
			return $this->callable;
		}
		
		public function onRun($currentTick)
		{
			call_user_func_array($this->callable, $this->args);
		}
	}
}
else
{
	class CallbackTask extends Task
	{
		protected $callable;
		protected $args;
		
		public function __construct(callable $callable, array $args = [])
		{
			$this->callable = $callable;
			$this->args = $args;
			$this->args[] = $this;
		}
		
		
		public function getCallable()
		{
			return $this->callable;
		}
		
		public function onRun(int $currentTick)
		{
			call_user_func_array($this->callable, $this->args);
		}
	}
}
?>
