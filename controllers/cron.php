<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Cron extends CI_Controller
{

	private $path_to_rsa_key = './capcuan/files/myrsakey.pem';
	private $path_to_image = './capcuan/files/tmp_images/';

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		

		$this->load->model('option_model');
		$this->load->model('blog_model');
		$this->load->model('image_model');

		$this->load->library(array('session', 'zend'));

		$this->load->helper(array('url', 'html', 'file'));

		/*
		if (!$this->input->is_cli_request())
		{
			log_message('debug', 'Someone with IP : ' . $this->input->ip_address() . ' tried to access your cron');
			die("Nice try, azzhole!");
		}
		 *
		 */
		
	}

	public function index()
	{
		show_404();
	}

	public function action()
	{
		$this->load->library('SimplePie');
		
		//get all RSS inside the blogs table
		$blogs = $this->blog_model->get_blogs();

		$posts = array();
		//loop through all blogs
		foreach ($blogs as $blog)
		{
			//using simplepie to get the feed
			$this->simplepie->set_feed_url($blog->blog_rss);
			$this->simplepie->set_cache_location(APPPATH . 'cache/simplepie');
			$this->simplepie->init();
			$this->simplepie->handle_content_type();
			
			$items = array();
			$items = $this->simplepie->get_items();

			//loop through items/feed
			foreach ($items as $item)
			{
				//is item old?
				$publication_date = $item->get_date('c');
				if (strtotime($publication_date) <= strtotime($blog->last_update))
				{
					//break the loop
					break;
				}

				$title = $item->get_title();
				$description = $item->get_content();

				//here, we try to replace the img's source inside description to our photo inside the Picasa Web Album

				$description.= '<br/><br/>Original post : ' . anchor($item->get_permalink(), $title);
				$author = $blog->blog_owner;

				//saved for later, because we want to order the posts by publication date
				$posts[] = array(
				    $blog->id,		// 0 : id 
				    $publication_date,	// 1 : publication date
				    $title,			// 2 : title
				    $description,		// 3 : description
				    $author);		// 4 : author
			}
		}

		//if posts is not empty
		if (!empty($posts))
		{
			//sort posts based on publication_date
			foreach ($posts as $key => $row)
			{
				$pub_date[$key] = $row[1]; //$row[1] is publication_date
			}
			array_multisort($pub_date, SORT_ASC, $posts);

			//print_r($posts);
			//do post to blogspot
			$today_post = 0;
			foreach ($posts as $post)
			{
				$blog_id = $post[0];
				$last_update = date('Y-m-d H:i:s', strtotime($post[1]));
				log_message('debug', "Last update for $blog_id : $last_update");
				
				//here we go, we will convert all images source to Picasa url
				$body = $post[3];
				log_message('debug', 'Body (before) :' . $body);
				
				$doc = new DOMDocument();
				
				@$doc->loadHTML($body);
				$images = $doc->getElementsByTagName('img');
				
				if (! empty($images))
				{
					//make sure that there is a '/' at the end of hostname
					$host = rtrim(prep_url($this->blog_model->get_blog_url($blog_id)), '/') . '/';
					$author = $post[4];
					$post_title = $post[2];
					
					log_message('debug', 'Host : ' . $host);
					
					foreach ($images as $image)
					{
						$ori_image_source = $image->getAttribute('src');
						log_message('debug', 'Original source : ' . $ori_image_source);
						
						//we need to know if it's a valid image
						if (! $this->is_image($ori_image_source))
						{
							//not a valid image!!
							log_message('debug', $ori_image_source . ' is not a valid image');
							continue;
						}
						
						//fix source url (add http://hostname.com as a prefix)
						$image_source = $this->prep_url($ori_image_source, $host);
						log_message('debug', 'Image source : ' . $image_source);
						if ($this->image_model->is_image_exist($image_source))
						{
							//image already entered to Picasa, call the destination
							$image_destination = $this->image_model->get_image_destination($image_source);
						}
						else 
						{
							//get the image to local disk
							$filename = microtime() . basename($image_source);
							$full_image_path = $this->path_to_image . $filename;
							log_message('debug', 'Full image path : ' . $full_image_path);
							if (write_file($full_image_path, file_get_contents($image_source)))
							{
								//upload to Picasa
								$photoUrl = $this->upload_photo($full_image_path, $author);
								if ($photoUrl)
								{
									$image_destination = $photoUrl;
								}
								else
								{
									log_message('debug', 'Can not upload photo to Picasa');
									continue;
								}
								
								//input it to database
								$this->image_model->add_image($post_title, $image_source, $image_destination);
							}
							else 
							{
								//Can not write to tmp_files
								log_message('debug', 'Can not write to ' . $full_image_path);
								continue;
							}
							
							//delete temporary files
							//delete_files($full_image_path);
						}
						
						//change each src="ori_image_source" to src="image_destination"
						$body = str_replace($ori_image_source, $image_destination, $body);
						log_message('debug', 'Body edited : ' . $body);
						
					}//endforeach ($images...
				}//endif (empty images)
				
				//if post is posted successfully, then change the last_update field
				if ($this->_post_to_blogspot(
						    $post[1], $post[2], $body, $post[4])
				)
				{
					//in case something gone wrong
					log_message('debug', 'Last update for blog ' . $blog_id . ' : ' . $last_update);
					$this->blog_model->edit_last_update($blog_id, $last_update);
				}
				
				//BREAK if there is already 50 posts today
				$today_post ++;
				if ($today_post >= 50)
					break;
			}
		}
	}

	private function _post_to_blogspot(
	$publication_date = '', $title = '', $description = '', $author = '')
	{
		$this->load->library('zend');
		$this->load->helper('security');

		$this->zend->load('Zend/Gdata');
		$this->zend->load('Zend/Crypt/Rsa/Key/Private');
		$this->zend->load('Zend/Oauth/Consumer');

		$CONSUMER_KEY = $this->option_model->get_consumer_key();

		$accessToken = unserialize($this->option_model->get_token());

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

		$httpClient = $accessToken->getHttpClient($oauthOptions);
		$gdClient = new Zend_Gdata($httpClient, 'Muhajirin-Capcuan-v1');

		$blogID = $this->option_model->get_blog_id();

		$uri = 'http://www.blogger.com/feeds/' . $blogID . '/posts/default';
		$entry = $gdClient->newEntry();

		//making an entry
		$entry->title = $gdClient->newTitle(xss_clean($title));
		$entry->content = $gdClient->newContent(xss_clean($description));
		$entry->content->setType('text');
		$publication_date = $publication_date ? $publication_date : date('c');
		$entry->published = $gdClient->newPublished($publication_date);

		//set the label = $author
		$labels[] = $gdClient->newCategory($author, 'http://www.blogger.com/atom/ns#');
		$entry->setCategory($labels);

		//create an entry
		$createdPost = $gdClient->insertEntry($entry, $uri);
		$idText = split('-', $createdPost->id->text);
		$newPostID = $idText[2];

		return $newPostID;
	}

	
	private function upload_photo($filename, $author)
	{
//		$filename = $this->path_to_image . 'sunset.jpg';
//		$author = 'Prabowo Murti';
		
		if (! is_file($filename))
			return FALSE;
		
		$this->load->library('zend');
		$this->zend->load('Zend/Gdata/AuthSub');
		$this->zend->load('Zend/Gdata/Photos');
		$this->zend->load('Zend/Gdata/App/MediaFileSource');

		$authsub_token = $this->option_model->get_picasa_authsub_token();
		$client = Zend_Gdata_AuthSub::getHttpClient($authsub_token);

		$gp = new Zend_Gdata_Photos($client, 'Muhajirin-Capcuan-v1');

		$username = "default";
		
		$photoTags = $author;

		// We use the albumId of 'default' to indicate that we'd like to upload
		// this photo into the 'drop box'.  This drop box album is automatically 
		// created if it does not already exist.
		// Capcuan AlbumId
		$albumId = "123456"; // change this to your album ID

		$fd = $gp->newMediaFileSource($filename);
		$mime_type = image_type_to_mime_type(exif_imagetype($filename));
		log_message('debug', "Content Type for $filename : " . $mime_type);
		$fd->setContentType($mime_type);
		
		// Create a PhotoEntry
		$photoEntry = $gp->newPhotoEntry();

		$photoEntry->setMediaSource($fd);
		$photoTitle = pathinfo($filename);
		$photoEntry->setTitle($gp->newTitle($photoTitle['filename']));

		// add some tags
		$keywords = new Zend_Gdata_Media_Extension_MediaKeywords();
		$keywords->setText($photoTags);
		$photoEntry->mediaGroup = new Zend_Gdata_Media_Extension_MediaGroup();
		$photoEntry->mediaGroup->keywords = $keywords;

		// We use the AlbumQuery class to generate the URL for the album
		$albumQuery = $gp->newAlbumQuery();

		$albumQuery->setUser($username);
		$albumQuery->setAlbumId($albumId);

		// We insert the photo, and the server returns the entry representing
		// that photo after it is uploaded
		$insertedEntry = $gp->insertPhotoEntry($photoEntry, $albumQuery->getQueryUrl());
		$mediaContent = $insertedEntry->getMediaGroup()->getContent();
		$photo_url = $mediaContent[0]->getUrl();
		
		return $photo_url;
	}

	private function retrieve_photos()
	{
		$this->zend->load('Zend/Gdata/AuthSub');
		$this->zend->load('Zend/Gdata/Photos');

		$authsub_token = $this->option_model->get_picasa_authsub_token();
		$client = Zend_Gdata_AuthSub::getHttpClient($authsub_token);

		$gp = new Zend_Gdata_Photos($client, 'Muhajirin-Capcuan-v1');
		$query = $gp->newAlbumQuery();

		$query->setUser('default');
		$query->setAlbumName('CapCuan');

		$albumFeed = $gp->getAlbumFeed($query);
		foreach ($albumFeed as $albumEntry)
		{
			$albumEntry->title->text . "<br /> \n";
		}
	}
	
	/**
	 * Check if filename is a valid image (we don't care about .ico file)
	 * @param type $filename
	 * @return type 
	 */
	private function is_image($filename = '')
	{
		$pathinfo = pathinfo($filename);
		
		return empty($pathinfo['extension']) ? FALSE : preg_match('/^(gif|png|jpg|jpeg|bmp)$/', $pathinfo['extension']);
	}
	
	/**
	 * Add htp://example.com/ for image url
	 * @param type $string 
	 */
	private function prep_url($string = '', $host = '')
	{
		if ($string == 'http://' OR $string == '')
		{
			return '';
		}

		$url = parse_url($string);

		if ( ! $url OR ! isset($url['scheme']))
		{
			$string = $host . ltrim($string, '/');
		}

		return $string;
	}
	
}

/* End of file cron.php */
/* Location: ./application/controllers/cron.php */