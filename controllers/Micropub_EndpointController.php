<?php
namespace Craft;
require dirname(__DIR__) . '/vendor/autoload.php';

class Micropub_EndpointController extends BaseController  {
	protected $allowAnonymous = array('actionPost');	

	public function actionPost()  {
//		$templatesPath = craft()->path->getPluginsPath().'micropub/templates/';
//		craft()->path->setTemplatesPath($templatesPath);

//		$this->renderTemplate('_endpoint.html');

		$auth = $this->auth();
		if ($auth)  {
			echo "yay";
			$this->savePost();
		}
		else  {
			echo "Didn't auth";
		}	
	}

	private function auth()  {

		$mysite = "http://$_SERVER[HTTP_HOST]"; // Change this to your website.
		$token_endpoint = 'https://tokens.indieauth.com/token';


		// blatantly stolen from https://github.com/idno/Known/blob/master/Idno/Pages/File/View.php#L25
		if (!function_exists('getallheaders')) {
		  function getallheaders()
		  {
		    $headers = '';
		    foreach ($_SERVER as $name => $value) {
		      if (substr($name, 0, 5) == 'HTTP_') {
			$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
		      }
		    }
		    return $headers;
		  }
		}


		# Written by Jeremy Keith
		
		# Licensed under a CC0 1.0 Universal (CC0 1.0) Public Domain Dedication
		# http://creativecommons.org/publicdomain/zero/1.0/

		$_HEADERS = array();
		foreach(getallheaders() as $name => $value) {
		    $_HEADERS[$name] = $value;
		}

		if (!isset($_HEADERS['Authorization'])) {
		    header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
		    echo 'Missing "Authorization" header.';
		    exit;
		}
		if (!isset($_POST['h'])) {
		    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
		    echo 'Missing "h" value.';
		    exit;
		}

		$options = array(
		    CURLOPT_URL => $token_endpoint,
		    CURLOPT_HTTPGET => TRUE,
		    CURLOPT_USERAGENT => $mysite,
		    CURLOPT_TIMEOUT => 5,
		    CURLOPT_RETURNTRANSFER => TRUE,
		    CURLOPT_HEADER => FALSE,
		    CURLOPT_HTTPHEADER => array(
			'Content-type: application/x-www-form-urlencoded',
			'Authorization: '.$_HEADERS['Authorization']
		    )
		);

		$curl = curl_init();
		curl_setopt_array($curl, $options);
		$source = curl_exec($curl);
		curl_close($curl);

		parse_str($source, $values);

		if (!isset($values['me'])) {
		    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
		    echo 'Missing "me" value in authentication token.';
		    exit;
		}
		if (!isset($values['scope'])) {
		    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
		    echo 'Missing "scope" value in authentication token.';
		    exit;
		}
		if (substr($values['me'], -1) != '/') {
		    $values['me'].= '/';
		}
		if (substr($mysite, -1) != '/') {
		    $mysite.= '/';
		}
		if (strtolower($values['me']) != strtolower($mysite)) {
		    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
		    echo 'Mismatching "me" value in authentication token.';
		    exit;
		}
		if (!stristr($values['scope'], 'post')) {
		    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
		    echo 'Missing "post" value in "scope".';
		    exit;
		}
		if (!isset($_POST['content'])) {
		    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
		    echo 'Missing "content" value.';
		    exit;
		}
		
		return true;
		/* Everything's cool. Do something with the $_POST variables
		   (such as $_POST['content'], $_POST['category'], $_POST['location'], etc.)
		   e.g. create a new entry, store it in a database, whatever. */
	}

	private function savePost()  {
		
		$sectionId = 2;
		$typeId = 3;
		$entry = new EntryModel();
		$entry->sectionId = $sectionId;
		$entry->typeId = $typeId; 	
		$entry->authorId = 1; // 1 for Admin
		$entry->enabled = true;
		$entry->setContentFromPost(array(
			'title' => 'note',
			'body' => $_POST['content'],
			//'the_url' => $_POST['source'],
		));

		$success = craft()->entries->saveEntry($entry); 	
		if (!$success)  {
			echo "Could not save micropub post";
		}
		else  {
			header('HTTP/1.1 201 Created');
			header('Location: ' . $entry->url);	
		}
	}
}
