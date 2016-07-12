<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Option_model extends CI_Model
{
	private $options_table	= 'options';
	
	public function __construct()
	{
		parent::__construct();

		$ci =& get_instance();
	}
	
	//Get all options
	public function get_options() 
	{
		$query = $this->db->get($this->options_table);
		
		return $query->num_rows() > 0 ? $query->result() : null;
	}
	
	//Get option by its id
	public function get_option_by_id($option_id = 0)
	{
		$this->db->where('id', $option_id);
		$query = $this->db->get($this->options_table);
		
		return $query->num_rows() > 0 ? $query->row() : null;
	}
	
	//Get only one option on options table
	public function get_option($option_name)
	{
		$this->db->select('option_name, option_value');
		$this->db->where('option_name', $option_name);
		$query = $this->db->get($this->options_table);
		
		if ($query->num_rows() > 0)
		{
			$row = $query->row(); 
			return $row->option_value;
		}
		
		return null;
	}
	
	//Set only one option on options table
	public function set_option($option_name, $value)
	{
		$this->db->set('option_value', $value);
		$this->db->where('option_name', $option_name);
		$this->db->update($this->options_table);
		
		return $this->db->affected_rows();
	}
	
	/**
	 * Edit option value !dangerous!
	 *
	*/
	public function edit($option_id, $option)
	{
		$this->db->where('id', $option_id);
		$this->db->update($this->options_table, $option);
		
		return $this->db->affected_rows();
	}
	
	/**
	 * Set access token
	 *
	*/
	public function set_token($access_token) 
	{
		return $this->set_option('access_token', $access_token);
	}
	
	// Get access_token
	public function get_token()
	{
		return $this->get_option('access_token');
	}
	
	/**
	 * Get consumer key (apps' url)
	 *
	*/
	public function get_consumer_key() 
	{
		return $this->get_option('consumer_key');
	}
	
	/**
	 * Get consumer secret
	 *
	*/
	public function get_consumer_secret() 
	{
		return $this->get_option('consumer_secret');
	}
	
	/**
	 * Set blog_id (ilmukomputerugm2004.blogspot.com)
	 */
	public function set_blog_id ($blog_id)
	{
		return $this->set_option('blog_id', $blog_id);
	}
	
	/**
	 * Get blog_id (ilmukomputerugm2004.blogspot.com)
	 */
	public function get_blog_id ()
	{
		return $this->get_option('blog_id');
	}

	/**
	 * AuthSub Token (different with OAuth Token)
	 */
	public function set_authsub_token($access_authsub_token)
	{
		return $this->set_option('authsub_token', $access_authsub_token);
	}

	/**
	 * Get AuthSub Token (different with OAuth Token)
	 */
	public function get_picasa_authsub_token()
	{
		return $this->get_option('authsub_token');
	}
}

/* End of file option_model.php */
/* Location: ./capcuan/models/auth/option_model.php */
