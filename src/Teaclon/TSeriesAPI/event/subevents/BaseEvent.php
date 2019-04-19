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

use pocketmine\Player;
use pocketmine\event\EventPriority as EP;

/**
	本类主要作用为: 
		[] - 指令基础类
		[] - 权限组控制
**/



abstract class BaseEvent
{
	
	const LISTEN_HANDLER = EP::NORMAL;
	
	public $name, $logger;
	private $plugin = null;
	
	
	public abstract function __construct(\Teaclon\TSeriesAPI\Main $plugin);
	
	public abstract function getName() : string;
	
	// public abstract function getPrefix() : string;
	
	
	
	/* public function onEvent(\pocketmine\event\Event $e)
	{
		
	} */
	
	
	public final function getClassName() : string
	{
		return __CLASS__;
	}
}
?>