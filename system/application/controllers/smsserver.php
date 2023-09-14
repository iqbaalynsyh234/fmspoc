<?php
include "base.php";

class SMSServer extends Base {
	var $configs;
	var $debug;

	function SMSServer()
	{
		parent::Base();	
		
		$this->debug = false;
		
		$this->load->model("configmodel");
		$this->load->model("smsmodel");
		$this->load->model("gpsmodel");		
		$this->load->model("agenmodel");
		$this->load->model("usermodel");
		
		$this->load->helper('file');
		$this->load->helper('email');
		
		$configs = $this->configmodel->get();
		$this->params['configs'] = $configs;
	}
	
	function inbox($inboxid=0, $notread=1)
	{			
		$smsdb = $this->load->database("sms", TRUE);
		
		$smsdb->where("config_name", "lastrecvnotice");
		$q = $smsdb->get("config");
	
		if ($q->num_rows() == 0)
		{
			$mail['subject'] = sprintf("recv sms");
			$mail['message'] = sprintf("hello, i fine and ready to process sms inbox, thanks. i will report next 2 hour, wassalam.\r\n");
			$mail['dest'] = "prastgtx@gmail.com,owner@adilahsoft.com"; 
		
			lacakmobilmail($mail);					
			
			unset($insert);
			
			$insert['config_name'] = "lastrecvnotice";
			$insert['config_value'] = mktime();
			
			$smsdb->insert("config", $insert);
		}
		else
		{
			$row = $q->row();
			$dt = mktime()-$row->config_value;
			$jam2 = 2*60*60;
			
			if ($dt > $jam2)
			{
				unset($update);
				
				$update['config_value'] = mktime();
				
				$smsdb->where("config_name", "lastrecvnotice");
				$smsdb->update("config", $update);

				$mail['subject'] = sprintf("recv sms");
				$mail['message'] = sprintf("hello, i fine and ready to process sms inbox, thanks. i will report next 2 hour, wassalam.\r\n");
				$mail['dest'] = "prastgtx@gmail.com,owner@adilahsoft.com"; 
			
				lacakmobilmail($mail);				
			}
		}

		unset($update);
		$update['config_value'] = date("Y-m-d H:i:s");
		
		$smsdb->where("config_name", "lastrecvrun");
		$smsdb->update("config", $update);				

		$smsdb->where("config_name", "inboxprocessing");
		$q = $smsdb->get("config");
		if ($q->num_rows() == 0)
		{
			unset($insert);
			
			$insert['config_name'] = 'inboxprocessing';
			$insert['config_value'] = 1;
			
			$smsdb->insert("config", $insert);
		}
		else
		{
			$rowinboxprocessing = $q->row();
			if (! $inboxid)
			{
				if ($rowinboxprocessing->config_value == 1) 
				return;
			}
		}
		
		unset($update);
		$update['config_value'] = 1;
		
		$smsdb->where("config_name", "inboxprocessing");
		$smsdb->update("config", $update);						
		
		if ($inboxid)
		{
				$smsdb->where("ID", $inboxid);
		}
		$smsdb->order_by("ReceivingDateTime", "asc");
		$smsdb->where("((UDH = '') OR (UDH LIKE '%01'))");
		
		if ($notread)
		{
			$smsdb->where("Processed", 'false');
		}
		$q = $smsdb->get("inbox");
		
		$rows = $q->result();
		
		$smsdb->where("pending_time <=", date("Y-m-d H:i:s"));
		$smsdb->where("pending_status", 1);
		$q = $smsdb->get("pending");
		$rowspending = $q->result();
		
		for($i=0; $i < count($rowspending); $i++)
		{
			unset($myinbox);
			
			$myinbox->pending_id = $rowspending[$i]->pending_id;
			$myinbox->ID = 0;
			$myinbox->ReceivingDateTime = date("Y-m-d H:i:s");
			$myinbox->UDH = '';
			$myinbox->TextDecoded = $rowspending[$i]->pending_inbox;
			$myinbox->SenderNumber = $rowspending[$i]->pending_sender;
			
			$rows[] = $myinbox;
		}
		
		if (count($rows) == 0)
		{
			printf("[%s] inbox not found\n", date("Ymd H:i:s"));
			
			unset($update);
			$update['config_value'] = 0;
			
			$smsdb->where("config_name", "inboxprocessing");
			$smsdb->update("config", $update);
			
			$smsdb->where("config_name", "lastwarninginbox");
			$q = $smsdb->get("config");
			
			if ($q->num_rows() == 0)
			{
				unset($insert);
				
				$insert['config_name'] = "lastwarninginbox";
				$insert['config_value'] = mktime();
				
				$smsdb->insert("config", $insert);
				
				$lastwarninginbox = 0;
			}
			else
			{
				$row = $q->row();
				$lastwarninginbox = $row->config_value;
			}
			
			if (($lastwarninginbox+1800) < mktime())
			{
				$smsdb->flush_cache();
				$smsdb->limit(1);
				$smsdb->order_by("UpdatedInDB", "DESC");
				$q = $smsdb->get("inbox");

				if ($q->num_rows() == 0) return;				
				unset($row);
				$row = $q->row();
					
				$t = dbmaketime($row->UpdatedInDB);				
				$now = mktime();
				
				if (($t+5*3600) < $now)
				{
					$mail['format'] = "html";
					$mail['subject'] = sprintf("sms server tidak terima sms");
					$mail['message'] = sprintf("<h1><font color='#ff0000'>WARNING!!! SMS Server tidak menerima sms sejak %s</font></h1>, wassalam.", date("d/m/Y H:i:s", $t));
					$mail['dest'] = "prastgtx@gmail.com,owner@adilahsoft.com"; 
					
					//lacakmobilmail($mail);
				}

				unset($update);
				$update['config_value'] = mktime();
				
				$smsdb->where("config_name", "lastwarninginbox");
				$smsdb->update("config", $update);

			}						
			
			return;
		}
				
		for($i=0; $i < count($rows); $i++)
		{
			$udh = trim($rows[$i]->UDH);
			if (strlen($udh) == 0) continue;
						
			$header = substr($udh, 0, strlen($udh)-4);			
			$jml = substr($udh, strlen($udh)-4, 2)*1;
			
			if (! is_numeric($jml)) continue;
			if ($jml < 0) continue;
			
			for($j=2; $j <= $jml; $j++)
			{
				for($k=0; $k < 3; $k++)
				{
					$smsdb->where("UDH", sprintf("%s%02d%02d", $header, $jml, $j));
					$q = $smsdb->get("inbox");
					
					if ($q->num_rows() == 0)
					{
						sleep(2);
						continue;
					}
					
					$rowadd = $q->row();
					$rows[$i]->TextDecoded .= $rowadd->TextDecoded;
					break;
				}
			}
		}
		
		for($i=0; $i < count($rows); $i++)
		{
			$recv = dbmaketime($rows[$i]->ReceivingDateTime);
			$sender = $rows[$i]->SenderNumber;
			$textdecoded = $rows[$i]->TextDecoded;
			$content = trim($rows[$i]->TextDecoded)." ";
			
			printf("[%s] %s %s %s\n", date("Ymd H:i:s"), date("Ymd_His", $recv), $sender, $content);
			
			list($command, $content) = explode(" ", $content, 2);
			
			$listcommands = array("PSSSEMUA", "POSISI", "PSS", "KM", "PARK", "AKTIVASIUSER", "AKTIVASIMOBIL", "NOTIFIKASI", "GEOFENCE", "BROADCAST", "BROADCASTAGENT", "CUT POWER ALERT", "MATIKAN", "NYALAKAN", "NOT BE AUTHORIZED!", "YOU AREN'T AUTHRIZED", "SUPPLY ELECTRICITY OK", "STOP ELECTRICITY OK", "AKTIVASIKENDARAAN", "REPLY", "AGEN", "BAYAR", "LUNAS", "SET APN OK", "SET PROTOCOL OK", "RESTART DEVICE IN", "SET OUTPUT OK", "RESETPASS", "APN:INDOSATGPRS", "RESET DEVICE IN 15 MINS", "DEVICE IS NO ADMIN STATE", "OTOMATISKIRIM", "APN:TELKOMSEL", "ISIPULSA", "INFO", "SET APN: INDOSATGPRS OK", "PULSA UTAMA", "PULSAUTAMA", "SISA PULSA ANDA", "STOP ENGINE SUCCEED", "RESUME ENGINE SUCCEED", "LOCK", "UNLOCK", "MAIN POWER OFF", "AKUN", "RESTART SUCCESS!", "AUTO TRACK SET OK", "SET PROVIDER OK", "BALANCE", "REG", "UNREG");
			$ucasecommand = strtoupper($command);
			
			if (! in_array($ucasecommand, $listcommands))
			{
				$found = false;
				foreach($listcommands as $val)
				{
					if (strcasecmp($val, substr($textdecoded, 0, strlen($val))) != 0) continue;
					
					$command = $val;
					$content = substr($textdecoded, strlen($val));
					$found = true;
					break;
				}
				
				if (! $found)
				{
					$pos = strpos(strtoupper($textdecoded), "CUT POWER ALERT!");
					if ($pos !== FALSE)
					{
						$command = "CUT POWER ALERT";
						$content = $textdecoded;
					}

					$pos = strpos(strtoupper($textdecoded), "MAIN POWER OFF");
					if ($pos !== FALSE)
					{
						$command = "CUT POWER ALERT";
						$content = $textdecoded;
					}					

					$pos = strpos(strtoupper($textdecoded), "STOP ENGINE SUCCEED");
					if ($pos !== FALSE)
					{
						$command = "STOP ELECTRICITY OK";
						$content = $textdecoded;
					}

					$pos = strpos(strtoupper($textdecoded), "RESUME ENGINE SUCCEED");
					if ($pos !== FALSE)
					{
						$command = "SUPPLY ELECTRICITY OK";
						$content = $textdecoded;
					}
					
					
				}
			}
			
			switch(strtoupper($command))
			{
				case "POSISI":
				case "PSS":
					$this->posisihandler($sender, trim($content), isset($rows[$i]->pending_id));
				break;
				case "PSSSEMUA":
					$this->posisisemuahandler($sender, trim($content));
				break;
				case "KM":
					$this->settingkmhandler($sender, trim($content));
				break;
				case "PARK": 
					$this->settingparkinghandler($sender, trim($content));
				break;
				case "AKTIVASIUSER": 
					$this->settingactivateperuserhandler($sender, trim($content));
				break;	
				case "AKTIVASIMOBIL":
				case "AKTIVASIKENDARAAN":
					$this->settingactivatepervehiclehandler($sender, trim($content));
				break;
				case "NOTIFIKASI":
					$this->settingnotifyhandler($sender, trim($content));
				break;
				case "GEOFENCE":					
					$this->settinggeofencehandler($sender, trim($content));
				break;
				case "BROADCASTAGENT":
					//$this->broadcasthandler($sender, trim($content), 3);
				break;
				case "BROADCAST":
					//$this->broadcasthandler($sender, trim($content), 0);					
				break;
				case "CUT POWER ALERT":
					$this->cutpowerhandler($sender, trim($content), $recv);
				break;
				case "MATIKAN":
					$this->enginehandler($sender, trim($content), 0, $rows[$i]);
				break;
				case "NYALAKAN":
					$this->enginehandler($sender, trim($content), 1, $rows[$i]);
				break;
				case "AGEN":
					$this->agenreplyhandler($sender, trim($content));
				break;
				//case "NOT BE AUTHORIZED!":
				//case "YOU AREN'T AUTHRIZED":
				case "SUPPLY ELECTRICITY OK":
				case "STOP ELECTRICITY OK":
					$this->enginereplyhandler($sender, trim($content), strtoupper($command));
				break;
				case "REPLY":
					$this->replyhandler($sender, trim($content));
				break;
				case "BAYAR":
					$this->paymenthandler($sender, trim($content));
				break;
				case "LUNAS":
					$this->invoiceapprovedhandler($sender, trim($content));
				break;
				case "RESETPASS":
					$this->resetpasswordhandler($sender, trim($content));
				break;
				case "OTOMATISKIRIM":
					$this->schedulehandler($sender, trim($content));
				break;
				case "ISIPULSA":
					$this->refillhandler($sender, trim($content));
				break;
				case "LOCK":
					$this->lockhandler($sender, trim($content));
				break;
				case "UNLOCK":
					$this->unlockhandler($sender, trim($content));
				break;
				case "AKUN":
					$this->akunhandler($sender);
				break;
				case "INFO":
					$this->infohandler($sender);
				break;
				case "PULSA UTAMA":
				case "PULSAUTAMA":
					$this->refillindosathandler($sender, trim($content));
				break;
				case "SISA PULSA ANDA":
					$this->refilltelkomselhandler($sender, trim($content));
				break;
				case "BALANCE":
					$this->refillxlhandler($sender, trim($content));
				break;
				case "REG":
					$this->reghandler($sender, trim($content));
				break;
				case "REG":
					$this->unreghandler($sender);
				break;

				case "SET APN OK":
				case "SET PROTOCOL OK":
				case "RESTART DEVICE IN":
				case "SET OUTPUT OK":
				case "APN:INDOSATGPRS":				
				case "RESET DEVICE IN 15 MINS":
				case "DEVICE IS NO ADMIN STATE":
				case "APN:TELKOMSEL":
				case "SET APN: INDOSATGPRS OK":
				case "RESTART SUCCESS!":
				case "AUTO TRACK SET OK":
				case "SET PROVIDER OK":
				// nothing
				break;
				default:
					$mail['subject'] = sprintf("unhandle sms from: %s", $sender);
					$mail['message'] = sprintf("%s %s", $command, $content);
					$mail['dest'] = "prastgtx@gmail.com,owner@adilahsoft.com,norman_ab@gpsandalas.com,zad_anwar@gpsandalas.com"; 
						
					lacakmobilmail($mail);					
					printf("unknown command\n");
			}
			
			unset($update);
			
			$update["Processed"] = "true";
			
			$smsdb->where("ID", $rows[$i]->ID);
			$smsdb->update("inbox", $update);

			unset($update);
			
			if (isset($rows[$i]->pending_id))
			{
				$update["pending_status"] = 2;
			
				$smsdb->where("pending_id", $rows[$i]->pending_id);
				$smsdb->update("pending", $update);
			}

		}

		unset($update);
		$update['config_value'] = 0;
		
		$smsdb->where("config_name", "inboxprocessing");
		$smsdb->update("config", $update);						
		
	}

