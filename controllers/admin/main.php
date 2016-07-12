<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Main extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();

		$this->load->helper(array('url', 'html'));
		$this->load->library('tank_auth');
		
		//no access if not logged in
		if (!$this->tank_auth->is_logged_in()) {
			redirect('/auth/login/');
		}
		
	}

	public function index()
	{
		echo 'You are inside administrator menu';
		echo br();
		echo anchor('', 'Home') , ' | ';
		echo anchor('admin/blog/', 'Manage Blogs'), ' | ';
		echo anchor('admin/option/', 'Manage Options'), ' | ';
		echo anchor('auth/logout/', 'Logout');

	}
}

/* End of file main.php */
/* Location: ./application/controllers/main.php */
