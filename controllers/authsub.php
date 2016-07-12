<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Authsub extends CI_Controller {

	private $path_to_rsa_key = './capcuan/files/myrsakey.pem';

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		
		$this->load->model('option_model');
		
		$this->load->library(array('zend', 'session'));
		
		$this->load->helper(array('url', 'html', 'file'));
	}

	public function index()
	{
		show_404();
		
	}
	
	private function _request_token() 
	{
		
		$this->zend->load('Zend/Crypt/Rsa/Key/Private');
		$this->zend->load('Zend/Oauth/Consumer');

		$CONSUMER_KEY = $this->main_model->get_consumer_key();
		$SCOPE = 'http://www.blogger.com/feeds/';

		$oauthOptions = array(
			'requestScheme' => Zend_Oauth::REQUEST_SCHEME_HEADER,
			'version' => '1.0',
			'consumerKey' => $CONSUMER_KEY,
			'consumerSecret' => new Zend_Crypt_Rsa_Key_Private(read_file($this->path_to_rsa_key)),
			'signatureMethod' => 'RSA-SHA1', 
			'callbackUrl' => 'http://apps.prabowomurti.com/capcuan.php/authsub/access_token',
			'requestTokenUrl' => 'https://www.google.com/accounts/OAuthGetRequestToken',
			'userAuthorizationUrl' => 'https://www.google.com/accounts/OAuthAuthorizeToken',
			'accessTokenUrl' => 'https://www.google.com/accounts/OAuthGetAccessToken'
		);
		
		$consumer = new Zend_Oauth_Consumer($oauthOptions);
		
		$access_token = $this->session->userdata('ACCESS_TOKEN');

		if (empty ($access_token)) {
			$this->session->set_userdata('REQUEST_TOKEN', serialize($consumer->getRequestToken(array('scope' => $SCOPE))));
		}
		
		$approvalUrl = $consumer->getRedirectUrl(array('hd' => 'default'));
		echo "<a href=\"$approvalUrl\">Grant access</a> to your application";
	}
	
	private function _access_token() 
	{
		$access_token = $this->session->userdata('ACCESS_TOKEN');
		$request_token = $this->session->userdata('REQUEST_TOKEN');
		
		$get_variables = $this->input->get();
		
		$this->zend->load('Zend/Crypt/Rsa/Key/Private');
		$this->zend->load('Zend/Oauth/Consumer');

		
		
		$CONSUMER_KEY = $this->main_model->get_consumer_key();
		$SCOPE = 'http://www.blogger.com/feeds/';

		$oauthOptions = array(
			'requestScheme' => Zend_Oauth::REQUEST_SCHEME_HEADER,
			'version' => '1.0',
			'consumerKey' => $CONSUMER_KEY,
			'consumerSecret' => new Zend_Crypt_Rsa_Key_Private(read_file($this->path_to_rsa_key)),
			'signatureMethod' => 'RSA-SHA1', 
			'callbackUrl' => 'http://apps.prabowomurti.com/capcuan.php/authsub/access_token',
			'requestTokenUrl' => 'https://www.google.com/accounts/OAuthGetRequestToken',
			'userAuthorizationUrl' => 'https://www.google.com/accounts/OAuthAuthorizeToken',
			'accessTokenUrl' => 'https://www.google.com/accounts/OAuthGetAccessToken'
		);
		
		$consumer = new Zend_Oauth_Consumer($oauthOptions);
		
		if (empty ($access_token)) {
			if (!empty($get_variables) && !empty($request_token)) {
				$serialized_access_token = serialize($consumer->getAccessToken($get_variables, unserialize($request_token)));
				$this->session->set_userdata('ACCESS_TOKEN', $serialized_access_token);
				$access_token = $this->session->userdata('ACCESS_TOKEN');
			}
		}
		
		//saving to database
		$this->main_model->set_token($access_token);
		
		echo 'Access Token saved successfuly';
		
	}

	public function _request_authsub_token() 
	{
		$this->zend->load('Zend/Gdata/Photos');
		$this->zend->load('Zend/Gdata/AuthSub');

		$next = 'http://apps.prabowomurti.com/capcuan.php/authsub/access_authsub_token/';
		$scope = 'https://picasaweb.google.com/data';
    		$secure = false;
		$session = true;
    		$authSubUrl = Zend_Gdata_AuthSub::getAuthSubTokenUri($next, $scope, $secure, $session);

		echo anchor($authSubUrl, 'Please login to authenticate this application');
	}

	public function _access_authsub_token()
	{
		$this->zend->load('Zend/Gdata/AuthSub');
		
		$token = $this->input->get('token');
		$authsub_token = Zend_Gdata_AuthSub::getAuthSubSessionToken($token);

		$this->option_model->set_authsub_token($authsub_token);
		echo "Success";
	}
	
}

/* End of file authsub.php */
/* Location: ./application/controllers/authsub.php */
