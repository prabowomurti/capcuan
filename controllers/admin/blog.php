<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Blog extends CI_Controller {

	public function __construct () 
	{
		parent::__construct();
		$this->load->database();
		
		$this->load->model('blog_model');
		
		$this->load->library(array('tank_auth', 'parser'));
		$this->load->helper(array('url', 'html', 'file'));
		
		if (!$this->tank_auth->is_logged_in())
		{
			redirect('auth/login');
		}
	}

	public function index()
	{
		//show all blogs
		$this->load->library('table');
		$this->load->helper('date');
		
		$blog_data = $this->blog_model->get_blogs();
		
		$this->table->set_heading('ID', 'Owner', 'Title', 'Feed URL', 'Last Update', 'Manage');
		
		foreach ($blog_data as $item)
		{
			$this->table->add_row(
				$item->id,
				$item->blog_owner,
				anchor($item->blog_url, $item->blog_title),
				anchor($item->blog_rss, 'RSS'),
				strtolower(current(explode(',', timespan(mysql_to_unix($item->last_update)), 2))) . ' ago',
				anchor('/admin/blog/edit/' . $item->id, 'Edit') . ' | ' . anchor('/admin/blog/delete/' . $item->id, 'Delete')
			);
		}
		
		$data['blog_add_new_anchor'] = anchor('/admin/blog/add', 'Add New Blog');
		$data['blog_table'] = $this->table->generate();
		$this->parser->parse('admin/blog', $data);
		
		
	}
	
	public function add()
	{
		$this->load->library('form_validation');
		
		$this->form_validation->set_rules('blog_owner', 'Blog Owner', 'required');
		$this->form_validation->set_rules('blog_title', 'Blog Title', 'required');
		$this->form_validation->set_rules('blog_url', 'Blog URL', 'required|prep_url');
		$this->form_validation->set_rules('blog_rss', 'Blog RSS',  'required|prep_url');
		
		if ($this->form_validation->run() === TRUE)
		{
			$blog = $this->input->post();
			unset($blog['submit']);
			
			if ($blog_id = $this->blog_model->add($blog))
			{
				$data['message'] = 'New blog : ' . $this->input->post('blog_title') . ' has been added successfully!';
				log_message('debug', 'Blog ID '. $blog_id);
			}
			else 
			{
				$data['message'] = 'Can not add new blog';
			}
			
		}
		else 
		{
			$data['message'] = validation_errors();
		}
		
		$data['form_open'] = form_open('admin/blog/add');
		$data['form_close'] = form_close();
		
		$data['form_input_blog_owner'] = form_input('blog_owner', set_value('blog_owner'));
		$data['form_input_blog_title'] = form_input('blog_title', set_value('blog_title'));
		$data['form_input_blog_url'] = form_input('blog_url', set_value('blog_url'));
		$data['form_input_blog_rss'] = form_input('blog_rss', set_value('blog_rss'));
		$data['form_submit'] = form_submit('submit', 'Add New Blog');
		
		$data['manage_blog_anchor'] = anchor('admin/blog/', '&laquo; Back');
		
		$this->parser->parse('admin/blog_add', $data);
	}
	
	public function edit($blog_id = 0)
	{
		if (!$blog_id)
			redirect('admin/blog/add');
		
		$blog_id = intval($blog_id);
		
		//check if the blog exists
		$blog = $this->blog_model->get_blog($blog_id);
		if (!$blog)
		{
			echo "Blog doesn't exist";
			echo br();
			echo anchor('admin/blog/', '&laquo; Back');
			die();
		}
			
		$this->load->library('form_validation');
		
		//validation rules
		$this->form_validation->set_rules('id', 'required');
		$this->form_validation->set_rules('blog_owner', 'Blog Owner', 'required');
		$this->form_validation->set_rules('blog_title', 'Blog Title', 'required');
		$this->form_validation->set_rules('blog_url', 'Blog URL', 'required|prep_url');
		$this->form_validation->set_rules('blog_rss', 'Blog RSS',  'required|prep_url');
		
		if ($this->form_validation->run() === TRUE)
		{
			//validated successfully
			$blog_post = $this->input->post();
			unset($blog_post['submit']);
			
			if ($this->blog_model->edit($blog_post['id'], $blog_post))
			{
				$data['message'] = 'Blog : ' . $blog_post['blog_title'] . ' has been updated successfully!';
				$blog = $this->blog_model->get_blog($blog_post['id']);
			}
			else 
			{
				$data['message'] = 'Blog is not updated';
			}
		}
		else 
		{
			$data['message'] = validation_errors();
		}
		
		$data['form_open'] = form_open('admin/blog/edit/' . $blog_id);
		$data['form_close'] = form_close();
		
		$data['form_hidden_blog_id'] = form_hidden('id', $blog->id);
		$data['form_input_blog_owner'] = form_input('blog_owner', $blog->blog_owner);
		$data['form_input_blog_title'] = form_input('blog_title', $blog->blog_title);
		$data['form_input_blog_url'] = form_input('blog_url', $blog->blog_url);
		$data['form_input_blog_rss'] = form_input('blog_rss', $blog->blog_rss);
		$data['form_submit'] = form_submit('submit', 'Update Blog');
		
		$data['manage_blog_anchor'] = anchor('admin/blog/', '&laquo; Back');
		
		$this->parser->parse('admin/blog_edit', $data);
	}
	
	public function delete($blog_id = 0)
	{
		$blog_id = intval($blog_id);
		
		if ($this->blog_model->is_blog_exist($blog_id))
		{
			if ($this->blog_model->delete($blog_id))
				redirect('admin/blog');
		}
		
		echo anchor('admin/blog/', '&laquo; Back');
		echo br();
		die("Can not delete blog");
	}
}

/* End of file blog.php */
/* Location: ./application/controllers/blog.php */
