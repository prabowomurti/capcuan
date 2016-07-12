<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Image_model extends CI_Model
{
	private $image_table	= 'images';
	
	public function __construct()
	{
		parent::__construct();

		$ci =& get_instance();
	}
	
	//Get all options
	public function get_images() 
	{
		$query = $this->db->get($this->image_table);
		
		return $query->num_rows() > 0 ? $query->result() : null;
	}
	
	/**
	 * Add new image to database
	 */
	public function add_image ($post_title, $image_source = '', $image_destination = '')
	{
		$image = array();
		$image['post_title'] = $post_title;
		$image['image_source'] = $image_source;
		$image['image_destination'] = $image_destination;
		$image['created_time'] = date('Y-m-d H:i:s');
		
		$this->db->insert($this->image_table, $image);
		return $this->db->insert_id();
	}
	
	/**
	 * Check whether an image is already in the database
	 * @param type $image_source 
	 */
	public function is_image_exist($image_source = '')
	{
		$this->db->select('1', FALSE);
		$this->db->where('image_source', $image_source);
		
		$query = $this->db->get($this->image_table);
		
		return ($query->num_rows() > 0 ? TRUE : FALSE);
	}
	
	/**
	 * Get image destination on Picasa album
	 * @param type $image_source 
	 */
	public function get_image_destination($image_source = '')
	{
		$this->db->where('image_source', $image_source);
		$query = $this->db->get($this->image_table);
		
		if ($query->num_rows() > 0)
		{
			$result = $query->row();
			$image_destination = $result->image_destination;
		}
		else 
		{
			$image_destination = null;
		}
		
		return $image_destination;
	}
	
}

/* End of file image_model.php */
/* Location: ./capcuan/models/image_model.php */
