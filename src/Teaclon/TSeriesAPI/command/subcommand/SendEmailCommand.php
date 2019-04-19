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

use Teaclon\TSeriesAPI\command\subcommand\BaseCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;


final class SendEmailCommand extends BaseCommand
{
	const MY_COMMAND             = "email";
	const MY_COMMAND_PEREMISSION = [CommandManager::PERMISSION_HIGHEST];
	
	
	private $debug_mode;
	
	// private $receiver = "";
	// private $theme = "";
	// private $send_reason = "";
	// private $content = "";
	// private $use_format = null;
	
	private $control = \false;
	private $email_arr = [];
	private $email_number = 0;
	
	public function __construct($name, \Teaclon\Supervisor\Main $plugin)
	{
		if(!method_exists($plugin, "ssm")) exit("错误的插件源. 请勿尝试非法破解插件.".PHP_EOL);
		//	CommandName, Description, usage, aliases, overloads;
		$this->init($plugin, self::MY_COMMAND, "邮件发送", null, [], []);
		
		$this->debug_mode = $plugin->config()->get("email-debug-mode") ? "§aON" : "§cOFF";
	}
	
	public function execute(CommandSender $sender, $currentAlias, array $args)
	{
		$name = $sender->getName();
		
		if($sender instanceof Player)
		{
			$this->sendMessage($sender, $this->getLang("command.permission.insufficient"));
			return true;
		}
		
		if(!isset($args[0]))
		{
			$this->sendMessage($sender, "Usage: §d/§6".self::MY_COMMAND." §f<§ereceiver§f> <§etheme§f> <§esend reason§f> <§econtent§f> <§euse format§f>   To send a email");
			$this->sendMessage($sender, "Usage: §d/§6".self::MY_COMMAND." list           §fThere will have some already sent emails");
			$this->sendMessage($sender, "Usage: §d/§6".self::MY_COMMAND." cancel §f<§eid§f>    When you want to cancel to send email, then please use this command.");
			$this->sendMessage($sender, "Usage: §d/§6".self::MY_COMMAND." resend §f<§eid§f>    to resend a email with an id.");
			$this->sendMessage($sender, "Usage: §d/§6".self::MY_COMMAND." debug-mode           to change display/undisplay sendEmailMassage.");
			return true;
		}
		
		if($args[0] === "debug-mode" || $args[0] === "dm")
		{
			$this->plugin->set("email-debug-mode", $this->plugin->get("email-debug-mode") ? false : true);
			$this->plugin->save();
			$this->sendMessage($sender, "§aSaved.");
			return true;
		}
		
		if($args[0] === "resend")
		{
			if(!isset($args[1]))
			{
				$this->sendMessage($sender, "§cPlease input an id of Email list.");
				$this->sendMessage($sender, "§d/§6".self::MY_COMMAND." resend §f<§eid§f>   to resend a email with an id");
				return true;
			}
			
			$this->sendMessage($sender, "---------------------------------------");
			$this->sendMessage($sender, "§eDEBUG MODE: ".$this->email_arr[$args[1]]["receiver"]);
			$this->sendMessage($sender, "§eReceiver: §b".$this->email_arr[$args[1]]["theme"]);
			$this->sendMessage($sender, "§eTheme: §d".$this->email_arr[$args[1]]["theme"]);
			$this->sendMessage($sender, "§eSend reason: §c".$this->email_arr[$args[1]]["send_reason"]);
			$this->sendMessage($sender, "§eContent: §f".$this->email_arr[$args[1]]["content"]);
			$this->sendMessage($sender, "§eFormat: §f".(int) $this->email_arr[$args[1]]["use_format"]);
			$this->sendMessage($sender, "---------------------------------------");
			
			
			
			$status = $this->plugin->sendEmailTo
			([
				"receiver" => $this->email_arr[$args[1]]["receiver"],
				"theme" => $this->email_arr[$args[1]]["theme"],
				"content" => $this->email_arr[$args[1]]["content"],
				"send_reason" => $this->email_arr[$args[1]]["send_reason"],
				"use_format" => $this->email_arr[$args[1]]["use_format"],
				"sender" => $this->email_arr[$args[1]]["sender"]
			]);
			$status = ($status) ? $this->getLang("command.description.sendemail.successful") : $this->getLang("command.description.sendemail.failed");
			$this->sendMessage($sender, str_replace("{email_address}", $this->email_arr[$args[1]]["receiver"], $status));
			$this->email_arr[$args[1]]["sendTime"] = date("Y-m-d H:i:s");
			return true;
		}
		
		if($args[0] === "list")
		{
			if($this->control)
			{
				$this->sendMessage($sender, "§cAre you prepair to send a email? Now you cannot do other things!");
				return true;
			}
			if(count($this->email_arr) == 0)
			{
				$this->sendMessage($sender, "§eAt the moment haven't any Email already sent.");
				return true;
			}
			
			
			if(isset($args[1]) && ($args[1] === "save"))
			{
				if(!isset($args[2]))
				{
					$this->sendMessage($sender, "§cPlease input an id of Email list.");
					$this->sendMessage($sender, "§d/§6".self::MY_COMMAND." list save §f<§eid§f>   to save a email data");
					return true;
				}
				if($this->plugin->emailTemper("save", $args[2], $this->email_arr[$args[2]])) $this->sendMessage($sender, "§aSaved.");
				return true;
			}
			
			foreach($this->email_arr as $id => $info)
			{
				$this->sendMessage($sender, "§e===================================");
				$this->sendMessage($sender, "§eID: §6{$id}");
				$this->sendMessage($sender, "§eRecevier: §b".$info["receiver"]);
				$this->sendMessage($sender, "§eTheme: §f".$info["theme"]);
				$this->sendMessage($sender, "§eSend Reason: §f".$info["send_reason"]);
				$this->sendMessage($sender, "§eContent: §f".$info["content"]);
				$this->sendMessage($sender, "§eSend Time: §a".$info["sendTime"]);
				$this->sendMessage($sender, "§eUse Format: §f".(int) $info["use_format"]);
				$this->sendMessage($sender, "§e===================================");
			}
			$this->sendMessage($sender, "§d/§6".self::MY_COMMAND." list save §f<§eid§f>   to save a email data");
			return true;
		}
		if($args[0] === "cancel")
		{
			if($this->control)
			{
				if(!isset($args[1]))
				{
					$this->sendMessage($sender, $this->getLang("command.description.args.command-not-entered"));
					return true;
				}
				$this->sendMessage($sender, "§eAre you sure to cancel temp_id with §b§l{$args[1]}§r§e? §f(§aY§d/§cN§f)");
				if($this->t("N") === "Y")
				{
					unset($this->email_arr[$args[1]]);
					$this->sendMessage($sender, "§eunset temp_id with §6{$args[1]}§e.");
					$this->control = \false;
					return true;
				}
				else
				{
					$this->sendMessage($sender, "§eCancelled.");
					return true;
				}
			}
			else
			{
				$this->sendMessage($sender, "§cYou don't need do this operate.");
				return true;
			}
		}
		
		
		
		
		// 设置邮件的接收者;
		// if($this->receiver === "")
		if(!isset($this->email_arr[$this->email_number]))
		{
			if(!isset($args[0]))
			{
				$this->sendMessage($sender, $this->getLang("command.description.args.command-not-entered"));
				$this->sendMessage($sender, "§ePlease input receiver's email please.");
				return true;
			}
			
			if($this->plugin->getMyApi()->check_email_format($args[0]))
			{
				$this->email_arr[$this->email_number] = 
				[
					"receiver"    => "",
					"sender"      => "",
					"theme"       => "",
					"send_reason" => "",
					"content"     => "",
					"use_format"  => null,
					"sendTime"    => "",
				];
				$this->email_arr[$this->email_number]["receiver"] = $args[0];
				
				// $this->receiver = $args[0];
				$this->sendMessage($sender, "§aStart temp_id with §e{$this->email_number}");
				$this->sendMessage($sender, "§aSaved Recevier is §d".$this->email_arr[$this->email_number]["receiver"]);
				// $this->sendMessage($sender, "§aSaved Recevier is §d{$this->receiver}");
				$this->sendMessage($sender, "§eNow please input the §b§ltheme§r§e of this email.");
				$this->sendMessage($sender, "If you want have a space, please use \"*\" to stead.");
				$this->control = \true;
				unset($args);
				return true;
			}
			else
			{
				$this->sendMessage($sender, $this->getLang("command.description.sendemail.format-incorrect"));
				return true;
			}
		}
		
		
		// 设置邮件的主题;
		if($this->email_arr[$this->email_number]["theme"] === "")
		{
			if(!isset($args[0]))
			{
				$this->sendMessage($sender, $this->getLang("command.description.args.command-not-entered"));
				$this->sendMessage($sender, "§ePlease input the §b§ltheme§r§e of this email.");
				$this->sendMessage($sender, "If you want have a space, please use \"*\" to stead.");
				return true;
			}
			else
			{
				$this->email_arr[$this->email_number]["theme"] = $args[0];
				// $this->theme = $args[0];
				$this->sendMessage($sender, "§aSaved theme is §d".$this->email_arr[$this->email_number]["theme"]);
				// $this->sendMessage($sender, "§aSaved theme is §d{$this->theme}");
				$this->sendMessage($sender, "§eNow please input the §b§lSender Name§r§e of this email.");
				$this->sendMessage($sender, "If you want have a space, please use \"*\" to stead.");
				unset($args);
				return true;
			}
		}
		
		
		// 设置发送邮件的人;
		if($this->email_arr[$this->email_number]["sender"] === "")
		{
			if(!isset($args[0]))
			{
				$this->sendMessage($sender, $this->getLang("command.description.args.command-not-entered"));
				$this->sendMessage($sender, "§ePlease input the §b§lSender Name§r§e of this email.");
				$this->sendMessage($sender, "If you want have a space, please use \"*\" to stead.");
				return true;
			}
			else
			{
				$this->email_arr[$this->email_number]["sender"] = str_replace("*", " ", $args[0]);
				// $this->sender = str_replace("*", " ", $args[0]);
				$this->sendMessage($sender, "§aSaved send reason is §d".$this->email_arr[$this->email_number]["sender"]);
				// $this->sendMessage($sender, "§aSaved Sender Name is §d{$this->sender}");
				$this->sendMessage($sender, "§eNow please input the §b§lsend reason§r§e of this email.");
				$this->sendMessage($sender, "If you want have a space, please use \"*\" to stead.");
				unset($args);
				return true;
			}
		}
		
		
		// 设置发送邮件的原因;
		if($this->email_arr[$this->email_number]["send_reason"] === "")
		{
			if(!isset($args[0]))
			{
				$this->sendMessage($sender, $this->getLang("command.description.args.command-not-entered"));
				$this->sendMessage($sender, "§ePlease input the §b§lsend reason§r§e of this email.");
				$this->sendMessage($sender, "If you want have a space, please use \"*\" to stead.");
				return true;
			}
			else
			{
				$this->email_arr[$this->email_number]["send_reason"] = str_replace("*", " ", $args[0]);
				// $this->send_reason = str_replace("*", " ", $args[0]);
				$this->sendMessage($sender, "§aSaved send reason is §d".$this->email_arr[$this->email_number]["send_reason"]);
				// $this->sendMessage($sender, "§aSaved send reason is §d{$this->send_reason}");
				$this->sendMessage($sender, "§eNow please input the §b§lcontent§r§e of this email.");
				$this->sendMessage($sender, "If you want have a space, please use \"*\" to stead.");
				unset($args);
				return true;
			}
		}
		
		
		// 设置邮件的内容;
		if($this->email_arr[$this->email_number]["content"] === "")
		{
			if(!isset($args[0]))
			{
				$this->sendMessage($sender, $this->getLang("command.description.args.command-not-entered"));
				$this->sendMessage($sender, "§ePlease input the §b§lcontent§r§e of this email.");
				$this->sendMessage($sender, "If you want have a space, please use \"*\" to stead.");
				return true;
			}
			else
			{
				$this->email_arr[$this->email_number]["content"] = str_replace("*", " ", $args[0]);
				// $this->content = str_replace("*", " ", $args[0]);
				$this->sendMessage($sender, "§aSaved content is §d".$this->email_arr[$this->email_number]["content"]);
				// $this->sendMessage($sender, "§aSaved content is §d{$this->content}");
				$this->sendMessage($sender, "§eDo you want to use §b§lemail-format§r§e? §f(§aY§d/§cN§f)");
				$this->sendMessage($sender, "If you want have a space, please use \"*\" to stead.");
				unset($args);
				
				
				// 询问是否使用邮件模板;
				if($this->t("N") === "Y")
				{
					$this->email_arr[$this->email_number]["use_format"] = true;
					// $this->use_format = true;
					$this->sendMessage($sender, "§aOk, then i will send this email with email-format.");
				}
				else
				{
					$this->email_arr[$this->email_number]["use_format"] = false;
					// $this->use_format = false;
					$this->sendMessage($sender, "§aOk, then i will send this email without email-format.");
				}
				$this->sendMessage($sender, "---------------------------------------");
				$this->sendMessage($sender, "§eDEBUG MODE: ".$this->email_arr[$this->email_number]["receiver"]);
				$this->sendMessage($sender, "§eReceiver: §b".$this->email_arr[$this->email_number]["theme"]);
				$this->sendMessage($sender, "§eTheme: §d".$this->email_arr[$this->email_number]["theme"]);
				$this->sendMessage($sender, "§eSend reason: §c".$this->email_arr[$this->email_number]["send_reason"]);
				$this->sendMessage($sender, "§eContent: §f".$this->email_arr[$this->email_number]["content"]);
				$this->sendMessage($sender, "§eFormat: §f".(int) $this->email_arr[$this->email_number]["use_format"]);
				$this->sendMessage($sender, "---------------------------------------");
				
				
				// $this->sendMessage($sender, "---------------------------------------");
				// $this->sendMessage($sender, "§eDEBUG MODE: ".$this->debug_mode);
				// $this->sendMessage($sender, "§eReceiver: §b".$this->receiver);
				// $this->sendMessage($sender, "§eTheme: §d".$this->theme);
				// $this->sendMessage($sender, "§eSend reason: §c".$this->send_reason);
				// $this->sendMessage($sender, "§eContent: §f".$this->content);
				// $this->sendMessage($sender, "§eFormat: §f".(int) $this->use_format);
				// $this->sendMessage($sender, "---------------------------------------");
				
				
				
				$status = $this->plugin->sendEmailTo
				([
					"receiver" => $this->email_arr[$this->email_number]["receiver"],
					"theme" => $this->email_arr[$this->email_number]["theme"],
					"content" => $this->email_arr[$this->email_number]["content"],
					"send_reason" => $this->email_arr[$this->email_number]["send_reason"],
					"use_format" => $this->email_arr[$this->email_number]["use_format"],
					"sender" => $this->email_arr[$this->email_number]["sender"]
				]);
				// $status = $this->plugin->sendEmailTo($this->receiver, $this->theme, $this->content, $this->send_reason, $this->use_format);
				// $this->sendMessage($sender, str_replace("{email_address}", $this->receiver, $status));
				$status = ($status) ? $this->getLang("command.description.sendemail.successful") : $this->getLang("command.description.sendemail.failed");
				$this->sendMessage($sender, str_replace("{email_address}", $this->email_arr[$this->email_number]["receiver"], $status));
				$this->email_arr[$this->email_number]["sendTime"] = date("Y-m-d H:i:s");
				$this->control = false;
				$this->email_number++;
				return true;
			}
		}
	}
	
	
	
	
	
	
	
	
	
	
	public function t($default = "")
	{
		return $this->plugin->getMyApi()->getInput($default);
	}
	
	
	
	public final static function Helper() : array
	{
		return 
		[
			"command" => $this->name,
			"description" => "Send Email.",
			"permission" => PERM::PERMISSION_CONSOLE
		];
	}
	
	
	
}
?>