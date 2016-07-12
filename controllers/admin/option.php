<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Option extends CI_Controller {

	public function __construct () 
	{
		parent::__construct();
		$this->load->database();
		
		$this->load->model('option_model');
		
		$this->load->library(array('tank_auth', 'parser'));
		$this->load->helper(array('url', 'html', 'file'));
		
		if (!$this->tank_auth->is_logged_in())
		{
			redirect('auth/login');
		}
	}

	public function index()
	{
		//show all options
		$this->load->library('table');
		$this->load->helper('date');
		
		$option_data = $this->option_model->get_options();
		
		$this->table->set_heading('ID', 'Option Name', 'Option Value', 'Manage');
		
		foreach ($option_data as $item)
		{
			$this->table->add_row(
				$item->id,
				$item->option_name, 
				$item->option_value,
				anchor('/admin/option/edit/' . $item->id, 'Edit')
			);
		}
		
		$data['administrator_home_anchor'] = anchor('/admin/', 'Administrator Home');
		$data['option_table'] = $this->table->generate();
		$this->parser->parse('admin/option', $data);
		
		
	}
	
	
	
	public function edit($option_id = 0)
	{
		if (!$option_id)
			redirect('admin/option/');
		
		$option_id = intval($option_id);
		
		//check if the option exists
		$option = $this->option_model->get_option_by_id($option_id);
		if (!$option)
		{
			echo "Option doesn't exist";
			echo br();
			echo anchor('admin/option/', '&laquo; Back');
			die();
		}
			
		$this->load->library('form_validation');
		
		//validation rules
		$this->form_validation->set_rules('id', 'required');
		$this->form_validation->set_rules('option_value', 'Option value', 'required');
		
		if ($this->form_validation->run() === TRUE)
		{
			//validated successfully
			$option_post = $this->input->post();
			unset($option_post['submit']);
			
			if ($this->option_model->edit($option_post['id'], $option_post))
			{
				$data['message'] = 'Option : ' . $option_post['option_name'] . ' has been updated successfully!';
				$option = $this->option_model->get_option_by_id($option_post['id']);
			}
			else 
			{
				$data['message'] = 'Option is not updated';
			}
		}
		else 
		{
			$data['message'] = validation_errors();
		}
		
		$data['option_name'] = $option->option_name;
		
		$data['form_open'] = form_open('admin/option/edit/' . $option_id);
		$data['form_close'] = form_close();
		$data['form_hidden_option_id'] = form_hidden('id', $option->id);
		$data['form_hidden_option_name'] = form_hidden('option_name', $option->option_name);
		$data['form_input_option_value'] = form_input('option_value', $option->option_value);
		$data['form_submit'] = form_submit('submit', 'Update Option');
		
		$data['manage_option_anchor'] = anchor('admin/option/', '&laquo; Back');
		
		$this->parser->parse('admin/option_edit', $data);
	}
}

/* End of file option.php */
/* Location: ./application/controllers/option.php */
