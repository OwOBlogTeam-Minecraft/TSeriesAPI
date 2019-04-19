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

namespace Teaclon\TSeriesAPI\event;

use pocketmine\plugin\Plugin;
use pocketmine\event\Listener;
use pocketmine\event\Event;
use pocketmine\event\EventPriority as EP;
use pocketmine\event\HandlerList;
use pocketmine\plugin\MethodEventExecutor as MEE;

use Teaclon\TSeriesAPI\Main;

/**
	本类主要作用为: 
		[√] - 事件注册
**/


final class EventManager
{
	const MY_PREFIX = Main::PLUGIN_PREFIX."§bEventManager §f> ";
	
	private $server, $logger;
	private $plugin = null;
	
	private static $regd_event_class = [];  // 已注册的事件的缓存数组;
	
	
	public function __construct(\Teaclon\TSeriesAPI\Main $plugin)
	{
		if(!method_exists($plugin, "ssm")) exit("错误的插件源. 请勿尝试非法破解插件.".PHP_EOL);
		$this->plugin = $plugin;
		$this->server = \pocketmine\Server::getInstance();
		$this->logger = \pocketmine\Server::getInstance()->getLogger();
		
		$plugin->ssm(self::MY_PREFIX."EventManager loaded.", "info", "server");
		$plugin->ssm(self::MY_PREFIX."§eyou can use my API: §f\"§dTSeriesAPI§2::§dgetInstance§2()->§dgetEventManager§2()->§dgetApis§2()§e\".", "info", "server");
		new \Teaclon\TSeriesAPI\event\subevents\EventsListener($plugin, $this);
	}
	
	
	
	
	
	
	
	public final function getEventFromFunctionName(string $event_function)
	{
		if(!isset(self::$regd_event_class[$event_function])) return false;
		else
		{
			return (self::$regd_event_class[$event_function]["object"] instanceof Event) ? self::$regd_event_class[$event_function]["event"] : \false;
		}
	}
	
	public final function registerEvent($object, Plugin $plugin, string $event_class, string $event_function, string $priority = "", bool $ignoreCancelled = false)
	{
		if(!$plugin->isEnabled()) return \false;
		if($object instanceof Listener)
		{
			$priority = ($priority != "") ? $priority : EP::NORMAL;
			self::$regd_event_class[$event_function] = ["plugin" => $plugin, "event" => $event_class, "object" => $object];
			
			try
			{
				$this->server->getPluginManager()->registerEvent($event_class, $object, $priority, new MEE($event_function), $plugin);
			}
			catch(\Throwable $e)
			{
				
				$reflection = new \ReflectionClass(get_class($object));
				foreach($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method)
				{
					if(!$method->isStatic() && $method->getDeclaringClass()->implementsInterface(Listener::class))
					{
						if($method->getName() !== $event_function) continue;
						$tags = \pocketmine\utils\Utils::parseDocComment((string) $method->getDocComment());
						if(isset($tags["notHandler"])) continue;
						// $parameters = $method->getParameters();
						// if(count($parameters) !== 1) continue;
						$handlerClosure = $method->getClosure($object);
						
						if(isset($tags["ignoreCancelled"]))
						{
							switch(strtolower($tags["ignoreCancelled"]))
							{
								case "true":
								case "":
									$ignoreCancelled = true;
								break;
								case "false":
									$ignoreCancelled = false;
								break;
								default:
									throw new PluginException("Event handler " . \pocketmine\utils\Utils::getNiceClosureName($handlerClosure) . "() declares invalid @ignoreCancelled value \"" . $tags["ignoreCancelled"] . "\"");
								break;
							}
						}
						
						$this->server->getPluginManager()->registerEvent($event_class, $handlerClosure, $priority, $plugin, $ignoreCancelled);
					}
				}
				
				// $this->server->getPluginManager()->registerEvent($event_class, function() use ($object) {$object;}, $priority, $this->plugin);
			}
			return \true;
		}
		else
		{
			throw new \Exception("§cClass §f\"§e".get_class($object)."§f\" §cmust §6instanceof §bListener§c.");
			return \false;
		}
	}
	
	public final function unregisterEvent(HandlerList $handlerList, Listener $object)
	{
		// \pocketmine\event\player\PlayerXXXXXXEvent::$handlerList;
		if(Main::getCurrentProtocol() <= 160)
		{
			if($handlerList !== null) $handlerList->unregister($object);
		}
		else
		{
			$h = $this->getHandlerListFor($handlerList);
			if($h !== null) $h->unregister($object);
		}
	}
	
	public final function unregisterEvents()
	{
		foreach(self::$regd_event_class as $event_function => $data)
		{
			if(\Teaclon\TSeriesAPI\Main::getCurrentProtocol() <= 160)
			{
				$event_class = $data["event"]::$handlerList;
				if($event_class !== null) $event_class->unregister($data["object"]);
			}
			else
			{
				$h = $this->getHandlerListFor($data["event"]);
				if($h !== null) $h->unregister($data["object"]);
			}
		}
	}
	
	
	public final function getEPLevel(string $level)
	{
		$level = strtolower($level);
		if(!in_array($level, ["lowest", "low", "normal", "high", "highest", "monitor"])) return EP::NORMAL;
		else
		{
			switch($level)
			{
				default:
					return EP::NORMAL;
				break;
				
				case "lowest":
					return EP::LOWEST;
				break;
				
				case "low":
					return EP::LOW;
				break;
				
				case "normal":
					return EP::NORMAL;
				break;
				
				case "high":
					return EP::HIGH;
				break;
				
				case "highest":
					return EP::HIGHEST;
				break;
				
				case "monitor":
					return EP::MONITOR;
				break;
			}
		}
	}
	
	public final function getHandlerListFor(string $event_name) : HandlerList
	{
		return HandlerList::getHandlerListFor($event_name);
	}
	
	public final function getDefaultEventClasses() : array
	{
		return $this->default_event_class_arr;
	}
	
	private function getEventListeners(string $event) : HandlerList
	{
		$list = HandlerList::getHandlerListFor($event);
		if($list === \null)
		{
			throw new \pocketmine\plugin\PluginException("Abstract events not declaring @allowHandle cannot be handled (tried to register listener for $event)");
			return \false;
		}
		return $list;
	}
	
	public final function getName() : string
	{
		return "事件管理模块";
	}
}
?>