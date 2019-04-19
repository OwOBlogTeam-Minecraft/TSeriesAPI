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

use pocketmine\Server;
use pocketmine\scheduler\Task;

final class TaskManager
{
	const MY_PREFIX = \Teaclon\TSeriesAPI\Main::PLUGIN_PREFIX."§bTaskManager §f> ";
	
	private $logger;
	private $plugin = null;
	private $task;                         // 服务器Task的Object;
	private $task_class = [];              // 已注册的Task缓存数组;
	private $task_id = 0;                  // 初始化Task时的id;
	private $callbackTask_arr = [];
	
	public function __construct(\Teaclon\TSeriesAPI\Main $plugin)
	{
		if(!method_exists($plugin, "ssm")) exit("错误的插件源. 请勿尝试非法破解插件.".PHP_EOL);
		$this->plugin    = $plugin;
		$this->logger    = \pocketmine\Server::getInstance()->getLogger();
		
		$this->task = method_exists('\pocketmine\Server', "getScheduler")
		? $plugin->getServer()->getScheduler()
		: (method_exists('\pocketmine\plugin\PluginBase', "getScheduler") ? $plugin->getScheduler() : $this->getScheduler());
		
		$plugin->ssm(self::MY_PREFIX."TaskManager loaded.", "info", "server");
		$plugin->ssm(self::MY_PREFIX."§efile §f\"§6Callbacktask§f\" §epath is: §f\"§a\\Teaclon\\TSeriesAPI\\task\\Callbacktask§f\"", "info", "server");
		$plugin->ssm(self::MY_PREFIX."§eyou also can use my API: §f\"§dTSeriesAPI§2::§dgetInstance§2()->§dgetTaskManager§2()->§dcreateCallbackTask§2(§oCallbackTask§r§2)§e\".", "info", "server");
	}
	
	
	
	
	
	
	
	public final function createCallbackTask(/* \pocketmine\plugin\Plugin  */$class, string $type, string $method, array $val = [], int $ticks, bool $display = \true)
	{
		if(!method_exists($class, $method))
		{
			throw new \Exception("§cUndefined Method §e{$method} §c.");
			return \false;
		}
		/* if(isset($this->callbackTask_arr[$method]))
		{
			$this->plugin->ssm(self::MY_PREFIX."§cCallbackTask §f\"§e{$method}§f\" §cis already registed in TSeriesAPI\\TaskManager.", "error", "server");
			return \false;
		} */
		return $this->registerTask($type, new CallbackTask([$class, $method], $val), $ticks, $display);
	}
	
	
	public final function cancelCallbackTask(string $method, bool $display = \true)
	{
		if(!isset($this->callbackTask_arr[$method]))
		{
			throw new \Exception("§cUndefined CallbackTask §e{$method} §c.");
			return \false;
		}
		$this->task->cancelTask($this->callbackTask_arr[$method]["task"]->getTaskId());
		if($display) $this->plugin->ssm(self::MY_PREFIX."§aCancelled CallbackTask §f\"§e".get_class($this->callbackTask_arr[$method]["class"])."§f\"", "info", "server");
		unset($this->task_class[$this->callbackTask_arr[$method]["id"]]);
		unset($this->callbackTask_arr[$method]);
		return \true;
	}
	
	
	public final function getTaskId(Task $task)
	{
		foreach($this->task_class as $id => $data)
		{
			if(isset($data["task"]) && ($data["task"] === $task)) return $id;
			else continue;
		}
		return false;
	}
	
	public final function registerTask(string $type, Task $task, int $time, bool $display = \true)
	{
		if(!method_exists($this->task, $type))
		{
			throw new \Exception("§cUndefined Method §e{$type} §c.");
			return \false;
		}
		else
		{
			if($task instanceof CallbackTask)
			{
				$this->callbackTask_arr[$task->getCallable()[1]] = ["class" => $task->getCallable()[0], "task" => $task, "id" => $this->task_id, "display" => $display];
				if($display) $this->plugin->ssm(self::MY_PREFIX."§aAdd CallbackTask §f\"§b{$type}§f\"§a from §f\"§e".get_class($task->getCallable()[0])."§f\"", "info", "server");
			}
			$this->task_class[$this->task_id] = ["task" => $task, "id" => $this->task_id, "display" => $display];
			++$this->task_id;
			$this->task->$type($task, $time);
			if(!$task instanceof CallbackTask){if($display) $this->plugin->ssm(self::MY_PREFIX."§aAdd Task type §f\"§b{$type}§f\"§a from §f\"§e".get_class($task)."§f\"", "info", "server");}
			return \true;
		}
	}
	
	public final function cancelTask(Task $task) // 注销一个Task;
	{
		$task_id = $this->getTaskId($task);
		if(is_bool($task_id) && !$task_id)
		{
			throw new \Exception("§cTask §f\"§e".get_class($task)."§f\" §cnot found.");
			return \false;
		}
		else
		{
			if(($task instanceof CallbackTask) && isset($this->callbackTask_arr[$task->getCallable()[1]]))
			{
				if($this->callbackTask_arr[$task->getCallable()[1]]["display"]) $this->plugin->ssm(self::MY_PREFIX."§aCancelled CallbackTask§f\"§e".$task->getCallable()[1]."§f\"", "info", "server");
				$this->cancelCallbackTask($task->getCallable()[1]);
				return \true;
			}
			if($this->task_class[$this->getTaskId($task)]["display"]) $this->plugin->ssm(self::MY_PREFIX."§aCancelled §f\"§e".get_class($task)."§f\"", "info", "server");
			$this->task->cancelTask($task->getTaskId());
			unset($this->task_class[$task_id]);
			return \true;
		}
	}
	
	public final function cancelAllTasks() // 注销全部Task;
	{
		foreach($this->task_class as $id => $data)
		{
			if(($data["task"] instanceof CallbackTask) && isset($this->callbackTask_arr[$data["task"]->getCallable()[1]]))
			{
				$this->cancelCallbackTask($data["task"]->getCallable()[1]);
				continue;
			}
			$this->task->cancelTask($data["task"]->getTaskId());
			$this->plugin->ssm(self::MY_PREFIX."§aCancelled §f\"§e".get_class($data["task"])."§f\"", "info", "server");
			unset($this->task_class[$id]);
		}
		return \true;
	}
	
	public final function getScheduler()
	{
		return $this->task;
	}
	
	
	public final function getName() : string
	{
		return "Task管理模块";
	}
	
}
?>