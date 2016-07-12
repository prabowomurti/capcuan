<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Blog_model extends CI_Model
{
	private $blogs_table			= 'blogs';
	
	public function __construct()
	{
		parent::__construct();

		$ci =& get_instance();
	}
	
	public function get_blogs($limit = 100)
	{
		$this->db->where('active', '1');//we just get the active blogs
		$this->db->limit($limit);
		$query = $this->db->get($this->blogs_table);
		
		if ($query->num_rows() > 0)
		{
			return $query->result();
		}
		
		return null;
		
	}
	
	public function get_blog_rss($blog_id)
	{
		$this->db->select('blog_rss');
		$this->db->from($this->blogs_table);
		$this->db->where('id', $blog_id);
		$this->db->where('active', '1');//we just get the active blogs
		
		$query = $this->db->get();
		
		if ($query->num_rows() > 0)
		{
			$row = $query->row();
			return $row->blog_rss;
		}
		
		return null;
	}
	
	public function get_blog_feeds($limit = 100)
	{
		$blogs = $this->get_blogs($limit);
		
		$feeds = array();
		
		foreach ($blogs as $row)
		{
			$feeds[] = $row->blog_rss; 
		}
		
		return $feeds;
	}
	
	/**
	 * Get single row
	 * @param $blog_id int
	 * @return object 
	*/
	public function get_blog($blog_id = 0)
	{
		$this->db->where('id', $blog_id);
		$this->db->where('active', '1');//we just get the active blogs
		$query = $this->db->get($this->blogs_table);
		
		if ($query->num_rows > 0)
		{
			return $query->row();
		}
		
		return null;
	}
	
	/**
	 * Add new blog to database 
	 * @param $blog array
	 * @return insert_id int 
	*/
	public function add($blog)
	{
		$this->db->insert($this->blogs_table, $blog);
		return $this->db->insert_id();
	}
	
	/**
	 * We never delete a row, we deactivate it 
	 * @param $blog_id
	 * @return affected_rows int
	*/
	public function delete($blog_id)
	{
		$this->db->where('id', $blog_id);
		$this->db->where('active', '1');
		$this->db->update($this->blogs_table, array('active'=>'0'));
		
		return $this->db->affected_rows();
		
	}
	
	/**
	 * Update the row of blog 
	 * @param $blog_id
	 * @return affected_rows int
	*/
	public function edit($blog_id, $blog)
	{
		$this->db->where('id', $blog_id);
		$this->db->where('active', '1');
		$this->db->update($this->blogs_table, $blog);
		
		return $this->db->affected_rows();
		
	}
	
	/**
	 * Update 'last_update' field
	 * @param $blog_id
	 * @param $last_update datetime()
	 * @return affected_rows int
	*/
	public function edit_last_update($blog_id, $last_update = '')
	{
		$last_update = $last_update ? $last_update : datetime('Y-m');
		return $this->edit($blog_id, array('last_update'=>$last_update));
	}
	
	/**
	 * Check if blog exists 
	 * @param $blog_id
	 * @return bool
	*/
	public function is_blog_exist($blog_id)
	{
		$this->db->select('1', FALSE);
		$this->db->where('id', $blog_id);
		$this->db->where('active', '1');
		
		$query = $this->db->get($this->blogs_table);
		
		return ($query->num_rows() > 0 ? TRUE : FALSE);
	}
	
	public function get_blog_url($blog_id = 0)
	{
		$blog = $this->get_blog($blog_id);
		
		return $blog->blog_url;
	}
}

/* End of file blog_model.php */
/* Location: ./capcuan/models/auth/blog_model.php */
