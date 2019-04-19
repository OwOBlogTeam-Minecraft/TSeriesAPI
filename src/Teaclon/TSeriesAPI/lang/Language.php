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

namespace Teaclon\TSeriesAPI\lang;

use Teaclon\TSeriesAPI\Main;

use pocketmine\Server;
use pocketmine\plugin\Plugin;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as C;
/**
	本类主要作用为: 
		[√] - 语言翻译包
		[√] - 插件指令转换
**/



final class Language
{
	/* 
	const CHS    = "chs";
	const ENG    = "eng";
	const ENG_US = "eng_us";
	const DEU    = "deu";
	
	public $lang_marked =      // 语言标识;
	[
		"zh_CN" => self::CHS,
		"en_GB" => self::ENG,
		"en_US" => self::ENG,
		"de_DE" => self::DEU,
	];
	 */
	
	private static $lang_pack_info = [];
	
	private $lang = null;
	private $plugin = null;
	private $server = null;
	private $logger = null;
	
	private $lang_pack   = [];
	private $nestedCache = []; // 已搜寻的翻译缓存;
	private $need_params = ["lang_v", "lang", "author", "plugin_name", "welcome_msg", "packs"];
	
	
	
	public function __construct($lang, Plugin $plugin)
	{
		$this->plugin = $plugin;
		$this->server = Server::getInstance();
		$this->logger = Server::getInstance()->getLogger();
		
		if(!$this->checkLangPack($lang))
		{
			$this->logger->error(Main::PLUGIN_PREFIX.C::RED."Invalid language pack from plugin §e".$plugin->getName()."");
			return \null;
		}
		else
		{
			$this->logger->info(Main::PLUGIN_PREFIX."§eInit language pack version §a".$this->getLangPackInfo("lang_v")."§e by author §b".$this->getLangPackInfo("author")."");
			$this->logger->info(Main::PLUGIN_PREFIX."§eThis language pack from plugin §a".$this->getLangPackInfo("plugin_name")."");
			$this->logger->info(Main::PLUGIN_PREFIX."§eWelcome message: §f".$this->getLangPackInfo("welcome_msg")."");
			return \null;
		}
	}
	
	
	
	
	public function getLang(string $msg) : string // 语言条;
	{
		$vars = explode(".", $msg); // 多层语言包解析;
		
		if(isset($this->nestedCache[$msg])) return $this->nestedCache[$msg]; // 如果存在该翻译的翻译缓存, 直接返回缓存值;
		if(isset($this->lang_pack[$msg]))
		{
			if(!isset($this->nestedCache[$msg])) $this->nestedCache[$msg] = $this->lang_pack[$msg];
			return $this->nestedCache[$msg];
		}
		elseif(count($vars) > 1)
		{
			$base = \array_shift($vars);
			if(isset($this->lang_pack[$base])) $base = $this->lang_pack[$base];
			else return $this->onMessageNotFound($msg);
			
			while(count($vars) > 0)
			{
				$baseKey = \array_shift($vars);
				if(\is_array($base) && isset($base[$baseKey])) $base = $base[$baseKey];
				else return $this->onMessageNotFound($msg);
			}
			
			return $this->nestedCache[$msg] = $base;
			// return $this->nestedCache[$msg] = ($this->GetBrowser()) ? C::toHTML($base) : $base;
		}
		else return $this->onMessageNotFound($msg);
	}
	
	
	
	
	
	
	
	private function checkLangPack($lang)
	{
		if((!$lang instanceof Config) && (!is_array($lang)))
		{
			$this->logger->error(Main::PLUGIN_PREFIX.C::RED."Invalid language pack");
			return \false;
		}
		else
		{
			$status = \null;
			if($lang instanceof Config)
			{
				foreach($this->need_params as $index)
				{
					if(!$lang->exists($index))
					{
						$status = \false;
						$this->logger->error(Main::PLUGIN_PREFIX.C::RED."Language pack config missing a param \"{$index}\"");
						break;
						return \false;
					}
				}
				if(($status === \null) && ($status !== false))
				{
					$this->setLangPack($lang->getAll());
					return \true;
				}
			}
			elseif(is_array($lang))
			{
				foreach($this->need_params as $index)
				{
					if(!isset($lang[$index]))
					{
						$status = \false;
						$this->logger->error(Main::PLUGIN_PREFIX.C::RED."Language pack config missing a param \"{$index}\"");
						break;
						return \false;
					}
				}
				
				if(($status === \null) && ($status !== false))
				{
					$this->setLangPack($lang);
					return \true;
				}
			}
		}
	}
	
	private function setLangPack(array $lang)
	{
		foreach($this->need_params as $index)
		{
			if($index === "packs") continue;
			self::$lang_pack_info[$index] = $lang[$index];
			unset($lang[$index]);
		}
		$this->lang_pack = $lang["packs"];
		return \true;
	}
	
	
	private function onMessageNotFound(string $msg) : string
	{
		return C::RED."Message ".C::WHITE."\"".C::YELLOW."{$msg}".C::WHITE."\" ".C::RED."not found";
	}
	
	private function onLanguageNotFound(string $lang, string $msg) : string
	{
		return C::RED."Language ".C::WHITE."\"".C::YELLOW."{$lang}".C::WHITE."\" ".C::RED."not found ".C::WHITE."(".C::YELLOW."{$msg}".C::WHITE.")";
	}
	
	public static function getLangPackInfo($param = "")
	{
		return ($param === "") ? self::$lang_pack_info : self::$lang_pack_info[$param];
	}
	
	public function clearNestedCache()
	{
		$this->nestedCache = [];
		return \true;
	}
	
	
	public function c($c)
	{
		return "\xc2\xa7".$c;
	}
}
?>