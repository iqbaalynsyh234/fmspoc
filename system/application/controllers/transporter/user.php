<?php
include "base.php";

class User extends Base {

	function User()
	{
		parent::Base();

		$this->load->model("gpsmodel");
		$this->load->model("vehiclemodel");
		$this->load->helper("email");
		$this->load->helper("common");

		if (! isset($this->sess->user_type))
		{
			redirect(base_url());
		}
	}

	function savepass($id)
	{
		$oldpass = isset($_POST['oldpass']) ? trim($_POST['oldpass']) : "";
		$pass = isset($_POST['pass']) ? trim($_POST['pass']) : "";
		$cpass = isset($_POST['cpass']) ? trim($_POST['cpass']) : "";

		if ($this->sess->user_type == 2)
		{
			if (strlen($oldpass) == 0)
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('lempty_olpassword');

				echo json_encode($callback);
				return;
			}

			$sql = "SELECT * FROM ".$this->db->dbprefix."user WHERE user_pass = PASSWORD('".$oldpass."') AND (user_id = '".$id."')";
			$q = $this->db->query($sql);

			if ($q->num_rows() == 0)
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('linvalid_olpassword');

				echo json_encode($callback);
				return;
			}
		}

		if (strlen($pass) == 0)
		{
			$callback['error'] = true;
			$callback['message'] = $this->lang->line('lerror_empty_userpass');

			echo json_encode($callback);
			return;
		}

		if (strlen($pass) < 6)
		{
			$callback['error'] = true;
			$callback['message'] = $this->lang->line('lpassword_too_short');

			echo json_encode($callback);
			return;
		}

		if ($pass != $cpass)
		{
			$callback['error'] = true;
			$callback['message'] = $this->lang->line('linvalid_cpass');

			echo json_encode($callback);
			return;
		}

		$sql = "UPDATE ".$this->db->dbprefix."user SET user_pass = PASSWORD('".$pass."') WHERE user_id = '".$id."'";

		$callback['error'] = false;
		$callback['message'] = $this->lang->line('lchangepassword_success');

		$this->db->query($sql);
		echo json_encode($callback);
	}

	function changepass($id)
	{
		if (isset($this->sess->user_manage_password))
		{
			if (! $this->sess->user_manage_password)
			{
				redirect(base_url());
			}
		}

		$this->db->where("user_id", $id);
		$q = $this->db->get("user");

		if ($q->num_rows() == 0)
		{
			$callback['error'] = true;
			echo json_encode($callback);
			return;
		}

		$row = $q->row();

		$params['row'] = $row;
		$html = $this->load->view("user/changepass", $params, true);

		$callback['html'] = $html;
		$callback['error'] = false;

		echo json_encode($callback);
	}

	function search()
	{
			$vreplaces = $this->config->item('vehicle_type_replace');
			$this->db->where("vehicle_company", $this->sess->user_company);
			$this->db->order_by("vehicle_no", "asc");
			$q = $this->db->get("vehicle");

			$rowcompanyvehicles = $q->result();

			for($i=0; $i < count($rowcompanyvehicles); $i++)
			{
				$arr = explode("@", $rowcompanyvehicles[$i]->vehicle_device);

				$rowcompanyvehicles[$i]->vehicle_device_name = (count($arr) > 0) ? $arr[0] : "";
				$rowcompanyvehicles[$i]->vehicle_device_host = (count($arr) > 1) ? $arr[1] : "";
				$rowcompanyvehicles[$i]->isnewport = isset($vreplaces) && isset($vreplaces[$rowcompanyvehicles[$i]->vehicle_type]);

				if (! isset($vcompaniesid))
				{
					$vcompanies[$rowcompanyvehicles[$i]->vehicle_company][] = $rowcompanyvehicles[$i];
				}
				else
				if (! isset($vcompaniesid[$rowcompanyvehicles[$i]->vehicle_company]))
				{
					$vcompaniesid[$rowcompanyvehicles[$i]->vehicle_company][] = $rowcompanyvehicles[$i]->vehicle_id;
				}
				else
				if (! in_array($rowcompanyvehicles[$i]->vehicle_id, $vcompaniesid[$rowcompanyvehicles[$i]->vehicle_company]))
				{
					$vcompanies[$rowcompanyvehicles[$i]->vehicle_company][] = $rowcompanyvehicles[$i];
				}

				$vcompaniesid[$rowcompanyvehicles[$i]->vehicle_company][] = $rowcompanyvehicles[$i]->vehicle_id;
				if ($rowcompanyvehicles[$i]->vehicle_group == 0) continue;

				$vgroups[$rowcompanyvehicles[$i]->vehicle_company][$rowcompanyvehicles[$i]->vehicle_group][] = $rowcompanyvehicles[$i];
			}

			// groups

			$groups = array();
			$grpprocessed = array();

			//$this->getAllGroups(0, &$groups, &$grpprocessed);

			$this->db->where("group_company", $this->sess->user_company);
			$this->db->join("group", "group_id = user_group");
			$q = $this->db->get("user");
			$rowusers = $q->result();
			//print_r($rowusers);

			for($i=0; $i < count($rowusers); $i++)
			{
				$childs = array();
				$this->vehiclemodel->getChilds($groups, $rowusers[$i]->user_group, &$childs);

				for($j=0; $j < count($childs); $j++)
				{
					if (! isset($vgroups[$rowusers[$i]->group_company][$childs[$j]])) continue;

					foreach($vgroups[$rowusers[$i]->group_company][$childs[$j]] as $val)
					{
						$vgroups[$rowusers[$i]->group_company][$rowusers[$i]->user_group][] = $val;
					}
				}
			}

		$offset = isset($_POST['offset']) ? $_POST['offset'] : 0;
		$field = isset($_POST['field']) ? $_POST['field'] : "";
		$keyword = isset($_POST['keyword']) ? $_POST['keyword'] : "";
		$vtype = isset($_POST['vehicle_type']) ? $_POST['vehicle_type'] : "";
		$type = isset($_POST['type']) ? $_POST['type'] : "";
		$status = isset($_POST['status']) ? $_POST['status'] : "";
		$sortby = isset($_POST['sortby']) ? $_POST['sortby'] : "user_login";
		$orderby = isset($_POST['orderby']) ? $_POST['orderby'] : "asc";
		$company = isset($_POST['company']) ? $_POST['company'] : 0;
		$groupid = isset($_POST['group']) ? $_POST['group'] : 0;

		$this->db->order_by($sortby, $orderby);

		switch($field)
		{
			case "user_login":
				$this->db->where("user_login LIKE '%".$keyword."%'", null);
				$this->db->where("user_company", $this->sess->user_company);
			break;
			case "user_name":
				$this->db->where("user_name LIKE '%".$keyword."%'", null);
				$this->db->where("user_company", $this->sess->user_company);
			break;
			case "vehicle":
				$this->db->distinct();
				$this->db->select("user.*, agent.*");
				$this->db->join("vehicle", "user_id = vehicle_user_id");
				$this->db->where("(UPPER(vehicle_no) LIKE '%".strtoupper($keyword)."%' OR UPPER(vehicle_name) LIKE '%".strtoupper($keyword)."%')", null);
				$this->db->where("vehicle_user_id", $this->sess->user_id);
			break;
			default:
				$this->db->where("user_company", $this->sess->user_company);
		}

		$this->db->where("user_id <>", $this->sess->user_id);
		$this->db->join("agent", "agent_id = user_agent", "left outer");
		$q = $this->db->get("user", $this->config->item("limit_records"), $offset);
		$rows = $q->result();
		//print_r($rows);

		// jumlah all vehicle

		if ($this->config->item('vehicle_type_fixed'))
		{
			$this->db->where("vehicle_type",  $this->config->item('vehicle_type_fixed'));
		}

		switch ($field)
		{
			case "user_login":
				$this->db->where("user_login LIKE '%".$keyword."%'", null);
				$this->db->where("user_company", $this->sess->user_company);
			break;
			case "user_name":
				$this->db->where("user_name LIKE '%".$keyword."%'", null);
				$this->db->where("user_company", $this->sess->user_company);
			break;
			case "vehicle":
				$this->db->where("(UPPER(vehicle_no) LIKE '%".strtoupper($keyword)."%' OR UPPER(vehicle_name) LIKE '%".strtoupper($keyword)."%')", null);
				$this->db->where("vehicle_user_id", $this->sess->user_id);
			break;
			default:
				$this->db->where("user_company", $this->sess->user_company);
		}


		$this->db->distinct();
		$this->db->select("vehicle_device");
		$this->db->where("user_id <>", $this->sess->user_id);
		$this->db->join("user", "vehicle_user_id = user_id");
		$this->db->join("agent", "agent_id = user_agent", "left outer");
		$q = $this->db->get("vehicle");

		$totalvehicle = $q->num_rows();

		for($i=0; $i < count($rows); $i++)
		{
			if (! $rows[$i]->user_group) continue;
			if (! isset($vgroups[$rows[$i]->user_company][$rows[$i]->user_group])) continue;

			$totalvehicle += count($vgroups[$rows[$i]->user_company][$rows[$i]->user_group]);
		}

		// vehicle

		$userids = array(0);
		for($i=0; $i < count($rows); $i++)
		{
			$userids[] = $rows[$i]->user_id;
		}

		if ($field == "vexpired")
		{
				$this->db->where("vehicle_active_date2 <", date("Ymd"));
		}
		else
		if ($field == "vactive")
		{
				$this->db->where("vehicle_active_date2 >=", date("Ymd"));
		}
		else
		if ($field == "vehicle")
		{
			$this->db->where("(UPPER(vehicle_no) LIKE '%".strtoupper($keyword)."%' OR UPPER(vehicle_name) LIKE '%".strtoupper($keyword)."%')", null);
		}
		else
		if ($field == "device")
		{
			$this->db->where("vehicle_device LIKE", '%'.$keyword.'%');
		}
		else
		if ($field == "vehicle_type")
		{
			$this->db->where("vehicle_type LIKE", '%'.$vtype.'%');
		}
		else
		if ($field == "vvisible")
		{
				$this->db->where("vehicle_status", 1);
		}
		else
		if ($field == "vehicle_card_no")
		{
			$this->db->where("vehicle_card_no LIKE", '%'.$keyword.'%');
		}

		$this->db->order_by("vehicle_name", "asc");
		$this->db->order_by("vehicle_no", "asc");

		$this->db->where_in("vehicle_user_id", $userids);

		if ($this->config->item('vehicle_type_fixed'))
		{
			$this->db->where("vehicle_type",  $this->config->item('vehicle_type_fixed'));
		}

		$q = $this->db->get("vehicle");

		$vreplaces = $this->config->item('vehicle_type_replace');

		$rowvehicles = $q->result();


		for($i=0; $i < count($rowvehicles); $i++)
		{
			$arr = explode("@", $rowvehicles[$i]->vehicle_device);

			$rowvehicles[$i]->vehicle_device_name = (count($arr) > 0) ? $arr[0] : "";
			$rowvehicles[$i]->vehicle_device_host = (count($arr) > 1) ? $arr[1] : "";
			$rowvehicles[$i]->isnewport = isset($vreplaces) && isset($vreplaces[$rowvehicles[$i]->vehicle_type]);

			$vehicles[$rowvehicles[$i]->vehicle_user_id][] = $rowvehicles[$i];
		}

		for($i=0; $i < count($rows); $i++)
		{
			if ($rows[$i]->user_company)
			{
				if ($rows[$i]->user_group)
				{
					$rows[$i]->vehicles = isset($vgroups[$rows[$i]->user_company][$rows[$i]->user_group]) ? $vgroups[$rows[$i]->user_company][$rows[$i]->user_group] : array();
				}
				else
				{
					$rows[$i]->vehicles = isset($vcompanies[$rows[$i]->user_company]) ? $vcompanies[$rows[$i]->user_company] : array();
				}
			}
			else
			{
				$rows[$i]->vehicles = isset($vehicles[$rows[$i]->user_id]) ? $vehicles[$rows[$i]->user_id] :array();
			}
		}

		switch($field)
		{
			case "user_login":
				$this->db->where("user_login LIKE '%".$keyword."%'", null);
				$this->db->where("user_company", $this->sess->user_company);
			break;
			case "user_name":
				$this->db->where("user_name LIKE '%".$keyword."%'", null);
				$this->db->where("user_company", $this->sess->user_company);
			break;
			case "vehicle":
				$this->db->join("vehicle", "vehicle_user_id = user_id");
				$this->db->where("(UPPER(vehicle_no) LIKE '%".strtoupper($keyword)."%' OR UPPER(vehicle_name) LIKE '%".strtoupper($keyword)."%')", null);
				$this->db->where("vehicle_user_id", $this->sess->user_id);
			break;
			default:
				$this->db->where("user_id <>", $this->sess->user_id);
				$this->db->where("user_company", $this->sess->user_company);
		}

		$this->db->where("user_id <>", $this->sess->user_id);
		$q = $this->db->get("user");
		$total = $q->num_rows();
		$this->load->library("pagination1");

		$config['uri_segment'] = 3;
		$config['total_rows'] = $total;
		$config['per_page'] = $this->config->item("limit_records");

		$this->pagination1->initialize($config);

		$this->params["paging"] = $this->pagination1->create_links();
		$this->params["offset"] = $offset;
		$this->params["total"] = $total;
		$this->params["data"] = $rows;
		$this->params['sortby'] = $sortby;
		$this->params['orderby'] = $orderby;

		$html = $this->load->view('user/result', $this->params, true);

		$callback['html'] = $html;
		$callback['total'] = $total;
		$callback['totalvehicle'] = $totalvehicle;

		echo json_encode($callback);
	}

	function index($offset=0)
	{

		/* if ($this->sess->user_type == 2)
		{
			if (! $this->sess->user_company)
			{
				redirect(base_url());
			}

			if ($this->sess->user_group)
			{
				redirect(base_url());
			}
		} */

		$this->db->where("company_id", $this->sess->user_company);
		$this->db->order_by("company_name", "asc");

		/* if ($this->sess->user_type == 3)
		{
			$this->db->where("company_agent", $this->sess->user_agent);
		} */

		$q = $this->db->get("company");

		$this->params['companies'] = $q->result();
		$this->params['sortby'] = "user_login";
		$this->params['orderby'] = "asc";
		$this->params['title'] = $this->lang->line('luser_list').", ".$this->lang->line('llist_trackers');

		$this->params["content"] = $this->load->view('user/list', $this->params, true);
		$this->load->view("templatesess", $this->params);
	}

	function add($id=0)
	{
		if ($id)
		{
			$this->db->where("user_id", $id);
			$this->db->join("agent", "agent_id = user_agent", "left outer");
			$q = $this->db->get("user");

			if ($q->num_rows() == 0)
			{
				redirect(base_url());
			}

			$row = $q->row();

			if ($row->user_birth_date)
			{
				$t = dbintmaketime($row->user_birth_date, 0);
				$row->user_date_fmt = date("d/m/Y", $t);
			}
			else
			{
				$row->user_date_fmt = "";
			}

			$this->params['row'] = $row;

			// get vehicle

			$this->db->where("vehicle_user_id", $id);
			$q = $this->db->get("vehicle");

			$rowvehicles = $q->result();
			for($i=0; $i < count($rowvehicles); $i++)
			{
				$rowvehicles[$i]->expire_date1 = $rowvehicles[$i]->vehicle_active_date1 ? inttodate($rowvehicles[$i]->vehicle_active_date1) : "";
				$rowvehicles[$i]->expire_date2 = $rowvehicles[$i]->vehicle_active_date2 ? inttodate($rowvehicles[$i]->vehicle_active_date2) : "";
				$rowvehicles[$i]->expire_date = $rowvehicles[$i]->vehicle_active_date ? inttodate($rowvehicles[$i]->vehicle_active_date) : "";
			}

			$this->params['vehicles'] = $rowvehicles;
			$this->params['title'] = $this->lang->line('luser_edit').", ".$this->lang->line('lupdate_vehicle');
		}
		else
		{
			if (($this->sess->user_type == 3) && ($this->sess->user_agent_admin == 0))
			{
				redirect(base_url());
			}


			$this->params['title'] = $this->lang->line('luser_add').", ".$this->lang->line('ladd_vehicle');
		}

		if ($this->sess->user_type == 3)
		{
			$this->db->where("agent_id", $this->sess->user_agent);
		}

		$this->db->order_by("agent_name");
		$q = $this->db->get("agent");

		$rowagents = $q->result();
		$this->db->where("company_id", $this->sess->user_company);
		$this->db->order_by("company_name", "asc");
		$q = $this->db->get("company");

		$rowcompanies = $q->result();

		$this->params["companies"] = $rowcompanies;
		$this->params["agents"] = $rowagents;
		$this->params["content"] = $this->load->view('user/form', $this->params, true);
		$this->load->view("templatesess", $this->params);
	}

	function saveuser()
	{
		$name = isset($_POST['license']) ? trim($_POST['name']) : "";
		$license = isset($_POST['license']) ? trim($_POST['license']) : "";
		$sex = isset($_POST['sex']) ? trim($_POST['sex']) : "";
		$birthdate = isset($_POST['birthdate']) ? trim($_POST['birthdate']) : "";
		$province = isset($_POST['province']) ? trim($_POST['province']) : "";
		$city = isset($_POST['city']) ? trim($_POST['city']) : "";
		$address = isset($_POST['address']) ? trim($_POST['address']) : "";
		$mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : "";
		$phone = isset($_POST['phone']) ? trim($_POST['phone']) : "";
		$email = isset($_POST['email']) ? trim($_POST['email']) : "";
		$agent_admin = isset($_POST['agent_admin']) ? trim($_POST['agent_admin']) : 0;

		if (strlen($email))
		{
			if (! valid_email($email))
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('linvalid_email');

				echo json_encode($callback);
				return;
			}
		}

		if (strlen($birthdate) > 0)
		{
			$tbirthdate = formmaketime($birthdate." 00:00:00");

			if (date("d/m/Y", $tbirthdate) != $birthdate)
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('linvalid_birthdate');

				echo json_encode($callback);
				return;
			}

		}

		unset($data);

		$data['user_mail'] = $email;
		$data['user_name'] = $name;
		$data['user_license_id'] = $license;
		$data['user_license_type'] = 'A';
		$data['user_sex'] = $sex;
		$data['user_birth_date'] = isset($tbirthdate) ? date("Ymd", $tbirthdate) : 0;
		$data['user_province'] = $province;
		$data['user_city'] = $city;
		$data['user_address'] = $address;
		$data['user_mobile'] = $mobile;
		$data['user_phone'] = $phone;
		$data['user_agent_admin'] = $agent_admin;

		$mydb = $this->load->database("master", TRUE);

		$mydb->where("user_id", $this->sess->user_id);
		$mydb->update("user", $data);

		/* //Update database lacakmobil
		$mydblacak = $this->load->database("masterlacak", TRUE);
		$mydblacak->where("user_id", $this->sess->user_id);
		$mydblacak->update("user", $data); */

		$this->db->cache_delete_all();

		$callback['error'] = false;
		$callback['message'] = $this->lang->line("luser_updated");
		$callback['redirect'] = base_url()."user/add/".$this->sess->user_id;

		echo json_encode($callback);
	}

	function save()
	{

		$id = isset($_POST['id']) ? $_POST['id'] : 0;
		$username = isset($_POST['username']) ? trim($_POST['username']) : "";
		$pass = isset($_POST['pass']) ? trim($_POST['pass']) : "";
		$cpass = isset($_POST['cpass']) ? trim($_POST['cpass']) : "";
		$type = isset($_POST['type']) ? trim($_POST['type']) : "";
		$agent = isset($_POST['agent']) ? trim($_POST['agent']) : "";
		$name = isset($_POST['license']) ? trim($_POST['name']) : "";
		$license = isset($_POST['license']) ? trim($_POST['license']) : "";
		$sex = isset($_POST['sex']) ? trim($_POST['sex']) : "";
		$birthdate = isset($_POST['birthdate']) ? trim($_POST['birthdate']) : "";
		$province = isset($_POST['province']) ? trim($_POST['province']) : "";
		$city = isset($_POST['city']) ? trim($_POST['city']) : "";
		$address = isset($_POST['address']) ? trim($_POST['address']) : "";
		$mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : "";
		$phone = isset($_POST['phone']) ? trim($_POST['phone']) : "";
		$email = isset($_POST['email']) ? trim($_POST['email']) : "";
		$usersite = isset($_POST['usersite']) ? trim($_POST['usersite']) : 0;
		$group = isset($_POST['group']) ? trim($_POST['group']) : "";
		$agent_admin = isset($_POST['agent_admin']) ? trim($_POST['agent_admin']) : 0;
		$manengine = isset($_POST['manengine']) ? trim($_POST['manengine']) : 0;
		$manpasswd = isset($_POST['manpasswd']) ? trim($_POST['manpasswd']) : 0;
		$manprofile = isset($_POST['manprofile']) ? trim($_POST['manprofile']) : 1;
		$user_payment_type = 0;
		$user_payment_period = 0;

		$user_payment_amount = 0;
		//$user_payment_amount = str_replace(",", "", $user_payment_amount);

		$user_payment_pulsa = 0;

		if (strlen($username) == 0)
		{
			$callback['error'] = true;
			$callback['message'] = $this->lang->line("lempty_login");

			echo json_encode($callback);
			return;
		}

		if (preg_match("/\s+/", $username))
		{
			$callback['error'] = true;
			$callback['message'] = $this->lang->line("linvalid_login");

			echo json_encode($callback);
			return;
		}

		$this->db->where("user_login", $username);
		$q = $this->db->get("user");

		if ($q->num_rows() > 0)
		{
			$rowuser = $q->row();
			if ($rowuser->user_id != $id)
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line("lexist_login");

				echo json_encode($callback);
				return;
			}
		}

		if (! $id)
		{

			if (strlen($pass) == 0)
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line("lempty_password");

				echo json_encode($callback);
				return;
			}

			if (strlen($pass) < 6)
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line("lpassword_too_short");

				echo json_encode($callback);
				return;
			}

			if ($cpass != $pass)
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line("linvalid_cpass");

				echo json_encode($callback);
				return;
			}
		}

		/*if ($type != 1)
		{
			if (strlen($agent) == 0)
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line("lempty_agent");

				echo json_encode($callback);
				return;
			}
		} */

		if (strlen($email))
		{
			if (! valid_email($email))
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('linvalid_email');

				echo json_encode($callback);
				return;
			}
		}

		if (strlen($birthdate) > 0)
		{
			$tbirthdate = formmaketime($birthdate." 00:00:00");

			if (date("d/m/Y", $tbirthdate) != $birthdate)
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('linvalid_birthdate');

				echo json_encode($callback);
				return;
			}

		}

		/* if ($type == 2)
		{
			 if (! $user_payment_type)
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('lempty_payment_type');

				echo json_encode($callback);
				return;
			}

			if ((! is_numeric($user_payment_period)) || ($user_payment_period <= 0))
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('linvalid_payment_period');

				echo json_encode($callback);
				return;
			}

			 if ((! is_numeric($user_payment_amount)) || ($user_payment_amount < 0))
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('linvalid_payment_amount');

				echo json_encode($callback);
				return;
			}
		}  */

		unset($data);

		$data['user_mail'] = $email;
		$data['user_name'] = $name;
		$data['user_license_id'] = $license;
		$data['user_license_type'] = 'A';
		$data['user_sex'] = $sex;
		$data['user_birth_date'] = isset($tbirthdate) ? date("Ymd", $tbirthdate) : 0;
		$data['user_province'] = $province;
		$data['user_city'] = $city;
		$data['user_address'] = $address;
		$data['user_mobile'] = $mobile;
		$data['user_phone'] = $phone;
		$data['user_type'] = $type;
		$data['user_agent'] = ($type == 1) ? 0 : $agent;
		$data['user_login'] = $username;
		$data['user_agent_admin'] = $agent_admin;
		$data['user_engine'] = $manengine;
		$data['user_manage_password'] = $manpasswd;
		$data['user_company'] = $usersite;
		$data['user_group'] = $group;
		$data['user_change_profile'] = $manprofile;
		$data['user_payment_type'] = $user_payment_type;
		$data['user_payment_period'] = $user_payment_period;
		$data['user_payment_amount'] = $user_payment_amount;
		$data['user_payment_pulsa'] = $user_payment_pulsa;

		if ($id)
		{
			$mydb = $this->load->database("master", TRUE);

			$mydb->where("user_id", $id);
			$mydb->update("user", $data);

			/* //Update database lacakmobil
			$mydblacak = $this->load->database("masterlacak", TRUE);
			$mydblacak->where("user_id", $id);
			$mydblacak->update("user", $data); */

			$this->db->cache_delete_all();

			$callback['error'] = false;
			$callback['message'] = $this->lang->line("luser_updated");
			$callback['redirect'] = base_url()."user";

			echo json_encode($callback);

			return;
		}

		$data['user_pass'] = $pass;
		$data['user_lastlogin_date'] = 0;
		$data['user_lastlogin_time'] = 0;
		$data['user_photo'] = "";
		$data['user_zipcode'] = "";
		$data['user_status'] = 1;
		$data['user_create_date'] = date("Ymd");

		$mydb = $this->load->database("master", TRUE);

		$mydb->insert("user", $data);

		$userid = $mydb->insert_id();

		$sql = "UPDATE ".$this->db->dbprefix."user SET user_pass = PASSWORD('".mysql_escape_string($pass)."') WHERE user_id = '".$userid."'";

		$this->db->query($sql);

		$this->db->cache_delete_all();

		$callback['error'] = false;
		$callback['message'] = $this->lang->line("luser_added");
		$callback['redirect'] = base_url()."user";

		echo json_encode($callback);
		return;
	}

	function remove()
	{
		$id = $this->uri->segment(4);
		//print_r($id);exit;
		$this->db->where("user_id", $id);
		$q = $this->db->get("user");

		if ($q->num_rows() == 0)
		{
			redirect(base_url());
		}

		$row = $q->row();

		$mydb = $this->load->database("master", TRUE);

		$mydb->where("user_id", $row->user_id);
		$mydb->delete("user");

		$this->db->cache_delete_all();

		redirect(base_url()."user");
	}

	function status($id)
	{
		if ($this->sess->user_type == 2)
		{
			redirect(base_url());
		}

		if ($this->sess->user_type == 3)
		{
			$this->db->where("user_agent", $this->sess->user_agent);
		}
		$this->db->where("user_id", $id);
		$q = $this->db->get("user");

		if ($q->num_rows() == 0)
		{
			redirect(base_url());
		}

		$row = $q->row();

		$status = ($row->user_status == 1) ? 2 : 1;

		unset($data);
		$data['user_status'] = $status;

		$mydb = $this->load->database("master", TRUE);

		$mydb->where("user_id", $id);
		$mydb->update("user", $data);

		$this->db->cache_delete_all();

		redirect(base_url()."user/");
	}

	function savevehicle($isman=0)
	{
		$vehicle_id = isset($_POST['vehicle_id']) ? trim($_POST['vehicle_id']) : "";

			$vehicleids = $this->vehiclemodel->getVehicleIds();

			if (! in_array($vehicle_id, $vehicleids))
			{
				redirect(base_url());
			}

		$vehicle_user_id = isset($_POST['vehicle_user_id']) ? trim($_POST['vehicle_user_id']) : "";
		$vehicle_device = isset($_POST['vehicle_device']) ? trim($_POST['vehicle_device']) : "";
		$vehicle_type = isset($_POST['vehicle_type']) ? trim($_POST['vehicle_type']) : "";
		$vehicle_no = isset($_POST['vehicle_no']) ? trim($_POST['vehicle_no']) : "";
		$vehicle_name = isset($_POST['vehicle_name']) ? trim($_POST['vehicle_name']) : "";

		$vehicle_card_no = isset($_POST['vehicle_card_no']) ? trim($_POST['vehicle_card_no']) : "";
		$vehicle_card_no = str_replace(" ", "", $vehicle_card_no);

		$vehicle_operator = isset($_POST['vehicle_operator']) ? trim($_POST['vehicle_operator']) : "";

		$vehicle_maxspeed = isset($_POST['vehicle_maxspeed']) ? trim($_POST['vehicle_maxspeed']) : "";
		$vehicle_maxspeed = str_replace(",", ".", $vehicle_maxspeed);

		$vehicle_maxparking = isset($_POST['vehicle_maxparking']) ? trim($_POST['vehicle_maxparking']) : "";
		$vehicle_maxparking = str_replace(",", ".", $vehicle_maxparking);

		$vehicle_odometer = isset($_POST['vehicle_odometer']) ? trim($_POST['vehicle_odometer']) : 0;
		$vehicle_odometer = str_replace(",", ".", $vehicle_odometer);

		$vehicle_image = isset($_POST['vehicle_image']) ? trim($_POST['vehicle_image']) : "";
		$vehicle_group = isset($_POST['group']) ? trim($_POST['group']) : "";
		$vehicle_company = isset($_POST['usersite']) ? trim($_POST['usersite']) : 0;

		$driver_id = isset($_POST['driver']) ? trim($_POST['driver']) : "";

			if (strlen($vehicle_device) == 0)
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('lempty_vehicle_device');
				echo json_encode($callback);
				return;
			}

			if ($vehicle_id)
			{
				$this->db->where("vehicle_id <>", $vehicle_id);
			}

			$this->db->where("vehicle_status <>", 3);
			$this->db->where("vehicle_device", $vehicle_device);
			$total = $this->db->count_all_results("vehicle");

			if ($total)
			{
				/* $callback['error'] = true;
				$callback['message'] = $this->lang->line('lexist_vehicle_device');

				echo json_encode($callback);
				return; */
			}


		if (strlen($vehicle_no) == 0)
		{
			$callback['error'] = true;
			$callback['message'] = $this->lang->line('lempty_vehicle_no');

			echo json_encode($callback);
			return;
		}

		if ($vehicle_id)
		{
			$this->db->where("vehicle_id <>", $vehicle_id);
		}

		$this->db->where("vehicle_status <>", 3);
		$this->db->where("vehicle_no", $vehicle_no);
		$total = $this->db->count_all_results("vehicle");
		if ($total)
		{
			/* $callback['error'] = true;
			$callback['message'] = $this->lang->line('lexist_vehicle_no');

			echo json_encode($callback);
			return; */
		}

		if (strlen($vehicle_name) == 0)
		{
			$callback['error'] = true;
			$callback['message'] = $this->lang->line('lempty_vehicle_name');

			echo json_encode($callback);
			return;
		}

		if (strlen($vehicle_odometer))
		{
			if ((! is_numeric($vehicle_odometer)) || ($vehicle_odometer < 0))
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('linvalid_initialodometer');

				echo json_encode($callback);
				return;
			}
		}

		if (strlen($vehicle_maxspeed))
		{
			if ((! is_numeric($vehicle_maxspeed)) || ($vehicle_maxspeed < 0))
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('linvalid_maxspeed');

				echo json_encode($callback);
				return;
			}
		}

		if (strlen($vehicle_maxparking))
		{
			if ((! is_numeric($vehicle_maxparking)) || ($vehicle_maxparking < 0))
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('linvalid_maxparkingtime');

				echo json_encode($callback);
				return;
			}
		}

			if (strlen($vehicle_card_no) == 0)
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('lvehicle_card_no_empty');

				echo json_encode($callback);
				return;
			}

			$this->db->where("vehicle_status", 1);
			$this->db->where("vehicle_card_no", $vehicle_card_no);
			$q = $this->db->get("vehicle");

			if ($q->num_rows() > 0)
			{
				$rowsimcard = $q->row();
				if ($rowsimcard->vehicle_id != $vehicle_id)
				{
					/* $callback['error'] = true;
					$callback['message'] = $this->lang->line('lvehicle_card_no_exist');

					echo json_encode($callback);
					return; */
				}
			}

		//Khusus Tupperware Transporter
		if ($this->sess->user_trans_tupper == 1)
		{
			$booking_id = $this->cek_booking_id($vehicle_device);
		}

		unset($data);

			$data['vehicle_status'] = 1;
			if ($this->sess->user_trans_tupper == 1)
			{
				if ($booking_id == "false")
				{
					$data['vehicle_group'] = $vehicle_group;
					$data['vehicle_image'] = $vehicle_image;
				}
			}
			else
			{
				$data['vehicle_group'] = $vehicle_group;
				$data['vehicle_image'] = $vehicle_image;
			}
			$data['vehicle_company'] = $vehicle_company;
			$data['vehicle_no'] = $vehicle_no;
			$data['vehicle_name'] = $vehicle_name;
			$data['vehicle_maxspeed'] = $vehicle_maxspeed;
			$data['vehicle_maxparking'] = $vehicle_maxparking;
			$data['vehicle_odometer'] = $vehicle_odometer;
			$this->db->where("vehicle_id", $vehicle_id);
			$this->db->update("vehicle", $data);

		//UPdate Driver
		$app_route = $this->config->item("app_route");
		if (isset($app_route) && $app_route ==1)
		{
		}
		else
		{
			$driver_update = $this->update_driver($vehicle_id, $driver_id);
			//Add History
			if ($driver_id != 0)
			{
				$history_driver = $this->driver_history($vehicle_id, $vehicle_name, $vehicle_no, $driver_id);
			}
		}

		$this->db->cache_delete_all();

		$callback['message'] = $this->lang->line('lvehicle_updated');
		$callback['error'] = false;
		echo json_encode($callback);

	}

	function savevehicle_tag($isman=0)
	{
		$vehicle_id = isset($_POST['vehicle_id']) ? trim($_POST['vehicle_id']) : "";

			$vehicleids = $this->vehiclemodel->getVehicleIds();

			if (! in_array($vehicle_id, $vehicleids))
			{
				redirect(base_url());
			}

		$vehicle_user_id = isset($_POST['vehicle_user_id']) ? trim($_POST['vehicle_user_id']) : "";
		$vehicle_device = isset($_POST['vehicle_device']) ? trim($_POST['vehicle_device']) : "";
		$vehicle_type = isset($_POST['vehicle_type']) ? trim($_POST['vehicle_type']) : "";
		$vehicle_no = isset($_POST['vehicle_no_old']) ? trim($_POST['vehicle_no_old']) : "";
		$vehicle_name = isset($_POST['vehicle_name_old']) ? trim($_POST['vehicle_name_old']) : "";

		$vehicle_card_no = isset($_POST['vehicle_card_no']) ? trim($_POST['vehicle_card_no']) : "";
		$vehicle_card_no = str_replace(" ", "", $vehicle_card_no);

		$vehicle_operator = isset($_POST['vehicle_operator']) ? trim($_POST['vehicle_operator']) : "";

		$vehicle_maxspeed = isset($_POST['vehicle_maxspeed']) ? trim($_POST['vehicle_maxspeed']) : "";
		$vehicle_maxspeed = str_replace(",", ".", $vehicle_maxspeed);

		$vehicle_maxparking = isset($_POST['vehicle_maxparking']) ? trim($_POST['vehicle_maxparking']) : "";
		$vehicle_maxparking = str_replace(",", ".", $vehicle_maxparking);

		$vehicle_odometer = isset($_POST['vehicle_odometer']) ? trim($_POST['vehicle_odometer']) : 0;
		$vehicle_odometer = str_replace(",", ".", $vehicle_odometer);

		$vehicle_image = isset($_POST['vehicle_image']) ? trim($_POST['vehicle_image']) : "";
		$vehicle_group = isset($_POST['group']) ? trim($_POST['group']) : "";
		$vehicle_company = isset($_POST['usersite']) ? trim($_POST['usersite']) : 0;

		//new condition
		$vehicle_no_old = isset($_POST['vehicle_no_old']) ? trim($_POST['vehicle_no_old']) : "";
		$vehicle_name_old = isset($_POST['vehicle_name_old']) ? trim($_POST['vehicle_name_old']) : "";

		$vehicle_name_new = isset($_POST['vehicle_name_new']) ? trim($_POST['vehicle_name_new']) : "";
		$vehicle_lm_new = isset($_POST['vehicle_lm_new']) ? trim($_POST['vehicle_lm_new']) : "";
		$vehicle_no_new = isset($_POST['vehicle_no_new']) ? trim($_POST['vehicle_no_new']) : "";

		$driver_id = isset($_POST['driver']) ? trim($_POST['driver']) : "";

			if (strlen($vehicle_device) == 0)
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('lempty_vehicle_device');
				echo json_encode($callback);
				return;
			}

			if ($vehicle_id)
			{
				$this->db->where("vehicle_id <>", $vehicle_id);
			}

			$this->db->where("vehicle_status <>", 3);
			$this->db->where("vehicle_device", $vehicle_device);
			$total = $this->db->count_all_results("vehicle");

			if ($total)
			{
				/* $callback['error'] = true;
				$callback['message'] = $this->lang->line('lexist_vehicle_device');

				echo json_encode($callback);
				return; */
			}


		if (strlen($vehicle_no) == 0)
		{
			$callback['error'] = true;
			$callback['message'] = $this->lang->line('lempty_vehicle_no');

			echo json_encode($callback);
			return;
		}

		if ($vehicle_id)
		{
			$this->db->where("vehicle_id <>", $vehicle_id);
		}

		$this->db->where("vehicle_status <>", 3);
		$this->db->where("vehicle_no", $vehicle_no);
		$total = $this->db->count_all_results("vehicle");
		if ($total)
		{
			/* $callback['error'] = true;
			$callback['message'] = $this->lang->line('lexist_vehicle_no');

			echo json_encode($callback);
			return; */
		}

		if (strlen($vehicle_name) == 0)
		{
			$callback['error'] = true;
			$callback['message'] = $this->lang->line('lempty_vehicle_name');

			echo json_encode($callback);
			return;
		}

		if (strlen($vehicle_odometer))
		{
			if ((! is_numeric($vehicle_odometer)) || ($vehicle_odometer < 0))
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('linvalid_initialodometer');

				echo json_encode($callback);
				return;
			}
		}

		if (strlen($vehicle_maxspeed))
		{
			if ((! is_numeric($vehicle_maxspeed)) || ($vehicle_maxspeed < 0))
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('linvalid_maxspeed');

				echo json_encode($callback);
				return;
			}
		}

		if (strlen($vehicle_maxparking))
		{
			if ((! is_numeric($vehicle_maxparking)) || ($vehicle_maxparking < 0))
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('linvalid_maxparkingtime');

				echo json_encode($callback);
				return;
			}
		}

			if (strlen($vehicle_card_no) == 0)
			{
				$callback['error'] = true;
				$callback['message'] = $this->lang->line('lvehicle_card_no_empty');

				echo json_encode($callback);
				return;
			}

			$this->db->where("vehicle_status", 1);
			$this->db->where("vehicle_card_no", $vehicle_card_no);
			$q = $this->db->get("vehicle");

			if ($q->num_rows() > 0)
			{
				$rowsimcard = $q->row();
				if ($rowsimcard->vehicle_id != $vehicle_id)
				{
					/* $callback['error'] = true;
					$callback['message'] = $this->lang->line('lvehicle_card_no_exist');

					echo json_encode($callback);
					return; */
				}
			}

		//Khusus Tupperware Transporter
		if ($this->sess->user_trans_tupper == 1)
		{
			$booking_id = $this->cek_booking_id($vehicle_device);
		}

		unset($data);

			$data['vehicle_status'] = 1;
			if ($this->sess->user_trans_tupper == 1)
			{
				if ($booking_id == "false")
				{
					//$data['vehicle_group'] = $vehicle_group;
					$data['vehicle_image'] = $vehicle_image;
				}
			}
			else
			{
				//$data['vehicle_group'] = $vehicle_group;
				$data['vehicle_image'] = $vehicle_image;
			}

			if($vehicle_no_new != ""){
				$data['vehicle_no'] = $vehicle_lm_new." ".$vehicle_no_new;
			}else{
				$data['vehicle_no'] = $vehicle_no_old;
			}
			if($vehicle_name_new != ""){
				$data['vehicle_name'] = $vehicle_name_new;
			}else{
				$data['vehicle_name'] = $vehicle_name_old;
			}

			$data['vehicle_company'] = $vehicle_company;
			$data['vehicle_maxspeed'] = $vehicle_maxspeed;
			$data['vehicle_maxparking'] = $vehicle_maxparking;
			$data['vehicle_odometer'] = $vehicle_odometer;
			$this->db->where("vehicle_id", $vehicle_id);
			$this->db->update("vehicle", $data);

		//UPdate Driver
		$app_route = $this->config->item("app_route");
		if (isset($app_route) && $app_route ==1)
		{
		}
		else
		{
			$driver_update = $this->update_driver($vehicle_id, $driver_id);
			//Add History
			if ($driver_id != 0)
			{
				$history_driver = $this->driver_history($vehicle_id, $vehicle_name, $vehicle_no, $driver_id);
			}
		}

		$this->db->cache_delete_all();

		$callback['message'] = $this->lang->line('lvehicle_updated');
		$callback['error'] = false;
		echo json_encode($callback);

	}

	function formvehicle()
	{

		$vehicleids = $this->vehiclemodel->getVehicleIds();
		$vid = isset($_POST['id']) ? $_POST['id'] : "";
		$uid = isset($_POST['uid']) ? $_POST['uid'] : "";

		$params['uid'] = $uid;

		if ($vid)
		{

			if ($this->sess->user_type == 2)
			{
				$this->db->where_in("vehicle_id", $vehicleids);
			}
			$this->db->where("vehicle_id", $vid);
			$this->db->join("user", "user_id = vehicle_user_id");
			$q = $this->db->get("vehicle");

			if ($q->num_rows() == 0)
			{
				$callback['error'] = true;
				echo json_encode($callback);
				return;
			}

			$row = $q->row();

			$row->vehicle_active_date1_t = dbintmaketime($row->vehicle_active_date1, 0);
			$row->vehicle_active_date2_t = dbintmaketime($row->vehicle_active_date2, 0);
			$row->vehicle_active_date_t = dbintmaketime($row->vehicle_active_date, 0);

			$json = json_decode($row->vehicle_info);
			$row->vehicle_ip = isset($json->vehicle_ip) ? $json->vehicle_ip : $this->config->item("ip_colo");


			$params['vehicle'] = $row;
			$params['owner'] = $row->vehicle_user_id;
		}
		else
		{
			$params['owner'] = $uid;
		}

		if ($this->sess->user_type == 2)
		{
			$this->db->where("user_id", $this->sess->user_id);
		}

		$this->db->order_by("user_name", "asc");
		$q = $this->db->get("user");

		$params["users"] = $q->result();

        //Get Company
		//$this->db->where("company_id", $this->sess->user_company);
        $this->db->where("company_id = '".$this->sess->user_company."' OR company_created_by = '".$this->sess->user_id."'");
		$this->db->order_by("company_name", "asc");
		$q = $this->db->get("company");


		$rowcompanies = $q->result();
        //print_r($rowcompanies);exit;

		$params["companies"] = $rowcompanies;

		$this->db->distinct();
		$this->db->select("fuel_tank_capacity");
		$qfuel = $this->db->get("fuel");

		if($qfuel->num_rows()>0){
			$rfuel = $qfuel->result();

			$params['fuel'] = $rfuel;
		}

		//Get Driver

        $rows_driver = $this->getAllDriver();
		$params["drivers"] = $rows_driver;
		$html = $this->load->view("user/formvehicle", $params, true);
		$callback['error'] = false;
		$callback['html'] = $html;
		echo json_encode($callback);
	}

	function changeport($id)
	{
		$this->db->where("vehicle_id", $id);
		$q = $this->db->get("vehicle");

		if ($q->num_rows() == 0)
		{
			$callback['error'] = true;
			$callback['message'] = "Access denied.";
			echo json_encode($callback);
			return;
		}

		$row = $q->row();

		$vreplaces = $this->config->item('vehicle_type_replace');
		if (! isset($vreplaces[$row->vehicle_type]))
		{
			$callback['error'] = true;
			$callback['message'] = "Access denied.";
			echo json_encode($callback);
			return;
		}

		unset($update);

		$update['vehicle_type'] = $vreplaces[$row->vehicle_type];

		$mydb = $this->load->database("master", TRUE);
		$mydb->where("vehicle_id", $id);
		$mydb->update("vehicle", $update);

		$this->db->cache_delete_all();

		$callback['error'] = false;
		$callback['message'] = $this->lang->line('lchangeport_success');
		echo json_encode($callback);
	}

	function reqinfo()
	{

		$this->db->where("user_id", $this->sess->user_id);
		$q = $this->db->get("user");

		$row = $q->row();

		$this->params['row'] = $row;

		$lang = $this->config->item("session_lang") ? $this->config->item("session_lang") : $this->config->item("language");

		$this->params['header'] = $this->load->view('user/'.$lang.'/reqinfoheader', $this->params, true);
		$callback['title'] = $this->lang->line('lreq_info');
		$callback['html'] = $this->load->view('user/reqinfo', $this->params, true);

		echo json_encode($callback);
	}

	function cekreqinfo()
	{
		$this->db->where("user_id", $this->sess->user_id);
		$q = $this->db->get("user");

		$row = $q->row();

		$hp = valid_mobiles($row->user_mobile);
		$callback['iscomplete'] = (strlen($row->user_mail) > 0) && ($hp !== FALSE) && (strlen($row->user_address) > 0);

		echo json_encode($callback);
	}

	function savereqinfo()
	{
		$mail = isset($_POST['email']) ? trim($_POST['email']) : "";
		$mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : "";
		$mobile = valid_mobiles($mobile);
		$address = isset($_POST['address']) ? trim($_POST['address']) : "";

		if (! $mail)
		{
			$callback['error'] = true;
			$callback['message'] = $this->lang->line('lempty_email');
			echo json_encode($callback);

			return;
		}

		if (! valid_email($mail))
		{
			$callback['error'] = true;
			$callback['message'] = $this->lang->line('linvalid_email');
			echo json_encode($callback);

			return;
		}

		if ($mobile !== FALSE)
		{
			$callback['error'] = true;
			$callback['message'] = $this->lang->line('linvalid_mobile');
			echo json_encode($callback);

			return;
		}

		if (! $address)
		{
			$callback['error'] = true;
			$callback['message'] = $this->lang->line('lempty_address');
			echo json_encode($callback);

			return;
		}

		unset($data);
		$data['user_mail'] = $mail;
		$data['user_mobile'] = $mobile;
		$data['user_address'] = $address;

		$mydb = $this->load->database("master", TRUE);

		$mydb->where("user_id", $this->sess->user_id);
		$mydb->update("user", $data);

		$this->db->cache_delete_all();

		$callback['error'] = false;
		echo json_encode($callback);
	}

	function getAllGroups($parent=0, $groups, $grpprocessed)
	{
		$this->db->where("group_company", $this->sess->user_company);
		$this->db->where("group_status", 1);
		$this->db->where("group_parent", $parent);
		$q = $this->db->get("group");

		$rows = $q->result();
		for($i=0; $i < count($rows); $i++)
		{
			if (in_array($rows[$i]->group_id, $grpprocessed)) continue;

			$grpprocessed[] = $rows[$i]->group_id;
			$groups[$rows[$i]->group_id] = array();

			$this->getAllGroups($rows[$i]->group_id, &$groups[$rows[$i]->group_id], &$grpprocessed);
		}
	}

    function getAllDriver()
    {
        $this->dbtransporter = $this->load->database('transporter', true);
		$this->dbtransporter->select("*");
		$this->dbtransporter->where("driver_company", $this->sess->user_company);
		$this->dbtransporter->from("driver");
		$qdriver = $this->dbtransporter->get();
		$qrow = $qdriver->result();
        return $qrow;
        $this->dbtransporter->close();
    }

	function update_driver($vehicle_id, $driver_id) {
		$this->dbtransporter = $this->load->database("transporter", true);

		//unset($driver_update);

		 if ($driver_id == 0) {

			 $driver_update['driver_vehicle'] = 0;
			 $this->dbtransporter->where("driver_vehicle", $vehicle_id);
			 $this->dbtransporter->update('driver', $driver_update);
		 }
		 else {

			$driver_update['driver_vehicle'] = $vehicle_id;
			$this->dbtransporter->where("driver_id", $driver_id);
			$this->dbtransporter->update('driver', $driver_update);
		}

		$this->dbtransporter->close();
	}

	function driver_history($vehicle_id, $vehicle_name, $vehicle_no, $driver_id)
	{
		$this->dbtransporter = $this->load->database("transporter", true);
		$date_hist = date("d-m-Y H:i:s");
		unset($data);
		$data['driver_hist_company'] = $this->sess->user_company;
		$data['driver_hist_vehicle'] = $vehicle_id;
		$data['driver_hist_vehicle_name'] = $vehicle_name;
		$data['driver_hist_vehicle_no'] = $vehicle_no;
		$data['driver_hist_driver'] = $driver_id;
		$data['driver_hist_date'] = $date_hist;
		$this->dbtransporter->insert("hist_driver", $data);
		$this->dbtransporter->close();
	}

	function cek_booking_id($v)
	{
		$my_r = "";
		$this->dbtransporter = $this->load->database("transporter", true);
		$this->dbtransporter->where("booking_vehicle",$v);
		$this->dbtransporter->where("booking_status",1);
		$this->dbtransporter->where("booking_delivery_status",1);
		$qb = $this->dbtransporter->get("id_booking");
		$rb = $qb->result();
		$tb = count($rb);
		if ($tb > 0)
		{
			$my_r = "true";
		}
		else
		{
			$my_r = "false";
		}
		return $my_r;

	}
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */
