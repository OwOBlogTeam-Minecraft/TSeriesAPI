<?php

namespace Teaclon\TSeriesAPI\plugin;

use pocketmine\event\plugin\PluginDisableEvent;
use pocketmine\event\plugin\PluginEnableEvent;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginDescription;
use pocketmine\plugin\PluginLoader;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;

$match1 = (bool) preg_match_all('/Return \[ (.*) \]/', \ReflectionMethod::export('pocketmine\plugin\PluginLoader', "loadPlugin", \true), $matches1);
$match2 = (bool) preg_match_all('/Return \[ (.*) \]/', \ReflectionMethod::export('pocketmine\plugin\PluginLoader', "getPluginDescription", \true), $matches2);

if(($match1 === \true) && isset($matches1[1][0]) || ($match2 === \true) && isset($matches2[1][0]))
{
	$matches1 = str_replace(" ", "", $matches1[1][0]);
	$matches2 = str_replace(" ", "", $matches2[1][0]);
	if(($matches1 === "void"))
	{
		if(($matches2 === "pocketmine\plugin\PluginDescriptionorNULL") || ($matches2 === "pocketmine\plugin\PluginDescription"))
		{
			class FolderPluginLoader implements PluginLoader
			{
				private $loader;
				public function __construct(\ClassLoader $loader){$this->loader = $loader;}

				public function loadPlugin(string $file) : void{$this->loader->addPath("$file/src");}
				public function canLoadPlugin(string $path) : bool{return is_dir($path) and file_exists($path . "/plugin.yml") and file_exists($path . "/src/");}
				public function getPluginDescription(string $file) : ?PluginDescription
				{
					if(is_dir($file) and file_exists($file . "/plugin.yml"))
					{
						$yaml = @file_get_contents($file . "/plugin.yml");
						if($yaml != "")
						{
							return new PluginDescription($yaml);
						}
					}
					return null;
				}
				public function getAccessProtocol() : string{return "";}
			}
		}
		else
		{
			class FolderPluginLoader implements PluginLoader
			{
				private $loader;
				public function __construct(\ClassLoader $loader){$this->loader = $loader;}

				public function loadPlugin(string $file) : void{$this->loader->addPath("$file/src");}
				public function canLoadPlugin(string $path) : bool{return is_dir($path) and file_exists($path . "/plugin.yml") and file_exists($path . "/src/");}
				public function getPluginDescription(string $file)
				{
					if(is_dir($file) and file_exists($file . "/plugin.yml"))
					{
						$yaml = @file_get_contents($file . "/plugin.yml");
						if($yaml != "")
						{
							return new PluginDescription($yaml);
						}
					}
					return null;
				}
				public function getAccessProtocol() : string{return "";}
			}
		}
		
	}
	else
	{
		exit("Unsupport Type \"{$matches1}\" in class \"pocketmine\\plugin\\PluginLoader\", please contact with developer Teaclon to fix this issue.");
	}
}
else
{
	class FolderPluginLoader implements PluginLoader
	{
		private $server;
		public function __construct(Server $server){$this->server = $server;}
		public function loadPlugin($file)
		{
			if(is_dir($file) and file_exists($file . "/plugin.yml") and file_exists($file . "/src/"))
			{
				if(($description = $this->getPluginDescription($file)) instanceof PluginDescription)
				{
					$logger = $this->server->getLogger();
					$logger->info(TextFormat::LIGHT_PURPLE . "Loading source plugin " . $description->getFullName());
					$dataFolder = dirname($file) . DIRECTORY_SEPARATOR . $description->getName();
					if(file_exists($dataFolder) and !is_dir($dataFolder))
					{
						$logger->warning("Projected dataFolder '" . $dataFolder . "' for source plugin " . $description->getName() . " exists and is not a directory");
						return null;
					}
					$className = $description->getMain();
					$this->server->getLoader()->addPath($file . "/src");
					if(class_exists($className, true))
					{
						$plugin = new $className();
						$this->initPlugin($plugin, $description, $dataFolder, $file);
						return $plugin;
					}
					else
					{
						$logger->warning("Couldn't load source plugin " . $description->getName() . ": main class not found");
						return null;
					}
				}
			}
			return null;
		}
		public function getPluginDescription($file)
		{
			if(is_dir($file) and file_exists($file . DIRECTORY_SEPARATOR . "plugin.yml"))
			{
				$yaml = @file_get_contents($file . DIRECTORY_SEPARATOR . "plugin.yml");
				if($yaml !== "") return new PluginDescription($yaml);
			}
			return null;
		}
		private function initPlugin(PluginBase $plugin, PluginDescription $description, $dataFolder, $file){$plugin->init($this, $this->server, $description, $dataFolder, $file);$plugin->onLoad();}
		public function enablePlugin(Plugin $plugin){if($plugin instanceof PluginBase and !$plugin->isEnabled()){MainLogger::getLogger()->info("Enabling " . $plugin->getDescription()->getFullName());$plugin->setEnabled(true);Server::getInstance()->getPluginManager()->callEvent(new PluginEnableEvent($plugin));}}
		public function disablePlugin(Plugin $plugin){if($plugin instanceof PluginBase and $plugin->isEnabled()){MainLogger::getLogger()->info("Disabling " . $plugin->getDescription()->getFullName());Server::getInstance()->getPluginManager()->callEvent(new PluginDisableEvent($plugin));$plugin->setEnabled(false);}}
		public function getPluginFilters() : string{return "/[^\\.]/";}
		public function canLoadPlugin(string $path) : bool{return is_dir($path);}
		public function getAccessProtocol(){return "";}
	}
}



?>