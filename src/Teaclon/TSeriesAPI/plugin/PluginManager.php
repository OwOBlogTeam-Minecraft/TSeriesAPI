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

namespace Teaclon\TSeriesAPI\plugin;

use pocketmine\Server;
use pocketmine\plugin\PluginLoadOrder;

use Teaclon\TSeriesAPI\Main;
use Teaclon\TSeriesAPI\plugin\FolderPluginLoader;

final class PluginManager
{
	const MY_PREFIX = "§bPluginManager §f> ";
	
	
	
	private $pluginManager;
	private $plugin = null;
	private static $instance = null;
	private static $interface_src_plugins = [];
	
	
	public function __construct(\Teaclon\TSeriesAPI\Main $plugin)
	{
		if(!method_exists($plugin, "ssm")) exit("错误的插件源. 请勿尝试非法破解插件.".PHP_EOL);
		self::$instance = $this;
		$this->plugin   = $plugin;
		$this->pluginManager = $plugin->getServer()->getPluginManager();
		$plugin->ssm(Main::PLUGIN_PREFIX.self::MY_PREFIX."PluginManager loaded.", "info", "server");
		
		if(!@\class_exists('\pocketmine\plugin\FolderPluginLoader', \false))
		{
			if(($this->plugin->getServer()->getName() === "PocketMine-MP") && (version_compare($plugin->getServer()->getAPIVersion(), "3.0.0-ALPHA12") == 0))
			{
				$plugin->getServer()->getPluginManager()->registerInterface(FolderPluginLoader::class);
				$plugin->getServer()->getPluginManager()->loadPlugins($plugin->getServer()->getPluginPath(), [FolderPluginLoader::class]);
				$plugin->getServer()->enablePlugins(PluginLoadOrder::STARTUP);
			}
			else
			{
				$plugin->getServer()->getPluginManager()->registerInterface(new FolderPluginLoader($plugin->getServer()->getLoader()));
				$plugin->getServer()->getPluginManager()->loadPlugins($plugin->getServer()->getPluginPath(), [FolderPluginLoader::class]);
				$plugin->getServer()->enablePlugins(PluginLoadOrder::STARTUP);
			}
		}
	}
	
	
	
	
	
	
	
	
	
	
	public final function makePlugin(string $plugin_name)
	{
		$plugin_name = trim($plugin_name);
		if(($plugin_name === "") || !(($plugin = Server::getInstance()->getPluginManager()->getPlugin($plugin_name)) instanceof \pocketmine\plugin\Plugin))
		{
			$this->plugin->ssm(Main::PLUGIN_PREFIX.self::MY_PREFIX."§c插件不存在.", "info", "server");
			return \false;
		}
		$description = $plugin->getDescription();
		
		if(!$plugin->getPluginLoader() instanceof \Teaclon\TSeriesAPI\plugin\FolderPluginLoader)
		{
			$this->plugin->ssm(Main::PLUGIN_PREFIX.self::MY_PREFIX."§c这不是一个有效的未打包的插件.", "info", "server");
			return \false;
		}
		
		$pharPath = $this->plugin->getDataFolder() . $description->getName() . "_v" . $description->getVersion() . ".phar";
		
		$metadata = 
		[
			"name"         => $description->getName(),
			"version"      => $description->getVersion(),
			"main"         => $description->getMain(),
			"api"          => $description->getCompatibleApis(),
			"depend"       => $description->getDepend(),
			"description"  => $description->getDescription(),
			"authors"      => $description->getAuthors(),
			"website"      => $description->getWebsite(),
			"creationDate" => time()
		];

		$stub = ($description->getName() === "DevTools")
			? '<?php require("phar://". __FILE__ ."/src/Teaclon/TSeriesAPI/plugin/ConsoleScript.php"); __HALT_COMPILER();'
			: '<?php echo "PocketMine-MP plugin ' . $description->getName() . ' v' . $description->getVersion() . '\nThis file has been generated using TSeriesAPI v' . $this->plugin->getDescription()->getVersion() . ' at ' . date("r") . '\n----------------\n";if(extension_loaded("phar")){$phar = new \Phar(__FILE__);foreach($phar->getMetadata() as $key => $value){echo ucfirst($key).": ".(is_array($value) ? implode(", ", $value):$value)."\n";}} __HALT_COMPILER();';
		
		$reflection = new \ReflectionClass("pocketmine\\plugin\\PluginBase");
		$file = $reflection->getProperty("file");
		$file->setAccessible(\true);
		$filePath = realpath($file->getValue($plugin));
		assert(is_string($filePath));
		$filePath = rtrim($filePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		
		$this->buildPhar($pharPath, $filePath, [], $metadata, $stub, \Phar::SHA1);
		
		$this->plugin->ssm(Main::PLUGIN_PREFIX.self::MY_PREFIX."§a插件 §f\"§e{$description->getName()}§f_v§d{$description->getVersion()}§f\" §a打包成功. 路径: §6".$pharPath, "info", "server");
		unset($pharPath, $metadata, $stub, $reflection, $file, $filePath);
		return \true;
	}
	
	
	public final function extractPlugin(string $plugin_name)
	{
		$plugin_name = trim($plugin_name);
		if(($plugin_name === "") || !(($plugin = Server::getInstance()->getPluginManager()->getPlugin($plugin_name)) instanceof \pocketmine\plugin\Plugin))
		{
			$this->plugin->ssm(Main::PLUGIN_PREFIX.self::MY_PREFIX."§c插件不存在.", "info", "server");
			return \false;
		}
		$description = $plugin->getDescription();
		
		if(!$plugin->getPluginLoader() instanceof \pocketmine\plugin\PharPluginLoader)
		{
			$this->plugin->ssm(Main::PLUGIN_PREFIX.self::MY_PREFIX."§c这不是一个有效的已打包的插件.", "info", "server");
			return \false;
		}
		
		$folderPath = $this->plugin->getDataFolder() . DIRECTORY_SEPARATOR . $description->getName() . "_v" . $description->getVersion() . "/";
		(file_exists($folderPath)) ? $this->plugin->ssm(Main::PLUGIN_PREFIX.self::MY_PREFIX."§e覆盖原先文件解压插件中...", "info", "server") : @mkdir($folderPath, 0777, \true);
		
		$reflection = new \ReflectionClass("pocketmine\\plugin\\PluginBase");
		$file = $reflection->getProperty("file");
		$file->setAccessible(\true);
		$pharPath = str_replace("\\", "/", rtrim($file->getValue($plugin), "\\/"));
		
		foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pharPath)) as $fInfo)
		{
			$path = $fInfo->getPathname();
			@mkdir(dirname($folderPath . str_replace($pharPath, "", $path)), 0755, \true);
			file_put_contents($folderPath . str_replace($pharPath, "", $path), file_get_contents($path));
		}
		$this->plugin->ssm(Main::PLUGIN_PREFIX.self::MY_PREFIX."§a已将插件 §f\"§e{$description->getName()}§f_v§d{$description->getVersion()}§f\" §a的源代码解压至文件夹 §f\"§e".$folderPath."§f\" §a内.", "info", "server");
		return \true;
	}
	
	
	
	
	
	private function preg_quote_array(array $strings, string $delim = null) : array
	{
		return array_map(function(string $str) use ($delim) : string { return preg_quote($str, $delim); }, $strings);
	}
	
	private function buildPhar(string $pharPath, string $basePath, array $includedPaths, array $metadata, string $stub, int $signatureAlgo = \Phar::SHA1)
	{
		if(file_exists($pharPath))
		{
			$this->plugin->ssm(Main::PLUGIN_PREFIX.self::MY_PREFIX."§e覆盖原先文件打包插件中...", "info", "server");
			try
			{
				\Phar::unlinkArchive($pharPath);
			}
			catch(\PharException $e)
			{
				//unlinkArchive() doesn't like dodgy phars
				unlink($pharPath);
			}
		}
		
		$this->plugin->ssm(Main::PLUGIN_PREFIX.self::MY_PREFIX."§e添加文件中...", "info", "server");
		
		$start = microtime(true);
		$phar = new \Phar($pharPath);
		$phar->setMetadata($metadata);
		$phar->setStub($stub);
		$phar->setSignatureAlgorithm($signatureAlgo);
		$phar->startBuffering();
		
		//If paths contain any of these, they will be excluded
		$excludedSubstrings = 
		[
			DIRECTORY_SEPARATOR . ".", //"Hidden" files, git information etc
			realpath($pharPath) //don't add the phar to itself
		];
		
		$regex = sprintf('/^(?!.*(%s))^%s(%s).*/i',
			implode('|', $this->preg_quote_array($excludedSubstrings, '/')), //String may not contain any of these substrings
			preg_quote($basePath, '/'), //String must start with this path...
			implode('|', $this->preg_quote_array($includedPaths, '/')) //... and must be followed by one of these relative paths, if any were specified. If none, this will produce a null capturing group which will allow anything.
		);
		
		$directory = new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS | \FilesystemIterator::CURRENT_AS_PATHNAME); //can't use fileinfo because of symlinks
		$iterator = new \RecursiveIteratorIterator($directory);
		$regexIterator = new \RegexIterator($iterator, $regex);
		
		$count = count($phar->buildFromIterator($regexIterator, $basePath));
		$this->plugin->ssm(Main::PLUGIN_PREFIX.self::MY_PREFIX."§a已添加 §6{$count} §a个文件", "info", "server");
		
		$this->plugin->ssm(Main::PLUGIN_PREFIX.self::MY_PREFIX."§e检查并压缩文件中...", "info", "server");
		foreach($phar as $file => $finfo)
		{
			/** @var \PharFileInfo $finfo */
			if($finfo->getSize() > (1024 * 512))
			{
				$this->plugin->ssm(Main::PLUGIN_PREFIX.self::MY_PREFIX."§b正在压缩文件 §f" . $finfo->getFilename(), "info", "server");
				$finfo->compress(\Phar::GZ);
			}
		}
		$phar->stopBuffering();
		
		$this->plugin->ssm(Main::PLUGIN_PREFIX.self::MY_PREFIX."§a结束任务. 最终耗时 §6".round(microtime(true) - $start, 3)." §as", "info", "server");
		unset($phar);
	}
	
	
	public static final function getInstance()
	{
		return self::$instance;
	}
}
?>