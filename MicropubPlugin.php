<?php
namespace Craft;

class MicropubPlugin extends BasePlugin
{
	function getName()
	{
		return Craft::t('Micropub');
	}

	function getVersion()
	{
		return '0.1';
	}

	function getDeveloper()
	{
		return 'Shane Hudson';
	}

	function getDeveloperUrl()
	{
		return 'https://shanehudson.net';
	}
		
	public function registerSiteRoutes()
	{
	    return array(
		'micropub' => array('action' => 'micropub/endpoint/post'),
	    );
	}	
}