	function senderror()
	{			
		$smsdb = $this->load->database("sms", TRUE);
		
		$smsdb->where("config_name", "lastsenderrornotice");
		$q = $smsdb->get("config");
	
		if ($q->num_rows() == 0)
		{
			$mail['subject'] = sprintf("sms sending error handler");
			$mail['message'] = sprintf("hello, i fine and ready to re-send sms until 3 times, thanks. i will report next 2 hour, wassalam.\r\n");
			$mail['dest'] = "prastgtx@gmail.com,owner@adilahsoft.com"; 
		
			lacakmobilmail($mail);					
			
			unset($insert);
			
			$insert['config_name'] = "lastsenderrornotice";
			$insert['config_value'] = mktime();
			
			$smsdb->insert("config", $insert);
		}
		else
		{
			$row = $q->row();
			$dt = mktime()-$row->config_value;
			$jam2 = 2*60*60;
			
			if ($dt > $jam2)
			{
				unset($update);
				
				$update['config_value'] = mktime();
				
				$smsdb->where("config_name", "lastsenderrornotice");
				$smsdb->update("config", $update);

				$mail['subject'] = sprintf("sms sending error handler");
				$mail['message'] = sprintf("hello, i fine and ready to re-send sms until 3 times, thanks. i will report next 2 hour, wassalam.\r\n");
				$mail['dest'] = "prastgtx@gmail.com,owner@adilahsoft.com"; 
			
				lacakmobilmail($mail);
			}
		}

		unset($update);
		$update['config_value'] = date("Y-m-d H:i:s");
		
		$smsdb->where("config_name", "lastsenderrorrun");
		$smsdb->update("config", $update);				
		
		// groub by yang sending error
		
		$smsdb->select("DestinationNumber, TextDecoded, count(*) tot", null, false);
		$smsdb->group_by("DestinationNumber, TextDecoded");
		$smsdb->where("Status", 'SendingError');
		$q = $smsdb->get("sentitems");
		
		$rowtotals = $q->result();
		for($i=0; $i < count($rowtotals); $i++)
		{
			if ($rowtotals[$i]->tot > 5)
			{
				$smsdb->where("TextDecoded", $rowtotals[$i]->TextDecoded);
				$smsdb->where("DestinationNumber", $rowtotals[$i]->DestinationNumber);
				$smsdb->where("Status", 'SendingError');
				$smsdb->delete("sentitems");
			}
		}
		
		
		$smsdb->where("retry", 0);
		$smsdb->where("Status", 'SendingError');
		//$smsdb->where("CreatorID <>", "retry");
		$q = $smsdb->get("sentitems");
		
		if ($q->num_rows() == 0)
		{
			printf("[%s] sending error not found\n", date("Ymd H:i:s"));
			return;
		}
		
		$rows = $q->result();
		for($i=0; $i < count($rows); $i++)
		{
			$id = $rows[$i]->ID;
			$retry = $rows[$i]->retry;
			$sender = $rows[$i]->DestinationNumber;
			$content = $rows[$i]->TextDecoded;
			$udh = trim($rows[$i]->UDH);
			
			if (strlen($udh) == 0)
			{
				$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
				$insert["SendingDateTime"] = date("Y-m-d H:i:s");
				$insert["TextDecoded"] = $content;
				$insert["DestinationNumber"] = $sender;
				$insert["UDH"] = "";
				
				$this->sendsms($insert);
			}
			else
			{
					// ???
			}
						
			unset($update);
			
			$update["retry"] = $retry+1;
			
			$smsdb->where("ID", $id);
			$smsdb->update("sentitems", $update);
		}
	}
	
	function sendsms($insert)
	{
		if (! isset($insert["DestinationNumber"]))
		{
			return;
		}
		
		if (! is_array($insert["DestinationNumber"]))
		{
			$this->sendsms1mobile($insert);
			return;
		}
		
		$hps = $insert["DestinationNumber"];
		foreach($hps as $hp)
		{
			$insert["DestinationNumber"] = $hp;
			$this->sendsms1mobile($insert);
		}
	}
	
	function sendsms1mobile($insert)
	{
		$maxlen = 153;
		
		$smsdb = $this->load->database("sms", TRUE);
		
		if (! isset($insert["DestinationNumber"]))
		{
			return;
		}
	
		if (strlen($insert['DestinationNumber'][0]) == 0)
		{
			return;
		}
	
		if ($insert["DestinationNumber"][0] == "+")
		{
			$insert["DestinationNumber"] = substr($insert["DestinationNumber"], 1);
		}
		
		print_r($insert);
		echo "\r\n";
		
		if ($this->debug)
		{			
			return;
		}
		
		$content = $insert['TextDecoded'];
		if (strlen($content) <= 160)
		{
			$contents[] = $content;
		}
		else
		while(true)
		{
			$contents[] = substr($content, 0, $maxlen);
			
			if (strlen($content) <= $maxlen) break;	
			
			$content = substr($content, $maxlen);
		}		

		if (count($contents) == 1)
		{
			$insert['TextDecoded'] = $contents[0];
			$smsdb->insert("outbox", $insert);
			return;
		}
		
		$len = count($contents);
		//$udh = substr(uniqid(), 0, 8);
		
		$udh = sprintf("050003%s", substr(uniqid(), 0, 2));
		
		$content = array_shift($contents);
		
		$insert['TextDecoded'] = $content;
		$insert['UDH'] = sprintf("%s%02d01", $udh, $len);
		$insert['MultiPart'] = 'true';

		$smsdb->insert("outbox", $insert);
		$lastid = $smsdb->insert_id();
		
		$i = 2;
		foreach($contents as $content)
		{			
			unset($insert);
			
			$insert['UDH'] = sprintf("%s%02d%02d", $udh, $len, $i);
			$insert['TextDecoded'] = $content;
			$insert['ID'] = $lastid;
			$insert['SequencePosition'] = $i;
			
			$i++;
			$smsdb->insert("outbox_multipart", $insert);
		}		
	}
	
	function enginereplyhandler($sender, $content, $command)
	{
		$this->db->where("vehicle_status", 1);
		$q = $this->db->get("vehicle");
		
		if ($q->num_rows() == 0)
		{
			return;
		}
		
		$rows = $q->result();
		for($i=0; $i < count($rows); $i++)
		{
			if (! same_valid_mobile($sender, $rows[$i]->vehicle_card_no)) continue; 
			
			$vehicle = $rows[$i];
		}
		
		if (! isset($vehicle))
		{
			return;
		}

		$this->db->where("user_id", $vehicle->vehicle_user_id);
		$q = $this->db->get("user");
		
		if ($q->num_rows() == 0)
		{
			return;
		}

		$rowuser = $q->row();
		

		$hp = valid_mobiles($rowuser->user_mobile);
		if ($hp !== FALSE) return;
				
		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");

		switch($command)
		{
			case "NOT BE AUTHORIZED!":
			case "YOU AREN'T AUTHRIZED":
				$sagent = $this->agenmodel->getAgenList($rowuser->user_agent);
			
				$insert["TextDecoded"] = sprintf("Nomor SMS Server belum didaftarkan untuk menyalakan/mematikan kendaraan %s. Silahkan hub agen Anda di %s", $rowvehicle->vehicle_no, $sagent);
			break;			
			case "SUPPLY ELECTRICITY OK":
				$insert["TextDecoded"] = sprintf("Engine kendaraan %s berhasil dinyalakan.", $vehicle->vehicle_no);
			break;
			case "STOP ELECTRICITY OK":
				$insert["TextDecoded"] = sprintf("Engine kendaraan %s berhasil dimatikan", $vehicle->vehicle_no);
			break;
		}
				
		$insert["DestinationNumber"] = $hp;
		$insert["UDH"] = "";
		
		$this->sendsms($insert);			
		
	}
	
	function infohandler($sender)
	{
		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = $this->config->item("SMS_COMMAND");
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";
		
		$this->sendsms($insert);			
		
	}

	function resetpasswordhandler($sender, $content)
	{
		$this->db->where("user_login", $content);
		$this->db->join("agent", "agent_id = user_agent");
		$q = $this->db->get("user");
		
		if ($q->num_rows() == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("Format sms yang benar RESETPASS<spasi><username>. Ketik username yang benar.");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			return;			
		}
		
		$rows = $q->result();
		for($i=0; $i < count($rows); $i++)
		{
			if (! same_valid_mobile($sender, $rows[$i]->user_mobile)) continue; 
			
			$user = $rows[$i];
		}
		
		if (! isset($user))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = "Nomor HP Anda tidak terdaftar sebagai pelanggan kami.";
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			return;			
		}
		
		if ($user->agent_msite)
		{
			$agentsite = $user->agent_msite;
		}
		else
		{
			$agentsite = "m.lacak-mobil.com";
		}
		

		$session = md5(uniqid());
		
		unset($insert);
		
		$insert['session_id'] = $session;
		$insert['session_user'] = $user->user_id;
		$insert['session_referer'] = "resetpasss";
		
		$this->db->insert("session", $insert);
		
		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = sprintf("Untuk reset password silahkan buka http://%s/pass.php?%s", $agentsite, $session);
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";
		
