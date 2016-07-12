<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Main extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();

		$this->load->library('tank_auth');
		$this->load->helper(array('url', 'html'));
		
		if (!$this->tank_auth->is_logged_in()) {
			show_404();
		}
	}

	public function index()
	{
		show_404();
	}

}

/* End of file main.php */
/* Location: ./application/controllers/main.php */