		$this->sendsms($insert);			
		return;			
		
	}

	function refilltelkomselhandler($sender, $content)
	{
		$pengirim = valid_mobile($sender);

		$this->db->where("vehicle_status", 1);
		$q = $this->db->get("vehicle");
		
		$rows = $q->result();
		for($i=0; $i < count($rows); $i++)
		{
			if (! same_valid_mobile($pengirim, $rows[$i]->vehicle_card_no)) continue; 

			if (! preg_match("/([0-9]+)/", $content, $matches)) return;
			$pulsa = $matches[1];

			if (! preg_match("/([0-3][0-9]\/[0-1][0-9]\/[0-9]{4})/", $content, $matches)) return;
			$aktif = str_replace("/", "", $matches[1]);

			$info = json_decode($rows[$i]->vehicle_info);
			
			$info->sisapulsa = $pulsa;
			$info->masaaktif = $aktif;
			
			unset($update);
			$update['vehicle_info'] = json_encode($info);
			
			$this->db->where("vehicle_id", $rows[$i]->vehicle_id);
			$this->db->update("vehicle", $update);

			return;
		}

	}	
	
	function refillxlhandler($sender, $content)
	{
		$pengirim = valid_mobile($sender);

		$this->db->where("vehicle_status", 1);
		$q = $this->db->get("vehicle");
		
		$rows = $q->result();
		for($i=0; $i < count($rows); $i++)
		{
			if (! same_valid_mobile($pengirim, $rows[$i]->vehicle_card_no)) continue; 

			if (! preg_match("/([0-9]+)/", $content, $matches)) return;
			$pulsa = $matches[1];

			if (! preg_match("/([0-3][0-9]\/[0-1][0-9]\/[0-9]{2})/", $content, $matches)) return;
			$aktif = str_replace("/", "", $matches[1]);

			$info = json_decode($rows[$i]->vehicle_info);
			
			$info->sisapulsa = $pulsa;
			$info->masaaktif = $aktif;
			
			unset($update);
			$update['vehicle_info'] = json_encode($info);
			
			$this->db->where("vehicle_id", $rows[$i]->vehicle_id);
			$this->db->update("vehicle", $update);

			return;
		}

	}
	
	function refillindosathandler($sender, $content)
	{
		$pengirim = valid_mobile($sender);

		$this->db->where("vehicle_status", 1);
		$q = $this->db->get("vehicle");
		
		$rows = $q->result();
		for($i=0; $i < count($rows); $i++)
		{
			if (! same_valid_mobile($pengirim, $rows[$i]->vehicle_card_no)) continue; 

			$pos = strpos($content, "Aktif");
			if ($pos == -1) return;
			
			$pulsa = preg_replace("/[^0-9]/", "", substr($content, 0, $pos));
			
			$content = substr($content, $pos);
			
			$pos = strpos($content, "Tenggang");
			if ($pos == -1) return;
			
			$aktif = preg_replace("/[^0-9]/", "", substr($content, 0, $pos));
			
			$info = json_decode($rows[$i]->vehicle_info);
			
			$info->sisapulsa = $pulsa;
			$info->masaaktif = $aktif;
						
			unset($update);
			$update['vehicle_info'] = json_encode($info);
			
			$this->db->where("vehicle_id", $rows[$i]->vehicle_id);
			$this->db->update("vehicle", $update);

			return;
		}

	}
	
	function akunhandler($sender)
	{
		$pengirim = valid_mobile($sender);		

		$this->db->where_in('user_type', array(2, 3));
		$this->db->where("user_status", 1);
		$q = $this->db->get("user");
		
		$rows = $q->result();
		for($i=0; $i < count($rows); $i++)
		{
			if (! same_valid_mobile($pengirim, $rows[$i]->user_mobile)) continue; 

			//$user = $rows[$i];
			$users[] = $rows[$i];
		}

		if (! isset($users))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = "Nomor HP Anda belum terdaftar sebagai pelanggan kami.";
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			
			return;
		}
		
		foreach ($users as $user)
		{		
			// informasi vehicle
			
			$this->db->where("vehicle_user_id", $user->user_id);
			$q = $this->db->get("vehicle");
			
			$vehicles = $q->result();

			// informasi agent
			
			$this->db->where("user_type", 3);
			$this->db->where("user_agent", $user->user_agent);		
			$q = $this->db->get("user");
			
			$agents = $q->result();
			
			// build message
			
			$content = sprintf("username: %s\nhp: %s", $user->user_login, $user->user_mobile);
			
			if (valid_emails($user->user_mail))
			{
				$content .= sprintf("\nmail: %s", $user->user_mail);
			}
			
			if ($user->user_payment_type == 1)
			{
				if (($user->user_payment_period % 12) == 0)
				{
					if (($user->user_payment_period/12) == 1)
					{
						$period = sprintf("thn");
					}
					else
					{
						$period = sprintf("%d thn", round($user->user_payment_period/12));
					}
				}
				else
				{
					if ($user->user_payment_period == 1)
					{
						$period = sprintf("bln");
					}
					else
					{
						$period = sprintf("%d bln", $user->user_payment_period/12);
					}
				}
				
				$content .= sprintf("\nbiaya: %s/%s/mobil", number_format($user->user_payment_amount, 0, "", "."), $period);
			}
			else
			if ($user->user_payment_type == 2)
			{
				if (($user->user_payment_period % 12) == 0)
				{
					if (($user->user_payment_period/12) == 1)
					{
						$period = sprintf("thn");
					}
					else
					{
						$period = sprintf("%d thn", round($user->user_payment_period/12));
					}
				}
				else
				{
					if ($user->user_payment_period == 1)
					{
						$period = sprintf("bln");
					}
					else
					{
						$period = sprintf("%d bln", $user->user_payment_period/12);
					}
				}			
				$content .= sprintf("\nbiaya: %s/%s", number_format($user->user_payment_amount, 0, "", "."), $period);
			}
			
			if (count($agents) > 0)
			{
				$content .= "\nINFO AGEN:";

				$i = 0;
				foreach($agents as $agent)
				{
					$content .= sprintf("\n%d. nama: %s ,hp: %s", ++$i, $agent->user_name, $agent->user_mobile);
				}
				
			}
			
			if (count($vehicles) > 0)
			{
				$content .= "\nINFO KEND:";

				$i = 0;
				foreach($vehicles as $vehicle)
				{
					$t = dbintmaketime($vehicle->vehicle_active_date2, 0);
					
					$content .= sprintf("\n%d. nopol: %s ,jenis: %s, aktif sd: %s, hp: %s", ++$i, $vehicle->vehicle_no, $vehicle->vehicle_name, date("d/m/Y", $t), $vehicle->vehicle_card_no);
				}
			}

			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $content;
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			
			continue;
		}
	}	
	
	function unreghandler($sender)
	{
		$pengirim = valid_mobile($sender);
		
		$ids = $this->usermodel->getIdsByMobile(array($pengirim));
		
		if ($ids === FALSE)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("No HP Anda tidak terdaftar sebagai pelanggan kami. Silahkan hubungi agen Anda.");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			
			return;				
		}
		
		$this->db->where_in("user_id", $ids);
		$q = $this->db->get("user");
		
		$rows = $q->result();
		for($i=0; $i < count($rows); $i++)
		{
			$nolama = trim($rows[$i]->user_mobile);
			if (strlen($nolama) == 0) continue;
			
			$nolamas = explode(";", $nolama);			
			if (! in_array($pengirim, $nolamas)) continue;
			
			$nosekarangs = array_diff($nolamas, array($pengirim));
			if (count($nosekarangs) == 0)
			{
				unset($update);
				
				$update["user_mobile"] = "";
				$this->db->where("user_id", $rows[$i]->user_id);
				$this->db->update("user", $update);
			}
			else
			{
				unset($update);
				
				$update["user_mobile"] = explode($nosekarangs);
				$this->db->where("user_id", $rows[$i]->user_id);
				$this->db->update("user", $update);
			}
			
			$found = true;
		}
		
		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = sprintf("No HP Anda telah dihapus dari daftar pelanggan kami.");
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";
		
		$this->sendsms($insert);			
		
		return;				
		
	}
	
	function reghandler($sender, $content)
	{
		$pengirim = valid_mobile($sender);
		$hpadmin = implode(";", $this->config->item("SMS_ADMIN"));
		
		if (! same_valid_mobile($sender, $hpadmin))
		{
			$ids = $this->usermodel->getIdsByMobile(array($pengirim));
			
			if ($ids === FALSE)
			{
				unset($insert);
				
				$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
				$insert["SendingDateTime"] = date("Y-m-d H:i:s");
				$insert["TextDecoded"] = sprintf("No HP Anda tidak terdaftar sebagai pelanggan kami. Silahkan hubungi agen Anda.");
				$insert["DestinationNumber"] = $sender;
				$insert["UDH"] = "";
				
				$this->sendsms($insert);			
				
				return;				
			}
		}
		
		$contents = explode(" ", $content, 2);
		
		if (count($contents) < 2)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("Format yang benar: REG <login> <no yang didaftarkan>.");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			

			return;
		}
		
		$this->db->where("user_login", $contents[0]);
		$q = $this->db->get("user");
		
		if ($q->num_rows() == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("%s tidak terdaftar sebagai pelanggan kami. Format yang benar: REG <login> <no yang didaftarkan>.", $contents[0]);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			

			return;
		}
		
		$row = $q->row();
		
		if (isset($ids))
		{
			if (! in_array($row->user_id, $ids))
			{
				unset($insert);
				
				$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
				$insert["SendingDateTime"] = date("Y-m-d H:i:s");
				$insert["TextDecoded"] = sprintf("%s adalah bukan no hp pemilik dari login %s. Silahkan hubungi agen Anda.", $sender, $contents[0]);
				$insert["DestinationNumber"] = $sender;
				$insert["UDH"] = "";
				
				$this->sendsms($insert);			

				return;
			}
		}
		
		$nobaru = valid_mobile($contents[1]);
		
		
		$this->db->where_in("user_id", $ids);
		$q = $this->db->get("user");
		
		$rows = $q->result();
		for($i=0; $i < count($rows); $i++)
		{
			$nolama = trim($rows[$i]->user_mobile);
			if (strlen($nolama) == 0)
			{
				$nosekarang = $nobaru;
				$found = true;

				unset($update);
				
				$update["user_mobile"] = $nosekarang;
				$this->db->where("user_id", $rows[$i]->user_id);
				$this->db->update("user", $update);

				break;
			}
			
			$nolamas = explode(";", $nolama);
			if (in_array($nobaru, $nolamas)) continue;
			
			$nosekarang = $nolama.";".$nobaru;
			$found = true;
			
			unset($update);
			
			$update["user_mobile"] = $nosekarang;
			$this->db->where("user_id", $rows[$i]->user_id);
			$this->db->update("user", $update);
		}
		
		if (isset($found))
		{
			$message = sprintf("No %s telah ditambahkan sebagai no pemilik dari login %s", $nobaru, $contents[0]);
		}
		else
		{
			$message = sprintf("No %s sudah terdaftar sebagai no pemilik dari login %s", $nobaru, $contents[0]);
		}

		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = $message;
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";
		
		$this->sendsms($insert);			
		
	}
		
	function lockhandler($sender, $content)
	{
		$pengirim = valid_mobile($sender);		
		$vehicleno = nomobil(trim($content));
				
		$this->db->where("vehicle_status", 1);
		$this->db->where("REPLACE(REPLACE(vehicle_no, ' ', ''), '.', '') = '".mysql_escape_string($vehicleno)."'", null);
		$this->db->join("user", "vehicle_user_id = user_id");
		$this->db->join("agent", "user_agent = agent_id");
		$q = $this->db->get("vehicle");
		
		if ($q->num_rows() == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("No kend %s tidak ada dalam database, ketik no kendaraan yang benar. Format sms: LOCK <no kendaraan>, misal LOCK B1234CD", $vehicleno);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			
			return;
		}
		
		$row = $q->row();
		
		if (! same_valid_mobile($pengirim, $row->user_mobile))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("No Hp Anda tidak diperbolehkan me-lock kend %s. Gunakan no hp yang terdaftar pada saat registrasi.", $vehicleno);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			
			return;
		}
		
		if ($row->vehicle_info)
		{			
			$json = json_decode($row->vehicle_info);
		}

		$json->lock = 1;
		
		unset($update);
		
		$update['vehicle_info'] = json_encode($json);
		
		$this->db->where("vehicle_id", $row->vehicle_id);
		$this->db->update("vehicle", $update);
		
		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = sprintf("Kendaraan %s berhasil di-lock.", $vehicleno);
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";
		
		$this->sendsms($insert);		
	}
	
	function zzz()
	{
		$this->unlockhandler("085717019778", "B1059GJ");
	}

	function unlockhandler($sender, $content)
	{
		$pengirim = valid_mobile($sender);		
		$vehicleno = nomobil(trim($content));
				
		$this->db->where("vehicle_status", 1);
		$this->db->where("REPLACE(REPLACE(vehicle_no, ' ', ''), '.', '') = '".mysql_escape_string($vehicleno)."'", null);
		$this->db->join("user", "vehicle_user_id = user_id");
		$this->db->join("agent", "user_agent = agent_id");
		$q = $this->db->get("vehicle");
		
		if ($q->num_rows() == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("No kend %s tidak ada dalam database, ketik no kendaraan yang benar. Format sms: UNLOCK <no kendaraan>, misal UNLOCK B1234CD", $vehicleno);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			
			return;
		}
		
		$row = $q->row();
		
		if (! same_valid_mobile($pengirim, $row->user_mobile))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("No Hp Anda tidak diperbolehkan me-unlock kend %s. Gunakan no hp yang terdaftar pada saat registrasi.", $vehicleno);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			
			return;
		}
		
		if ($row->vehicle_info)
		{			
			$json = json_decode($row->vehicle_info);
			
			if (isset($json->lock))
			{
				unset($json->lock);
			}
		}
		
		unset($update);
		
		$update['vehicle_info'] = json_encode($json);
		
		$this->db->where("vehicle_id", $row->vehicle_id);
		$this->db->update("vehicle", $update);

		$command = $this->smsmodel->resumeengine($row->vehicle_type);
		$gsm = valid_mobile($row->vehicle_card_no);
		
		if ($gsm)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $command;
			$insert["DestinationNumber"] = $gsm;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);		
		}
		
		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = sprintf("Kendaraan %s berhasil di-unlock.", $vehicleno);
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";
		
		$this->sendsms($insert);		
	}
	
	function refillhandler($sender, $content)
	{
		$pengirim = valid_mobile($sender);
		
		$this->db->where("user_status", 1);
		$q = $this->db->get("user");
		
		$rows = $q->result();
		for($i=0; $i < count($rows); $i++)
		{
			if (! same_valid_mobile($pengirim, $rows[$i]->user_mobile)) continue; 

			$userids[] = $rows[$i]->user_id;
		}

		if (! isset($userids))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = "Nomor HP Anda belum terdaftar pelanggan kami.";
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			return;			
		}
		
		$contents = explode(" ", $content, 2);
		if (count($contents) < 2)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = "Format sms yang benar: ISIPULSA <nominal pulsa> <no kendaraan>, misal ISIPULSA 20000 B1234CD";
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			return;			
		}

		$nominal = str_replace(".", "", $contents[0]);

		if (! is_numeric($nominal))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = "Nilai nominal pulsa salah. Format sms yang benar: ISIPULSA <nominal pulsa> <no kendaraan>, misal ISIPULSA 20000 B1234CD";
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			return;			
		}
		
		$vehicleno = nomobil(trim($contents[1]));
				
		$this->db->where("vehicle_status", 1);
		$this->db->where("REPLACE(REPLACE(vehicle_no, ' ', ''), '.', '') = '".mysql_escape_string($vehicleno)."'", null);
		$this->db->join("user", "vehicle_user_id = user_id");
		$this->db->join("agent", "user_agent = agent_id");
		$q = $this->db->get("vehicle");
		
		if ($q->num_rows() == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("No kend %s tidak ada dalam database, ketik no kendaraan yang benar. Format sms yang benar: ISIPULSA <nominal pulsa> <no kendaraan>, misal ISIPULSA 20000 B1234CD", $vehicleno);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			return;			
		}
		
		$row = $q->row();
		
		if (! in_array($row->user_id, $userids))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("No hp %s bukan pemilik kend %s, ketik no kendaraan yang benar. Format sms yang benar: ISIPULSA <nominal pulsa> <no kendaraan>, misal ISIPULSA 20000 B1234CD", $pengirim, $vehicleno);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			return;			
		}
		
		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = "Terima kasih atas informasi pengisian pulsanya.";
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";
		
		$masaaktif = $this->smsmodel->masaaktif($nominal);
		if (! $masaaktif)
		{
			unset($mail);
			
			$mail['sender'] = "info@lacak-mobil.com";
			$mail['subject'] = sprintf("ISIPULSA");
			$mail['message'] = $content;
			$mail['dest'] = "jaya@vilanishop.com,jayatriyadi@hotmail.com,prastgtx@gmail.com,owner@adilahsoft.com"; 
		
			lacakmobilmail($mail);
			
			return;
		}
		
		
		$t = mktime(date("G"), date("i"), date("s"), date('n'), date('j')+$masaaktif, date("Y"));
		
		unset($update);
		
		$update['vehicle_active_date'] = date("Ymd", $t);
		
		$this->db->where("vehicle_id", $row->vehicle_id);
		$this->db->update("vehicle", $update);
	}
	
	function schedulehandler($sender, $content)
	{
		$pengirim = valid_mobile($sender);
		$smsdb = $this->load->database("sms", TRUE);
		
		$q = $smsdb->get("schedule");
		$rows = $q->result();
		
		for($i=0; $i < count($rows); $i++)
		{
			if (! same_valid_mobile($pengirim, $rows[$i]->hp)) continue; 

			$schedules[] = $rows[$i];
		}

		if (! isset($schedules))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = "Nomor HP Anda belum terdaftar sebagai no yg dikirim posisi kendaraan secara otomatis.";
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			return;			
		}
		
		$contents = explode(" ", $content);
		$status = strtoupper(trim($contents[0]));
		
		if (! in_array($status, array("ON", "OFF", "INFO", "JADWAL")))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = "Format yang benar OTOMATISKIRIM <ON|OFF|INFO> atau OTOMATISKIRIM JADWAL <jam mulai> <jam akhir> <interval> <no kend>";
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			return;			
		}
		
		if ($status == "JADWAL")
		{
			$contents = explode(" ", $content, 5);
			$isjadwalvalid = true;
			if (count($contents) < 5)
			{
				$isjadwalvalid = false;
			}
			else
			{
				
				$start = $contents[1];
				$end = $contents[2];
				$interval = $contents[3];
				$nokend = nomobil(trim($contents[4]));
				
				if ((! is_numeric($start)) || (! is_numeric($end)) || (! is_numeric($interval)) || ($start >= $end) || ($start < 0) || ($end > 24))
				{
					$isjadwalvalid = false;
				}
				else
				foreach($schedules as $schedule)
				{
					$nokend1 = nomobil(trim($schedule->vehicle));
					
					if ($nokend1 == $nokend)
					{
						unset($update);
						
						$update['interval'] = $interval;
						$update['start'] = $start;
						$update['end'] = $end;
						$update['sent'] = date("Y-m-d H:i:s", mktime()-3600*$interval);
						
						$smsdb->where("id", $schedule->id);
						$smsdb->update("schedule", $update);
						
						unset($insert);
						
						$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
						$insert["SendingDateTime"] = date("Y-m-d H:i:s");
						$insert["TextDecoded"] = sprintf("Sms posisi kend %s menjadi dikirim setiap %d jam dari pukul %02d:00 - %02d:00", $nokend, $interval, $start, $end);						
						$insert["DestinationNumber"] = $sender;
						$insert["UDH"] = "";
						
						$this->sendsms($insert);
						return;
					}
					
					$isjadwalvalid = false;
				}
			}
			
			if (! $isjadwalvalid)
			{
				unset($insert);
				
				$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
				$insert["SendingDateTime"] = date("Y-m-d H:i:s");
				$insert["TextDecoded"] = "OTOMATISKIRIM JADWAL <jam mulai> <jam berakhir> <interval dlm jam> <no kend>";
				$insert["DestinationNumber"] = $sender;
				$insert["UDH"] = "";
				
				$this->sendsms($insert);			
				return;			
			}
			
			return;
		}
		
		if ($status == "INFO")
		{
			$s = "";
			foreach($schedules as $schedule)
			{
				$s .= sprintf("Posisi kend %s dikirim setiap %d jam dari pukul %02d:00 - %02d:00\r\n", $schedule->vehicle, $schedule->interval, $schedule->start, $schedule->end);
			}
			
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $s;			
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			
			return;
		}
		
		foreach($schedules as $schedule)
		{
			unset($update);
			
			$update['sent'] = date("Y-m-d H:i:s", mktime()-$schedule->interval*3600);
			$update['status'] =  ($status == "ON") ? 1 : 2;
			
			$smsdb->where("id", $schedule->id);
			$smsdb->update("schedule", $update);
		}
				
		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		if ($status == "ON")
		{
			$insert["TextDecoded"] = "Layanan otomatis kirim posisi telah diaktifkan kembali.";
		}
		else
		{
			$insert["TextDecoded"] = "Layanan otomatis kirim posisi telah dinon-aktifkan.";
		}
		
		
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";
		
		$this->sendsms($insert);			
		return;			
		
	}
	
	function enginehandler($sender, $content, $turnon, $inbox)
	{
		$q = $this->db->get("user");
		$rows = $q->result();
		
		for($i=0; $i < count($rows); $i++)
		{
			if (! same_valid_mobile($sender, $rows[$i]->user_mobile)) continue; 

			$userids[] = $rows[$i]->user_id;
		}
		
		if (! isset($userids))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = "Nomor HP Anda tidak terdaftar sebagai pelanggan kami.";
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			return;			
		}
		
		$vehicleno = nomobil(trim($content));
		
		$this->db->where("vehicle_status", 1);
		$this->db->where("REPLACE(REPLACE(vehicle_no, ' ', ''), '.', '') = '".mysql_escape_string($vehicleno)."'", null);
		$this->db->join("user", "vehicle_user_id = user_id");
		$q = $this->db->get("vehicle");
		
		if ($q->num_rows() == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("No kendaraan %s tidak terdaftar dalam database kami. Silahkan hub agen Anda.", $vehicleno);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			return;			
		}
		
		$rowvehicle = $q->row();
		
		if (! in_array($rowvehicle->user_id, $userids))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("No HP anda tidak diperbolehkan menyalakan/mematikan engine kendaraan %s.", $vehicleno);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			return;			
		}

		if (	0 
				|| (in_array(strtoupper($rowvehicle->vehicle_type), $this->config->item("vehicle_t1")))
				|| (in_array(strtoupper($rowvehicle->vehicle_type), $this->config->item("vehicle_t3")))
				|| (in_array(strtoupper($rowvehicle->vehicle_type), $this->config->item("vehicle_t5")))
				|| (in_array(strtoupper($rowvehicle->vehicle_type), $this->config->item("vehicle_gtp")))
		) {
			$command = $turnon ? $this->smsmodel->resumeengine($rowvehicle->vehicle_type) : $this->smsmodel->cutoffengine($rowvehicle->vehicle_type);
			$gsm = valid_mobile($rowvehicle->vehicle_card_no);
			if ($gsm)
			{			
				unset($insert);
				
				$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
				$insert["SendingDateTime"] = date("Y-m-d H:i:s");
				$insert["TextDecoded"] = $command;		
				$insert["DestinationNumber"] = $gsm;
				$insert["UDH"] = "";
				
				$this->sendsms($insert);
				
				unset($insert);
				
				$sagent = $this->agenmodel->getAgenList($rowvehicle->user_agent);				
				if ($turnon) 
				{
					$terimakasih = "Pesan untuk menghidupkan mesin telah dikirim ke GPS Anda. Tunggu sebentar. Jika tidak berhasil, hub agen Anda di ".$sagent;
				}
				else
				{
					$terimakasih = "Pesan untuk mematikan mesin telah dikirim ke GPS Anda. Tunggu sebentar. Jika tidak berhasil, hub agen Anda di ".$sagent;
				}
				
				$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
				$insert["SendingDateTime"] = date("Y-m-d H:i:s");
				$insert["TextDecoded"] = $terimakasih;
				$insert["DestinationNumber"] = $sender;
				$insert["UDH"] = "";
				
				$this->sendsms($insert);
							
			}
			return;
		}
		
		
		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = sprintf("GPS %s tidak mempunyai feature menyalakan atau mematikan mesin via sms ke no ini.", $rowvehicle->vehicle_type);
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";
		
		$this->sendsms($insert);			
		return;		
	}
		
	function replyhandler($sender, $content)
	{
		$sender = valid_mobile($sender);
		
		if (	true
				&& (! in_array($sender, $this->config->item("SMS_LACAKMOBIL")))
				&& (! in_array($sender, $this->config->item("SMS_GPSANDALAS")))
				&& (! in_array($sender, $this->config->item("SMS_ADMIN")))
		)
		{
			return;
		}

		$contents = explode(" ", $content, 2);
		if (count($contents) < 2)
		{
			return;
		}
		
		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = $contents[1];
		$insert["DestinationNumber"] = $contents[0];
		$insert["UDH"] = "";
		
		$this->sendsms($insert);
	}
	
	function broadcasthandler($sender, $content, $usertype)
	{
		$sender = valid_mobile($sender);		
		if (in_array($sender, $this->config->item("SMS_LACAKMOBIL")))
		{
			$this->db->where("user_agent <>", $this->config->item("GPSANDALASID"));
		}
		else
		if (in_array($sender, $this->config->item("SMS_GPSANDALAS")))
		{
			$this->db->where("user_agent", $this->config->item("GPSANDALASID"));
		}
		else
		{
			return;
		}
		
		if ($usertype)
		{
			$this->db->where("user_type", $usertype);
		}
		
		//$this->db->distinct();
		$this->db->select("user_mobile");
		$q = $this->db->get("user");
		
		if ($q->num_rows() == 0) return;
		
		$rows = $q->result();
		$t = mktime();
		foreach($rows as $row)
		{
			$hp = valid_mobiles($row->user_mobile);
			if ($hp === FALSE) continue;
			
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s", $t);
			$insert["TextDecoded"] = $content;
			$insert["DestinationNumber"] = $hp;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			$t += 3*60;
		}
	
	}
	
	function settinggeofencehandler($sender, $content)
	{
		$pengirim = valid_mobile($sender);
		$smsdb = $this->load->database("sms", TRUE);
		
		$content = str_replace(":", ";", $content);

		$contents = explode(";", $content);
		if (count($contents) < 3)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $this->config->item("SMS_INVALIDGEOFENCECOMMAND_MESSAGE");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		list($city, $province, $vehicleno) = explode(";", $content, 3);
		
		$city = trim($city);
		$city = str_replace('<', '', $city);
		$city = str_replace('>', '', $city);		
		
		if (! $city)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $this->config->item("SMS_INVALIDGEOFENCECOMMAND_MESSAGE");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}

		$province = trim($province);
		$province = str_replace('<', '', $province);
		$province = str_replace('>', '', $province);
		$province = strtoupper($province);
		
		if (! $province)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $this->config->item("SMS_INVALIDGEOFENCECOMMAND_MESSAGE");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		$vehicleno = nomobil($vehicleno);
		
		$this->db->where("vehicle_status", 1);
		$this->db->where("REPLACE(REPLACE(vehicle_no, ' ', ''), '.', '') = '".mysql_escape_string($vehicleno)."'", null);
		$this->db->join("user", "vehicle_user_id = user_id");
		$this->db->join("agent", "agent_id = user_agent");
		$q = $this->db->get("vehicle");
		
		if ($q->num_rows() == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf($this->config->item("SMS_NOMOBIL_NOTFOUND"), $vehicleno);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		$rowvehicle = $q->row();
		
		// list hp yang diijinkan
		
		$owners = $this->config->item("SMS_OWNER");
		for($i=0; $i < count($owners); $i++)
		{
			$hps[] = intl_mobile($owners[$i]);
		}

		$lacaks = $this->config->item("SMS_LACAKMOBIL");
		for($i=0; $i < count($lacaks); $i++)
		{
			$hps[] = intl_mobile($lacaks[$i]);
		}

		$andalass = $this->config->item("SMS_GPSANDALAS");
		for($i=0; $i < count($andalass); $i++)
		{
			$hps[] = intl_mobile($andalass[$i]);
		}
		
		$hp = valid_mobiles($rowvehicle->user_mobile);
		if ($hp !== FALSE)
		{
			foreach($hp as $hp1)
			{
				$hps[] = intl_mobile($hp1);
			}
		}
		
		if (! in_array($pengirim, $hps))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $this->config->item("SMS_GEOFENCE_ACCESS_DENIED");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;			
		}
		
		$rowvehicle = $q->row();
		$ownercoorp = $this->agenmodel->getLicense($rowvehicle);
		
		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = sprintf($this->config->item("SMS_GEOFENCE_THANKS"), $rowvehicle->user_login, $rowvehicle->vehicle_no, $ownercoorp);
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";
		
		$this->sendsms($insert);
		
		$token = md5(uniqid());
		
		unset($insert);
		
		$insert['session_id'] = $token;
		$insert['session_user'] = $rowvehicle->vehicle_user_id;
		
		$this->db->insert("session", $insert);
				
		$message  = "";
		$message .= "<br />SMS No: ".$pengirim."\r\n";
		$message .= "<br />SMS content: ".$content."\r\n";
		$message .= "<br />Vehicle ID: ".$rowvehicle->vehicle_id."\r\n";
		$message .= "<br />URL: ".base_url()."geofence/sms/".$rowvehicle->vehicle_id."/".$pengirim."/".$token;

		$mail['sender'] = "info@lacak-mobil.com";
		$mail['format'] = "html";
		$mail['subject'] = sprintf("Request Geofence");
		$mail['message'] = $message;
		$mail['dest'] = "jaya@vilanishop.com,jayatriyadi@hotmail.com,prastgtx@gmail.com,owner@adilahsoft.com"; 
	
		lacakmobilmail($mail);
		
		return;		

	}
	
	function settingnotifyhandler($sender, $content)
	{
		$pengirim = valid_mobile($sender);
		$smsdb = $this->load->database("sms", TRUE);

		$contents = explode(" ", $content);
		
		$onoff = trim($contents[0]);
		$type = (count($contents) > 1) ? strtoupper(trim($contents[1])) : "";
		
		if (strlen($onoff) == "")
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = "Format yang benar NOTIFIKASI <ON/OFF> atau NOTIFIKASI <ON/OFF> <SEMUA/GEOFENCE/PARKIR/SPEED>";
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		if (strlen($type))
		{
			if (! in_array($type, array("SEMUA", "GEOFENCE", "PARKIR", "SPEED")))
			{
				unset($insert);
				
				$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
				$insert["SendingDateTime"] = date("Y-m-d H:i:s");
				$insert["TextDecoded"] = "Format yang benar NOTIFIKASI <ON/OFF> atau NOTIFIKASI <ON/OFF> <SEMUA/GEOFENCE/PARKIR/SPEED>";
				$insert["DestinationNumber"] = $sender;
				$insert["UDH"] = "";
				
				$this->sendsms($insert);
				return;
			}
		}
		
		if (substr($pengirim, 0, 2) == "62")
		{
			$localsender = "0".substr($pengirim, 2);
		}		
		else
		{
			$localsender = $pengirim;
		}
		
		$ids = $this->usermodel->getIdsByMobile(array($pengirim, $localsender));
		if ($ids === FALSE)
		{
			return;
		}
		
		$this->db->where_in("user_id", $ids);
		$q = $this->db->get("user");
		
		if ($q->num_rows() == 0)
		{
			$binaries = "1111111111111111";
		}
		else
		{
			$rowuser = $q->row();
			$binaries = paddingleft(decbin($rowuser->user_sms_notifikasi), "0", 16);
		}

		unset($update);
		
		if (strcasecmp($onoff, "OFF") == 0)
		{
			
			switch($type)
			{
				case "GEOFENCE":
					$binaries[15] = 0;
					
					$update['user_sms_notifikasi'] = bindec($binaries);
					$reply = "No Anda telah di-nonaktif-kan u/ terima notifikasi geofence. U/ mengaktifkan kembali kirim sms NOTIFIKASI ON GEOFENCE";

				break;
				case "SPEED":
					$binaries[14] = 0;
					
					$update['user_sms_notifikasi'] = bindec($binaries);
					$reply = "No Anda telah di-nonaktif-kan u/ terima notifikasi maksimum kecepatan. U/ mengaktifkan kembali kirim sms NOTIFIKASI ON SPEED";

				break;				
				case "PARKIR":
					$binaries[13] = 0;
					
					$update['user_sms_notifikasi'] = bindec($binaries);
					$reply = "No Anda telah di-nonaktif-kan u/ terima notifikasi maksimum parkir. U/ mengaktifkan kembali kirim sms NOTIFIKASI ON PARKIR";

				break;
				default:
					$update['user_sms_notifikasi'] = 0;
					$reply = "No Anda telah di-nonaktif-kan u/ terima notifikasi. U/ mengaktifkan kembali kirim sms NOTIFIKASI ON";
			}			
		}
		else
		{
			switch($type)
			{
				case "GEOFENCE":
					$binaries[15] = 1;
					
					$update['user_sms_notifikasi'] = bindec($binaries);
					$reply = "No Anda telah di-aktif-kan u/ terima notifikasi geofence. U/ berhenti kirim sms NOTIFIKASI OFF GEOFENCE";

				break;
				case "SPEED":
					$binaries[14] = 1;
					
					$update['user_sms_notifikasi'] = bindec($binaries);
					$reply = "No Anda telah di-aktif-kan u/ terima notifikasi maksimum kecepatan. U/ berhenti kirim sms NOTIFIKASI OFF SPEED";

				break;				
				case "PARKIR":
					$binaries[13] = 1;
					
					$update['user_sms_notifikasi'] = bindec($binaries);
					$reply = "No Anda telah di-aktif-kan u/ terima notifikasi maksimum parkir. U/ berhenti kirim sms NOTIFIKASI OFF PARKIR";

				break;
				default:
					$update['user_sms_notifikasi'] = 65535;
					$reply = "No Anda telah di-aktif-kan u/ terima notifikasi. U/ berhenti kirim sms NOTIFIKASI OFF";
			}			
		}
		
		$this->db->where_in("user_id", $ids);		
		$this->db->update("user", $update);

		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = $reply;
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";
		
		$this->sendsms($insert);
		
		return;
		
	}
	
	function cutpowerhandler($sender, $content, $recv)
	{		
		$pengirim = valid_mobile($sender);		
		
		//$this->db->distinct();
		$this->db->where("vehicle_status", 1);
		$this->db->select("vehicle_card_no");
		$q = $this->db->get("vehicle");
		
		if ($q->num_rows() == 0) return;
		
		$rows = $q->result();
		foreach($rows as $row)
		{
			$vehiclegsm = valid_mobile($row->vehicle_card_no);
	
			if (! same_valid_mobile($vehiclegsm, $pengirim)) continue;
	
			$this->db->where("vehicle_card_no", $row->vehicle_card_no);
			$this->db->join("user", "vehicle_user_id = user_id");
			$q = $this->db->get("vehicle");
			
			if ($q->num_rows() == 0) return;
			
			$vehicle = $q->row();
			
			$pengirim = valid_mobiles($vehicle->user_mobile);
			if ($pengirim === FALSE) return;
			
			unset($insert);			

			$contents = explode("\n", $content);
			foreach($contents as $line)
			{
				if (strcasecmp("TIME:", substr($line, 0, 5)) == 0)
				{
					$jam = trim(substr($line, 5));
				}

				if (strcasecmp("DATE:", substr($line, 0, 5)) == 0)
				{
					$tgl = trim(substr($line, 5));
				}

			}
			
			if (! isset($jam)) 
			{
				if (strpos($content, "MAIN POWER OFF") === FALSE)
				{
					return;
				}
				
				$jam = date("H:i", $recv);
			}
			if (! isset($tgl)) 
			{
				if (strpos($content, "MAIN POWER OFF") === FALSE)
				{
					return;
				}
				
				$tgl = date("d/m/Y", $recv);
			}
			
			$time = formmaketimeshort($tgl." ".$jam)+7*3600;
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("Pada %s, arus listrik kend '%s' terputus, mohon dicek. U/ monitor posisi sms PSS %s %s", date("d/m/Y H:i:s", $time), $vehicle->vehicle_no, $vehicle->user_login, $vehicle->vehicle_no);
			$insert["DestinationNumber"] = $pengirim;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
						
			return;
		}		
	}

	function settingactivatepervehiclehandler($sender, $content)
	{
		$pengirim = valid_mobile($sender);

		$smsaktivasi = $this->config->item("SMS_AKTIVASI");
		if (! in_array($pengirim, $smsaktivasi)) return;

		$smsdb = $this->load->database("sms", TRUE);

		$contents = explode(" ", $content);
		if (count($contents) < 2)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = "Format yang benar AKTIVASIMOBIL <lama diperpanjang dlm bln> <nokendaraan>";
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		list($activelong, $vehicleno) = explode(" ", $content, 2);
		
		$activelong = trim($activelong);
		$activelong = str_replace('<', '', $activelong);
		$activelong = str_replace('>', '', $activelong);
		
		if ((! is_numeric($activelong)) || ($activelong <= 0))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = "Format yang benar AKTIVASIMOBIL <lama diperpanjang dlm bln> <nokendaraan>";
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		$vehicleno = trim($vehicleno);
		$vehicleno = str_replace('<', '', $vehicleno);
		$vehicleno = str_replace('>', '', $vehicleno);		
		$vehicleno = nomobil($vehicleno);		
		
		$this->db->where("vehicle_status", 1);
		$this->db->where("REPLACE(REPLACE(vehicle_no, ' ', ''), '.', '') = '".mysql_escape_string($vehicleno)."'", null);
		$q = $this->db->get("vehicle");

		if ($q->num_rows() == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("Vehicle %s tidak ditemukan", $vehicleno);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		$rowvehicle = $q->row();

		$lastactive = dbintmaketime($rowvehicle->vehicle_active_date2, 0);
		$newactive = mktime(0, 0, 0, date('n', $lastactive)+$activelong, date('j', $lastactive), date('Y', $lastactive));
		
		unset($update);

		$update['vehicle_active_date2'] = date("Ymd", $newactive);
	
		$this->db->where("vehicle_id", $rowvehicle->vehicle_id);
		$this->db->update("vehicle", $update);
		
		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = sprintf("Kendaraan dgn no mobil %s telah diperpanjang selama %s bulan dr sebelumnya", $vehicleno, $activelong);
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";
		
		$this->sendsms($insert);
		
		return;
	}
		
	function settingactivateperuserhandler($sender, $content)
	{
		$pengirim = valid_mobile($sender);		

		$smsaktivasi = $this->config->item("SMS_AKTIVASI");
		if (! in_array($pengirim, $smsaktivasi)) return;
				
		$smsdb = $this->load->database("sms", TRUE);

		$contents = explode(" ", $content);
		if (count($contents) < 2)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = "Format yang benar AKTIVASIUSER <lama diperpanjang dlm bln> <namauser>";
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		list($activelong, $login) = explode(" ", $content, 2);
		
		$activelong = trim($activelong);
		$activelong = str_replace('<', '', $activelong);
		$activelong = str_replace('>', '', $activelong);
		
		if ((! is_numeric($activelong)) || ($activelong <= 0))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = "Format yang benar AKTIVASIUSER <lama diperpanjang dlm bln> <namauser>";
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		$login = trim($login);
		$login = str_replace('<', '', $login);
		$login = str_replace('>', '', $login);
		
		$this->db->select("vehicle.*");
		$this->db->where("user_login", $login);
		$this->db->join("vehicle", "vehicle_user_id = user_id");
		$q = $this->db->get("user");
		
		if ($q->num_rows() == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("Login %s tidak ditemukan / tidak ada kendaraan yang meggunakan layanan gps", $login);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		$rowvehicles = $q->result();
		foreach($rowvehicles as $vehicle)
		{
			$lastactive = dbintmaketime($vehicle->vehicle_active_date2, 0);
			$newactive = mktime(0, 0, 0, date('n', $lastactive)+$activelong, date('j', $lastactive), date('Y', $lastactive));

			unset($update);
			
			$update['vehicle_active_date2'] = date("Ymd", $newactive);

			$this->db->where("vehicle_id", $vehicle->vehicle_id);
			$this->db->update("vehicle", $update);			
		}

		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = sprintf("Kendaraan dgn login %s telah diperpanjang selama %s bulan dr sebelumnya", $login, $activelong);
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";
		
		$this->sendsms($insert);
		
		return;
	}
	
	function settingkmhandler($sender, $content)
	{
		
		$pengirim = valid_mobile($sender);
		$smsdb = $this->load->database("sms", TRUE);
		
		$contents = explode(" ", $content);
		if (count($contents) < 2)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $this->config->item("SMS_INVALIDKMCOMMAND_MESSAGE");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		list($km, $vehicleno) = explode(" ", $content, 2);
		
		$km = trim($km);
		$km = str_replace('<', '', $km);
		$km = str_replace('>', '', $km);
		
		if (! is_numeric($km))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $this->config->item("SMS_INVALIDKMCOMMAND_MESSAGE");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		$vehicleno = nomobil(trim($vehicleno));		
		
		$this->db->where("vehicle_status", 1);
		$this->db->where("REPLACE(REPLACE(vehicle_no, ' ', ''), '.', '') = '".mysql_escape_string($vehicleno)."'", null);
		$this->db->join("user", "vehicle_user_id = user_id");
		$this->db->join("agent", "agent_id = user_agent");
		$q = $this->db->get("vehicle");
		
		if ($q->num_rows() == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf($this->config->item("SMS_NOMOBIL_NOTFOUND"), $vehicleno);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		$rowvehicle = $q->row();
		
		// list hp yang diijinkan
		
		$owners = $this->config->item("SMS_OWNER");
		for($i=0; $i < count($owners); $i++)
		{
			$hps[] = intl_mobile($owners[$i]);
		}

		$lacaks = $this->config->item("SMS_LACAKMOBIL");
		for($i=0; $i < count($lacaks); $i++)
		{
			$hps[] = intl_mobile($lacaks[$i]);
		}

		$andalass = $this->config->item("SMS_GPSANDALAS");
		for($i=0; $i < count($andalass); $i++)
		{
			$hps[] = intl_mobile($andalass[$i]);
		}
		
		$hp = valid_mobiles($rowvehicle->user_mobile);
		if ($hp !== FALSE)
		{
			foreach($hp as $h)
			{
				$hps[] = intl_mobile($h);
			}
		}
		
		if (! in_array($pengirim, $hps))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $this->config->item("SMS_KM_ACCESS_DENIED");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;			
		}
		
		$update['vehicle_maxspeed'] = $km;
		$this->db->where("vehicle_id", $rowvehicle->vehicle_id);
		$this->db->update("vehicle", $update);
				
		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = sprintf("Setting alert maksimum kecepatan %s telah berhasil. Terima kasih.", $rowvehicle->vehicle_no);
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";
		
		$this->sendsms($insert);
		
		return;			
		
	}

	function settingparkinghandler($sender, $content)
	{
		
		$pengirim = valid_mobile($sender);
		$smsdb = $this->load->database("sms", TRUE);
		
		$contents = explode(" ", $content);
		if (count($contents) < 2)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $this->config->item("SMS_INVALIDPARKCOMMAND_MESSAGE");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		list($parkirtime, $vehicleno) = explode(" ", $content, 2);
		
		$parkirtime = trim($parkirtime);
		$parkirtime = str_replace('<', '', $parkirtime);
		$parkirtime = str_replace('>', '', $parkirtime);
		
		if (! is_numeric($parkirtime))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $this->config->item("SMS_INVALIDPARKCOMMAND_MESSAGE");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		$vehicleno = nomobil(trim($vehicleno));		
		
		$this->db->where("vehicle_status", 1);
		$this->db->where("REPLACE(REPLACE(vehicle_no, ' ', ''), '.', '') = '".mysql_escape_string($vehicleno)."'", null);
		$this->db->join("user", "vehicle_user_id = user_id");
		$this->db->join("agent", "agent_id = user_agent");
		$q = $this->db->get("vehicle");
		
		if ($q->num_rows() == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf($this->config->item("SMS_NOMOBIL_NOTFOUND"), $vehicleno);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		$rowvehicle = $q->row();
		
		// list hp yang diijinkan
		
		$owners = $this->config->item("SMS_OWNER");
		for($i=0; $i < count($owners); $i++)
		{
			$hps[] = intl_mobile($owners[$i]);
		}

		$lacaks = $this->config->item("SMS_LACAKMOBIL");
		for($i=0; $i < count($lacaks); $i++)
		{
			$hps[] = intl_mobile($lacaks[$i]);
		}

		$andalass = $this->config->item("SMS_GPSANDALAS");
		for($i=0; $i < count($andalass); $i++)
		{
			$hps[] = intl_mobile($andalass[$i]);
		}
		
		$hp = valid_mobiles($rowvehicle->user_mobile);
		if ($hp !== FALSE)
		{
			foreach($hp as $h)
			{
				$hps[] = intl_mobile($h);
			}
		}
		
		if (! in_array($pengirim, $hps))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $this->config->item("SMS_PARK_ACCESS_DENIED");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;			
		}
		
		$update['vehicle_maxparking'] = $parkirtime;
		$this->db->where("vehicle_id", $rowvehicle->vehicle_id);
		$this->db->update("vehicle", $update);
				
		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = sprintf("Setting alert maksimum lama parkir %s telah berhasil. Terima kasih.", $rowvehicle->vehicle_no);
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";
		
		$this->sendsms($insert);
		
		return;			
		
	}
	
	function posisisemuahandler($sender, $content)
	{
		unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $this->config->item("SMS_PSSSEMUA_DISABLE");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
	      /*
                $login = trim($content);
		$login = str_replace('<', '', $login);
		$login = str_replace('>', '', $login);
		
		$this->db->where("user_status", 1);
		$this->db->where("user_login", trim($login));
		$q = $this->db->get("user");
	
		if ($q->num_rows() == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $this->config->item("SMS_INVALID_LOGIN_SEMUA");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		$rowuser = $q->row();
		if ($rowuser->user_type != 2)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $this->config->item("SMS_ACCESS_DENIED_PSS_SEMUA");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";

			$this->sendsms($insert);
			
			return;
		}		

		$this->db->where("user_id", $rowuser->user_id);	
		$this->db->where("vehicle_status", 1);
		$this->db->join("user", "vehicle_user_id = user_id");
		$this->db->join("agent", "user_agent = agent_id");
		$q = $this->db->get("vehicle");
		
		if ($q->num_rows() == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $this->config->item("SMS_NOMOBIL_NOTFOUND_SEMUA");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";

			$this->sendsms($insert);
			
			return;			
		}

		$rowvehicles = $q->result();
		for($i=0; $i < count($rowvehicles); $i++)
		{
			$this->posisihandler($sender, sprintf("%s %s", $login, $rowvehicles[$i]->vehicle_no));
			sleep(2);
		} */
	}
	
	
	function agenreplyhandler($sender, $content)
	{
		$smsdb = $this->load->database("sms", TRUE);
		
		$nooruser = trim($content);
		if (strlen($nooruser) == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = "Format salah, seharusnya AGEN <user name atau no polisi>";
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		$login = str_replace('<', '', $nooruser);
		$login = str_replace('>', '', $login);
		
		$this->db->where("user_status", 1);
		$this->db->where("user_login", trim($login));
		$q = $this->db->get("user");
		
		if ($q->num_rows() > 0)
		{
			$row = $q->row();
			
			$this->db->where("user_status", 1);
			$this->db->where("user_type", 3);
			$this->db->where("user_agent", $row->user_agent);
			
			$q = $this->db->get("user");			
			$rows = $q->result();
			
			$params['agents'] = $rows;
			$content = $this->load->view("sms/agentreply", $params, true);
			
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $content;
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
				
		$vehicleno = nomobil($nooruser);

		$this->db->where("vehicle_status", 1);
		$this->db->where("REPLACE(REPLACE(vehicle_no, ' ', ''), '.', '') = '".mysql_escape_string($vehicleno)."'", null);
		$this->db->join("user", "vehicle_user_id = user_id");
		$q = $this->db->get("vehicle");

		if ($q->num_rows() == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = "Format salah, seharusnya AGEN <user name atau no polisi>";
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;			
		}
		
		$row = $q->row();
		
		$this->db->where("user_status", 1);
		$this->db->where("user_type", 3);
		$this->db->where("user_agent", $row->user_agent);
		
		$q = $this->db->get("user");			
		$rows = $q->result();
		
		$params['agents'] = $rows;
		$content = $this->load->view("sms/agentreply", $params, true);
		
		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = $content;
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";
		
		$this->sendsms($insert);
		
		return;

	}
	
	function invoiceapprovedhandler($sender, $content)
	{
		$pengirim = valid_mobile($sender);		

		$smsboss = $this->config->item("SMS_INVOICEAPPROVED");
		if (! in_array($pengirim, $smsboss)) return;
				
		$smsdb = $this->load->database("sms", TRUE);
		
		unset($param);
		$params['invoiceno'] = trim($content);
		$json = erpservice("/invoice/getinfo", $params, true);
		if (strlen($json) == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("Invoice No# %s tidak ada.", $params['invoiceno']);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		$invoice = json_decode($json);
		
		if ($invoice->invoice->invoice_status == 3)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("Invoice No# %s sudah dibayar.", $params['invoiceno']);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;			
		}
		
		unset($params);
		
		$params['referer'] = "BOSS";
		$params['invoiceid'] = $invoice->invoice->invoice_id;		
		erpservice("/invoice/approved", $params);

		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = sprintf("Invoice No# %s lunas. Terima kasih", $$invoice->invoice->invoice_no);
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";
		
		$this->sendsms($insert);
		
		return;			

	}
	
	function paymenthandler($sender, $content)
	{
		$smsdb = $this->load->database("sms", TRUE);
		
		$contents = explode(" ", trim($content), 5);
		if (count($contents) != 5)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = "Format salah,seharusnya BAYAR [INVOICE#] [JML DIBAYAR DLM RIBUAN] [TGL BAYAR DLM DDMMYYYY] [CARA PEMBAYARAN] [NAMA PEMILIK REK/KODE VALIDASI JIKA TUNAI], misal BAYAR B1234CD 150 19092011 ATM ABAH ADILAH";
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		unset($param);
		$params['invoiceno'] = trim($contents[0]);
		$json = erpservice("/invoice/getinfo", $params, true);
		if (strlen($json) == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("Invoice No# %s tidak ada. Silahkah hubungi agen Anda", $params['invoiceno']);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		$invoice = json_decode($json);
		
		if ($invoice->invoice->invoice_status == 3)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("Terima kasih atas konfirmasi pembayaran Invoice No# %s . Kami akan segera mengaktifkan kembali kendaraan anda.", $params['invoiceno']);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		$amount = trim($contents[1]);
		$amount = str_replace(".", "", $amount);
		
		if ((! is_numeric($amount)) || ($amount <= 0))
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("Format jumlah yang dibayar salah (%s)", $amount);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;			
		}
		
		$sdate = trim($contents[2]);
		
		if ($sdate['0'] == "0")
		{
			$sdate = substr($sdate, 1);
		}
		
		$date = $sdate*1;
		
		$day = floor($date/1000000);
		$month = floor(($date%1000000)/10000);
		$year = ($date%1000000)%10000;
		
		$t = mktime(0, 0, 0, $month, $day, $year);

		if (date("jmY", $t) != $sdate)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("Format tanggal pembayaran salah. Format yang benar adalah ddmmyyyy, misal 09112011");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;			
		}
		
		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = "Terima kasih atas konfirmasi pembayaraannya. Kami akan segera memproses pembayaran Anda.";
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";
		
		$this->sendsms($insert);				
		
		unset($params);
		
		$params['id'] = $invoice->invoice->invoice_id;
		$params['transfermethod'] = trim($contents[3]);
		$params['bankdest'] = 0;
		$params['amount'] = $amount*1000;
		$params['paymentdate'] = date("d/m/Y", $t);
		$params['transfercode'] = trim($contents[4]);
		$params['sendername'] = trim($contents[4]);
		
		erpservice("/invoice/saveconfirmation/1", $params);

	}
	
	function test()
	{
			$this->unreghandler("085711475755");
	}
	
	function fromfile($filename="sms.txt")
	{
		
		$arr = file($filename);
		$content = "";
		for($i=0; $i < count($arr)-1; $i++)
		{
			$content .= $arr[$i];
		}
		
		$dest = trim($arr[count($arr)-1]);
		$dests = explode(",", $dest);
		
		foreach($dests as $dest)
		{
			$dest = trim($dest);
			if (! $dest) continue;
					
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $content; 
			$insert["DestinationNumber"] = $dest;
			$insert["UDH"] = "";

			$this->sendsms($insert);
		}
	}
	
	function posisihandler($sender, $content, $ispending=0)
	{
		$smsdb = $this->load->database("sms", TRUE);
		
		$contents = explode(" ", $content);
		if (count($contents) < 2)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $this->config->item("SMS_INVALIDCOMMAND_MESSAGE");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);
			
			return;
		}
		
		list($login, $vehicleno) = explode(" ", $content, 2);
		
		$login = str_replace('<', '', $login);
		$login = str_replace('>', '', $login);
				
		$this->db->where("user_status", 1);
		$this->db->where("user_login", trim($login));
		$q = $this->db->get("user");
		
		if ($q->num_rows() == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $this->config->item("SMS_INVALID_LOGIN");
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";

			$this->sendsms($insert);
			
			return;
		}
		
		$rowuser = $q->row();
		
		$vehicleno = nomobil(trim($vehicleno));
		
		$this->db->where("vehicle_status", 1);
		$this->db->where("REPLACE(REPLACE(vehicle_no, ' ', ''), '.', '') = '".mysql_escape_string($vehicleno)."'", null);
		$this->db->join("user", "vehicle_user_id = user_id");
		$this->db->join("agent", "user_agent = agent_id");
		$q = $this->db->get("vehicle");
	
		if ($q->num_rows() == 0)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf($this->config->item("SMS_NOMOBIL_NOTFOUND"), $vehicleno);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";

			$this->sendsms($insert);
			
			return;
		}
	
		$rowvehicle = $q->row();
		$rowvehicles = $q->result();
		
		foreach($rowvehicles as $rowvehicle1)
		{
			$owners[] = $rowvehicle1->user_id;
		}
		
		if ($rowuser->user_type == 2)
		{
			if (! in_array($rowuser->user_id, $owners))
			{
				unset($insert);
				
				$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
				$insert["SendingDateTime"] = date("Y-m-d H:i:s");
				$insert["TextDecoded"] = sprintf($this->config->item("SMS_POSISI_ACCESS_DENIED"), $login, $vehicleno);
				$insert["DestinationNumber"] = $sender;
				$insert["UDH"] = "";

				$this->sendsms($insert);
				
				return;
			}			
		}
		else
		if ($rowuser->user_type == 3)
		{
			if ($rowuser->user_agent != $rowvehicle->user_agent)
			{
				unset($insert);
				
				$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
				$insert["SendingDateTime"] = date("Y-m-d H:i:s");
				$insert["TextDecoded"] = sprintf($this->config->item("SMS_POSISI_ACCESS_DENIED"), $login, $vehicleno);
				$insert["DestinationNumber"] = $sender;
				$insert["UDH"] = "";

				$this->sendsms($insert);
				
				return;
			}			
		}
		
		$t = $rowvehicle->vehicle_active_date2;
		$now = date("Ymd");
		
		if ($t < $now)
		{
			if ($rowvehicle->user_agent == $this->config->item("GPSANDALASID"))
			{
				$reply = sprintf($this->config->item("SMS_NOMOBIL_EXPIRED_GPSANDALAS"), $vehicleno);
			}
			else
			{
				$sagent = $this->agenmodel->getAgenList($rowvehicle->user_agent);
				$reply = sprintf($this->config->item("SMS_NOMOBIL_EXPIRED_LACAKMOBIL"), $vehicleno, $sagent);
			}

			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $reply;
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";

			$this->sendsms($insert);
			
			return;
		}
		
		list($name, $host) = explode("@", $rowvehicle->vehicle_device);
				
		$gps = $this->gpsmodel->GetLastInfo($name, $host, true, false, 0, $rowvehicle->vehicle_type);
		if (! $gps)
		{
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("%s belum aktif. Silahkan hub agen Anda.", $vehicleno);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";

			$this->sendsms($insert);			
			return;
		}
		
		if ($rowvehicle->agent_msite)
		{
			$agentsite = $rowvehicle->agent_msite;
		}
		else
		{
			$agentsite = "m.lacak-mobil.com";
		}
		
		if (! $agentsite)
		{
			if ($rowvehicle->user_agent == 3)
			{
				$agentsite = "m.gpsandalas.com";
			}
			else
			{
				$agentsite = "m.lacak-mobil.com";
			}
		}
		
		
		$mapurl = sprintf("http://%s/map.php?%s=%s", $agentsite, date("YmdHis", $gps->gps_timestamp), urlencode($rowvehicle->vehicle_no));						
		$abbreviation = $this->smsmodel->abbreviation($mapurl);
		$mapurl = sprintf("http://%s/%s", $agentsite, $abbreviation);		
		
		$gtps = $this->config->item("vehicle_gtp");
		$gtpdoors = $this->config->item("vehicle_gtp_door");
		
		$dir = $gps->direction-1;
		$dirs = $this->config->item("direction");
		
		if ($dir < 0)
		{
			$sdir = $gps->gps_course."°";
		}
		else
		if ($dir >= count($dirs))
		{
			$sdir = $gps->gps_course."°";
		}
		else
		{
			$sdir = $dirs[$dir]."(".$gps->gps_course."°)";
		}
		
		if (in_array(strtoupper($rowvehicle->vehicle_type), $gtps))
		{
			$this->db->order_by("gps_info_time", "DESC");
			$this->db->where("gps_info_device", $rowvehicle->vehicle_device);
			$q = $this->db->get($this->gpsmodel->getGPSInfoTable($rowvehicle->vehicle_type), 1, 0);
				
			if ($q->num_rows() == 0)
			{
				$engine = "OFF";
				$door = "CLOSED";
			}
			else
			{
				$rowinfo = $q->row();					
				$ioport = $rowinfo->gps_info_io_port;
					
				$status3 = ((strlen($ioport) > 1) && ($ioport[1] == 1)); // opened/closed
				$status2 = ((strlen($ioport) > 3) && ($ioport[3] == 1)); // release/hold
				$status1 = ((strlen($ioport) > 4) && ($ioport[4] == 1)); // on/off
					
				$engine = $status1 ? "ON" : "OFF";
				$door = $status3 ? "OPENED" : "CLOSED";
			}			
				
			if (in_array(strtoupper($rowvehicle->vehicle_type), $gtpdoors))
			{
				$reply = sprintf("%s\n%s\n%s\n%s %s\n%s %s\n%s\nEng:%s Door:%s\nPeta: %s", $rowvehicle->vehicle_no, date("d/m/Y H:i", $gps->gps_timestamp), $gps->georeverse->display_name, $gps->gps_latitude_real_fmt, $gps->gps_longitude_real_fmt, $gps->gps_speed_fmt."kph", $sdir, ($gps->gps_status != "V") ? "OK" : "NO", $engine, $door, $mapurl);
			}
			else
			{
				$reply = sprintf("%s\n%s\n%s\n%s %s\n%s %s\n%s\nEng:%s\nPeta: %s", $rowvehicle->vehicle_no, date("d/m/Y H:i", $gps->gps_timestamp), $gps->georeverse->display_name, $gps->gps_latitude_real_fmt, $gps->gps_longitude_real_fmt, $gps->gps_speed_fmt."kph", $sdir, ($gps->gps_status != "V") ? "OK" : "NO", $engine, $mapurl);
			}
		}
		else
		{
			$reply = sprintf("%s\n%s\n%s\n%s %s\n%s %s\n%s\nPeta: %s", $rowvehicle->vehicle_no, date("d/m/Y H:i", $gps->gps_timestamp), $gps->georeverse->display_name, $gps->gps_latitude_real_fmt, $gps->gps_longitude_real_fmt, $gps->gps_speed_fmt."kph", $sdir, ($gps->gps_status != "V") ? "OK" : "NO", $mapurl);
		}
		
		$delta = mktime() - $gps->gps_timestamp;
		if ($delta >= 1800)
		{
			$restart = $this->smsmodel->restart($rowvehicle->vehicle_type, $rowvehicle->vehicle_operator);
			
			$isrestart = (strlen($restart) > 0) && (strcasecmp($restart, "NOT SUPPORT") != 0);			
			$hpgps = valid_mobile($rowvehicle->vehicle_card_no);
			
			if ($isrestart && $hpgps)
			{
				unset($insert);
				
				$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
				$insert["SendingDateTime"] = date("Y-m-d H:i:s");
				$insert["TextDecoded"] = $restart;
				$insert["DestinationNumber"] = $hpgps;
				$insert["UDH"] = "";

				$this->sendsms($insert);			
				
				if (! $ispending)
				{				
					unset($insert);
					
					$insert['pending_inbox'] = sprintf("PSS %s", $content);
					$insert['pending_status'] = 1;
					$insert['pending_time'] = date("Y-m-d H:i:s", mktime()+60);
					$insert['pending_sender'] = $sender;
					
					$smsdb->insert("pending", $insert);
					
					return;
				}
			}
		}
				
		unset($insert);
		
		$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
		$insert["SendingDateTime"] = date("Y-m-d H:i:s");
		$insert["TextDecoded"] = $reply;
		$insert["DestinationNumber"] = $sender;
		$insert["UDH"] = "";

		$this->sendsms($insert);			
		
		if ($delta >= 24*3600)
		{
			//
			
			$this->db->where("user_type", 3);
			$this->db->where("user_agent", $rowvehicle->user_agent);
			$q = $this->db->get("user");
			if ($q->num_rows() == 0) return;
			
			$rowagents = $q->result();
			
			$sagent = "";
			$idx = 1;
			foreach($rowagents as $agent)
			{
				$sagent .= sprintf("%d. %s %s %s  ", $idx, $agent->user_name, $agent->user_address." ".$agent->user_city." ".$agent->user_province, $agent->user_mobile);
				$idx++;
			}
			
			unset($insert);
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = sprintf("Kendaraan Anda %s tidak terupdate > 1 hari, silahkan hubungi agen Anda di %s", $rowvehicle->vehicle_no, $sagent);
			$insert["DestinationNumber"] = $sender;
			$insert["UDH"] = "";

			$this->sendsms($insert);			
		}
		
		return;
		
	}
	
	function sendq()
	{
		$smsdb = $this->load->database("sms", TRUE);
		
		$smsdb->where("config_name", "lastsendqnotice");
		$q = $smsdb->get("config");
	
		if ($q->num_rows() == 0)
		{
			$mail['subject'] = sprintf("get sms from mondial sms server");
			$mail['message'] = sprintf("hello, i fine and ready to process sms from mondial sms server, i hope replace that server, amin. i will report next 5 hour, wassalam.\r\n");
			$mail['dest'] = "prastgtx@gmail.com,owner@adilahsoft.com"; 
		
			lacakmobilmail($mail);					
			
			unset($insert);
			
			$insert['config_name'] = "lastsendqnotice";
			$insert['config_value'] = mktime();
			
			$smsdb->insert("config", $insert);
		}
		else
		{
			$row = $q->row();
			$dt = mktime()-$row->config_value;
			$jam2 = 5*60*60;
			
			if ($dt > $jam2)
			{
				unset($update);
				
				$update['config_value'] = mktime();
				
				$smsdb->where("config_name", "lastsendqnotice");
				$smsdb->update("config", $update);

				$mail['subject'] = sprintf("get sms from mondial sms server");
				$mail['message'] = sprintf("hello, i fine and ready to process sms from mondial sms server, i hope replace that server, amin. i will report next 5 hour, wassalam.\r\n");
				$mail['dest'] = "prastgtx@gmail.com,owner@adilahsoft.com"; 
			
				lacakmobilmail($mail);
			}
		}

		unset($update);
		$update['config_value'] = date("Y-m-d H:i:s");
		
		$smsdb->where("config_name", "lastsendqrun");
		$smsdb->update("config", $update);						
		
		$smsdb->where("config_name", "mondialprocessing");
		$q = $smsdb->get("config");
		
		if ($q->num_rows() == 0)
		{
			unset($insert);
			$insert['config_name'] = 'mondialprocessing';
			$insert['config_value'] = "1";
			
			$smsdb->insert("config", $insert);						
		}
		else
		{
			$rowmondial = $q->row();
			if ($rowmondial->config_value == 1) 
			{
				printf("sendq is running");
				return;
			}
		}		
		
		unset($update);
		$update['config_value'] = 1;
		
		$smsdb->where("config_name", "mondialprocessing");
		$smsdb->update("config", $update);				

		$smscolodb = $this->load->database("smscolo", TRUE);
		
		$smscolodb->order_by("ReceivingDateTime", "asc");
		$smscolodb->where("Processed", 'false');
		$q = $smscolodb->get("inbox");
		$rows = $q->result();
		$sendtime = mktime();
		for($i=0; $i < count($rows); $i++)
		{
			unset($update);
			
			$update['Processed'] = 'true';
			$smscolodb->where("ID", $rows[$i]->ID);
			$smscolodb->update("inbox", $update);
			
			unset($insert);
			
			$insert["InsertIntoDB"] = $rows[$i]->ReceivingDateTime;			
			$insert["SendingDateTime"] = date("Y-m-d H:i:s");
			$insert["TextDecoded"] = $rows[$i]->TextDecoded;
			$insert["DestinationNumber"] = $rows[$i]->SenderNumber;
			$insert["UDH"] = "";
			
			$this->sendsms($insert);			
			
			$sendtime += 120;
			sleep(1);			
		}

		unset($update);
		$update['config_value'] = 0;
		
		$smsdb->where("config_name", "mondialprocessing");
		$smsdb->update("config", $update);				
		
		$this->load->database("default", TRUE);
	}
	
	function expired()
	{
		if (date("j")%2) return;
		
		$this->db->where("vehicle_active_date2 <", date("Ymd"));
		$this->db->where("vehicle_status", 1);
		$this->db->join("user", "user_id = vehicle_user_id");
		$q = $this->db->get("vehicle");
		
		$rows = $q->result();
		for($i=0; $i < count($rows); $i++)
		{
			$hps = valid_mobiles($rows[$i]->user_mobile);
			if ($hps === FALSE) continue;
			
			foreach($hps as $hp)
			{
				$expired[$hp][] = $rows[$i];
			
				if (! isset($notifikasi[$hp])) 
				{
					$notifikasi[$hp] = false;
				}
				
				$notifikasi[$hp] = $notifikasi[$hp] || isON($rows[$i]->user_sms_notifikasi, 12);
			}
		}
		
		if (! isset($expired)) return;
		
		$total = 0;
		$summary = 0;
		$start = microtime(true);		
		foreach($expired as $nohp=>$vehicles)
		{			
			if ($vehicles[0]->user_agent == $this->config->item("GPSANDALASID"))
			{
				$hpagent = "031-70771444";
				$site = "www.gpsandalas.com";
				$sender = "support@gpsandalas.com";
			}
			else
			{
				$hpagent = "0877 777 97 940";
				$site = "www.lacak-mobil.com";
				$sender = "support@lacak-mobil.com";
			}						
			
			if (count($vehicles) == 1)
			{
				$tgl = inttodate($vehicles[0]->vehicle_active_date2);
				$content = sprintf($this->config->item("SMS_EXPIRED_1_VEHICLE"), $vehicles[0]->user_login, $vehicles[0]->vehicle_no, $tgl, $hpagent, $site);
				
				$summary .= sprintf("%03d. %s %s %s\r\n", ++$total, $vehicles[0]->user_login, $vehicles[0]->vehicle_no, $tgl);
			}
			else
			{
				$sexpired = "";
				for($i=0; $i < count($vehicles); $i++)
				{
					if ($i > 0)
					{
						$sexpired .= ", ";
					}
					
					$tgl = inttodate($vehicles[$i]->vehicle_active_date2);
					$sexpired .= sprintf("%s %s", $vehicles[$i]->vehicle_no, $tgl);
					
					$summary .= sprintf("%03d. %s %s %s\r\n", ++$total, $vehicles[$i]->user_login, $vehicles[$i]->vehicle_no, $tgl);
				}		
		
				$content = sprintf($this->config->item("SMS_EXPIRED_N_VEHICLE"), $vehicles[0]->user_login, $sexpired, $hpagent, $site);				
			}
			
			if (isset($notifikasi[$nohp]) && $notifikasi[$nohp])
			{
				unset($insert);
				
				$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
				$insert["SendingDateTime"] = date("Y-m-d H:i:s");			
				$insert["TextDecoded"] = $content;
				$insert["DestinationNumber"] = $nohp;
				$insert["UDH"] = "";
				//$insert["CreatorID"] = "retry";
				
				$this->sendsms($insert);			
			}
			
			$mail['subject'] = sprintf("[%s] Masa aktif layanan %s telah habis", $site, $vehicles[0]->user_login );
			$mail['message'] = $content;			
			$mail['sender'] = $sender;
			
			$emails = get_valid_emails($vehicles[0]->user_mail);
			if ($emails !== FALSE)
			{
				foreach($emails as $email)
				{
					$mail['dest'][] = $email; 
					lacakmobilmail($mail);
				}
			}					
						
			$start += 5;
			time_sleep_until($start);											
		}

		$mail['subject'] = sprintf("%d expired vehicle", $total);
		$mail['message'] = $summary;
		$mail['dest'] = "prastgtx@gmail.com,owner@adilahsoft.com,norman_ab@gpsandalas.com,zad_anwar@gpsandalas.com"; 
	
		lacakmobilmail($mail);					

	}

	function expirednday($n=2)
	{
		$day2 = mktime(0, 0, 0, date('n'), date('j')-$n-1, date('Y'));
		
		$this->db->where("vehicle_active_date2", date("Ymd", $day2));
		$this->db->where("vehicle_status", 1);
		$this->db->join("user", "user_id = vehicle_user_id");
		$q = $this->db->get("vehicle");
		
		$rows = $q->result();
		for($i=0; $i < count($rows); $i++)
		{
			$hps = valid_mobiles($rows[$i]->user_mobile);
			if ($hps === FALSE) continue;
			
			foreach($hps as $hp)
			{			
				$expired[$hp][] = $rows[$i];
				if (! isset($notifikasi[$hp])) 
				{
					$notifikasi[$hp] = false;
				}
				
				$notifikasi[$hp] = $notifikasi[$hp] || isON($rows[$i]->user_sms_notifikasi, 12);
			}
		}
		
		if (! isset($expired)) return;
		
		$total = 0;
		$summary = 0;
		$start = microtime(true);		
		foreach($expired as $nohp=>$vehicles)
		{			
			if ($vehicles[0]->user_agent == $this->config->item("GPSANDALASID"))
			{
				$hpagent = "031-70771444";
				$site = "www.gpsandalas.com";
				$sender = "support@gpsandalas.com";
			}
			else
			{
				$hpagent = "0877 777 97 940";
				$site = "www.lacak-mobil.com";
				$sender = "support@lacak-mobil.com";
			}						
			
			if (count($vehicles) == 1)
			{
				$content = sprintf($this->config->item("SMS_REMINDER_IN_N_DAY_1_VEHICLE"), $vehicles[0]->user_login, $vehicles[0]->vehicle_no, $n, $hpagent, $site);
				
				$summary .= sprintf("%03d. %s %s\r\n", ++$total, $vehicles[0]->user_login, $vehicles[0]->vehicle_no);
			}
			else
			{
				$sexpired = "";
				for($i=0; $i < count($vehicles); $i++)
				{
					if ($i > 0)
					{
						$sexpired .= ", ";
					}
					
					$sexpired .= sprintf("%s", $vehicles[$i]->vehicle_no);
					
					$summary .= sprintf("%03d. %s %s\r\n", ++$total, $vehicles[$i]->user_login, $vehicles[$i]->vehicle_no);
				}		
		
				$content = sprintf($this->config->item("SMS_REMINDER_IN_N_DAY_N_VEHICLE"), $vehicles[0]->user_login, $n, $sexpired, $hpagent, $site);				
			}
			
			if (isset($notifikasi[$nohp]) && $notifikasi[$nohp])
			{
				unset($insert);
				
				$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
				$insert["SendingDateTime"] = date("Y-m-d H:i:s");			
				$insert["TextDecoded"] = $content;
				$insert["DestinationNumber"] = $nohp;
				$insert["UDH"] = "";
				//$insert["CreatorID"] = "retry";
				
				$this->sendsms($insert);
			}
			
			$mail['subject'] = sprintf("[%s] Masa aktif layanan %s telah habis", $site, $vehicles[0]->user_login );
			$mail['message'] = $content;			
			$mail['sender'] = $sender;
			
			$emails = get_valid_emails($vehicles[0]->user_mail);
			if ($emails !== FALSE)
			{
				foreach($emails as $email)
				{
					$mail['dest'][] = $email; 
					lacakmobilmail($mail);
				}
			}					
						
			$start += 5;
			time_sleep_until($start);											
		}

		$mail['subject'] = sprintf("expired vehicle in %d day(s): %d", $n, $total);
		$mail['message'] = $summary;
		$mail['dest'] = "prastgtx@gmail.com,owner@adilahsoft.com,norman_ab@gpsandalas.com,zad_anwar@gpsandalas.com"; 
	
		lacakmobilmail($mail);					

	}
	
	function send()
	{
		if ($this->sess->user_type != 1) return;
		
		$xml = sprintf("%s\1%s", $_POST['hp'], $_POST['message']);
		$this->smsmodel->sendsms($xml, 1);
		
	}
	
	function smstoalluser($agentid=0, $interval=2)
	{		
		if ($agentid)
		{
			$this->db->where("agent_id", $agentid);
		}
		$this->db->where("user_status", 1);
		$this->db->join("agent", "agent_id = user_agent");
		$q = $this->db->get("user");
		
		$t = mktime();
		$rows = $q->result();
		
		for($i=0; $i < count($rows); $i++)
		{
			$content = sprintf("u/ mendaftarkan no np tambahan, silahkan ketik REG <login> <no yang didaftarkan>, misal REG abahadilah 0857575757 dan kirim ke no ini. lacak-mobil.com");			
			$hp = valid_mobiles($rows[$i]->user_mobile);
			
			if ($hp === FALSE) continue;
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s", $t);
			$insert["TextDecoded"] = $content;
			$insert["DestinationNumber"] = $hp;
			$insert["UDH"] = "";
						
			$this->sendsms($insert);
			$t += $interval*60; // 10 menit
		}
	}
	
	function smstosupervisor()
	{
		$t = mktime();
		
		$this->db->where("user_type <>", 2);
		$q = $this->db->get("user");
		
		$rowagents = $q->result();		
		for($i=0; $i < count($rowagents); $i++)
		{
			$hp = valid_mobiles($rowagents[$i]->user_mobile);
			if ($hp === FALSE) continue;
			
			$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
			$insert["SendingDateTime"] = date("Y-m-d H:i:s", $t);
			$insert["TextDecoded"] = "Yth Agent. Mohon inform jika melakukan pengisian ulang sim card GPS dengan cara ketik ISIPULSA <nominal pulsa> <no kendaraan>, misal ISIPULSA 20000 B1234CD";
			$insert["DestinationNumber"] = $hp;
			$insert["UDH"] = "";
						
			$this->sendsms($insert);
			$t += 10*60; // 10 menit
			
		}
		
	}
	
	function schedule()
	{
		$smsdb = $this->load->database("sms", TRUE);
		
		$now = date("G");
		
		//$smsdb->where("start <=", $now);
		//$smsdb->where("end >=", $now);
		$smsdb->where("interval >=", 1);
		$smsdb->where("status", 1);
		$q = $smsdb->get("schedule");
		
		if ($q->num_rows() == 0)
		{
			return;
		}
		
		$rows = $q->result();
		for($i=0; $i < count($rows); $i++)
		{
			if (($now < $rows[$i]->start) || ($now > $rows[$i]->end))
			{
				unset($update);
				
				$t = mktime($rows[$i]->start-$rows[$i]->interval, 0, 0, date("n"), date('j'), date("Y"));
				$update['sent'] = date("Y-m-d H:i:s", $t);
				
				$smsdb->where("id", $rows[$i]->id);
				$smsdb->update("schedule", $update);
				
				$nextsending = $t+$rows[$i]->interval*3600;
				printf("%s %s next shedule at %s\r\n", $rows[$i]->login, $rows[$i]->vehicle, date("H:00:00", $nextsending));
				
				continue;
			}

			if ($rows[$i]->sent != "0000-00-00 00:00:00")
			{
					$nextsending = dbmaketime($rows[$i]->sent)+$rows[$i]->interval*3600;
					if (date("G", $nextsending) != $now) 
					{
						if (date("G", $nextsending) < $now)
						{
							unset($update);
							
							$t = mktime(date('G')-$rows[$i]->interval, 0, 0, date("n"), date('j'), date("Y"));
							$update['sent'] = date("Y-m-d H:i:s", $t);
							
							$smsdb->where("id", $rows[$i]->id);
							$smsdb->update("schedule", $update);
						}

						printf("%s %s next shedule at %s\r\n", $rows[$i]->login, $rows[$i]->vehicle, date("H:00:00", $nextsending));
						continue;
					}										
					
			}
			
			printf("PSS %s %s\r\n", $rows[$i]->login, $rows[$i]->vehicle);
			
			if ($rows[$i]->welcome == 0)
			{
				$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
				$insert["SendingDateTime"] = date("Y-m-d H:i:s");
				$insert["TextDecoded"] = sprintf("No Anda telah diaktifkan untuk menerima posisi kend %s setiap %d jam dari jam %02s - %02d", $rows[$i]->vehicle, $rows[$i]->interval, $rows[$i]->start, $rows[$i]->end);
				$insert["DestinationNumber"] = $rows[$i]->hp;
				$insert["UDH"] = "";
				//$insert["CreatorID"] = "retry";
				
				$this->sendsms($insert);
				
			}
			
			unset($insert);
			
			$insert['UpdatedInDB'] = date("Y-m-d H:i:s");
			$insert['ReceivingDateTime'] = date("Y-m-d H:i:s");
			$insert['Coding'] = "Default_No_Compression";
			$insert['SenderNumber'] = $rows[$i]->hp;			
			$insert['TextDecoded'] = sprintf("PSS %s %s", $rows[$i]->login, $rows[$i]->vehicle);				
			$insert['Processed'] = 'false';
			
			$smsdb->insert("inbox", $insert);
			
			unset($update);
			
			$update['sent'] = date("Y-m-d H:i:s");
			$update['welcome'] = 1;
			
			$smsdb->where("id", $rows[$i]->id);
			$smsdb->update("schedule", $update);
		}
	}
	
	function sinkron($tblname)
	{
		$q = $this->db->get($tblname);
		$rows = $q->result_array();
		
		$smsdb = $this->load->database("sms", TRUE);
		$smsdb->truncate($tblname);
		
		for($i=0; $i < count($rows); $i++)
		{
			$smsdb->insert($tblname, $rows[$i]);
		}
	}
	
	function broadcast()
	{
		$smsdb = $this->load->database("sms", TRUE);
		
		$smsdb->where("status", 0);
		$q = $smsdb->get("broadcast");
		
		if ($q->num_rows() == 0) 
		{
			sprintf("%s: tidak ada antrian broadcast\r\n", date("d/m/Y H:i:s"));
			return;
		}
		
		$rows = $q->result();
		for($i=0; $i < count($rows); $i++)
		{
			unset($update);
			
			$update['status'] = 1;
			
			$smsdb->where("id", $rows[$i]->id);
			$smsdb->update("broadcast", $update);
			
			if ($rows[$i]->agent != -1)
			{
				$this->db->where("user_agent", $rows[$i]->agent);
			}

			if ($rows[$i]->usertype != -1)
			{
				$this->db->where("user_type", $rows[$i]->usertype);
			}
			
			$q = $this->db->get("user");	
			
			if ($q->num_rows() == 0) continue;
					
			$rowusers = $q->result();
			
			$t = mktime();
			foreach($rowusers as $user)
			{
				$hp = valid_mobiles($user->user_mobile);				
				if ($hp === FALSE) continue;
				
				unset($insert);
				
				$insert["InsertIntoDB"] = date("Y-m-d H:i:s");
				$insert["SendingDateTime"] = date("Y-m-d H:i:s", $t);
				$insert["TextDecoded"] = $rows[$i]->message;
				$insert["DestinationNumber"] = $hp;
				$insert["UDH"] = "";
				
				$this->sendsms($insert);
				
				$t += $rows[$i]->interval;				
			}
		}
		
	}
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */
