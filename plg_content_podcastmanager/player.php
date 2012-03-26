<?php
/**
 * Podcast Manager for Joomla!
 *
 * @package     PodcastManager
 * @subpackage  plg_content_podcastmanager
 *
 * @copyright   Copyright (C) 2011-2012 Michael Babker. All rights reserved.
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 *
 * Podcast Manager is based upon the ideas found in Podcast Suite created by Joe LeBlanc
 * Original copyright (c) 2005 - 2008 Joseph L. LeBlanc and released under the GPLv2 license
 */

defined('_JEXEC') or die;

/**
 * Podcast Manager player builder.
 *
 * @package     PodcastManager
 * @subpackage  plg_content_podcastmanager
 * @since       1.6
 */
class PodcastManagerPlayer
{
	/**
	 * The type of player being rendered
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $playerType = 'player';

	/**
	 * The title of the podcast being processed
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $podtitle = null;

	/**
	 * The URL of the file being processed
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $fileURL = null;

	/**
	 * Podcast Manager component parameters
	 *
	 * @var    JRegistry
	 * @since  1.6
	 */
	protected $podmanparams = null;

	/**
	 * Podcast Manager Content Plugin parameters
	 *
	 * @var    JRegistry
	 * @since  2.0
	 */
	protected $pluginParams = null;

	/**
	 * The server file path to the file being processed
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $podfilepath = null;

	/**
	 * The podcast's ID
	 *
	 * @var    integer
	 * @since  2.0
	 */
	protected $podcastID = null;

	/**
	 * An array of valid player types
	 *
	 * @var    array
	 * @since  1.6
	 */
	protected $validTypes = array('custom', 'link', 'player');

	/**
	 * An array of valid file types
	 *
	 * @var    array
	 * @since  1.6
	 */
	protected $fileTypes = array(
		'm4a' => 'audio/x-m4a',
		'm4v' => 'video/x-m4v',
		'mov' => 'video/quicktime',
		'mp3' => 'audio/mpeg',
		'mp4' => 'video/mp4'
	);

	/**
	 * The class constructor
	 *
	 * @param   JRegistry  $podmanparams  The Podcast Manager parameters
	 * @param   string     $podfilepath   The path to the file being processed
	 * @param   string     $podtitle      The title of the podcast being processed
	 * @param   string     $playerType    The type of player to use
	 * @param   integer    $podcastID     The type of player to use
	 * @param   JRegistry  $pluginParams  The Podcast Manager Content Plugin parameters
	 *
	 * @since   1.6
	 * @throws  RuntimeException
	 */
	public function __construct($podmanparams, $podfilepath, $podtitle, $playerType, $podcastID, $pluginParams)
	{
		$this->podmanparams = $podmanparams;
		$this->podfilepath = $podfilepath;
		$this->podcastID = $podcastID;
		$this->pluginParams = $pluginParams;

		if (in_array($playerType, $this->validTypes))
		{
			$this->playerType = $playerType;
		}
		else
		{
			throw new RuntimeException('Invalid Player', 500);
		}

		$this->fileURL = $this->determineURL($podfilepath);
		$this->podtitle = $podtitle;
	}

	/**
	 * Function to generate the player
	 *
	 * @return  object  The player for the article
	 *
	 * @since   1.6
	 */
	public function generate()
	{
		$func = $this->playerType;

		return $this->$func();
	}

	/**
	 * Function to create the URL for a podcast episode file
	 *
	 * @param   object  $podfilepath  The filename of the podcast file.
	 *
	 * @return  string  The URL to the file
	 *
	 * @since   1.6
	 */
	protected function determineURL($podfilepath)
	{
		// Convert the file path to a string
		$tempfile = $podfilepath;

		if (isset($tempfile->filename))
		{
			$filepath = $tempfile->filename;
		}
		else
		{
			$filepath = $tempfile;
		}

		$filename = $filepath;

		// Check if the file is from off site
		if (!preg_match('/^http/', $filename))
		{
			// The file is stored on site, check if it exists
			$filepath = JPATH_ROOT . '/' . $filename;

			// Check if the file exists
			if (JFile::exists($filepath))
			{
				$filename = JURI::base() . $filename;
			}
		}

		return $filename;
	}

	/**
	 * Function to generate a custom player
	 *
	 * @return  object  A link to the podcast as defined by the user
	 *
	 * @since   1.7
	 */
	protected function custom()
	{
		$linkcode = $this->podmanparams->get('customcode', '');
		return preg_replace('/\{podcast\}/', $this->fileURL, $linkcode);
	}

	/**
	 * Function to generate a link player
	 *
	 * @return  object  A HTML link to the podcast
	 *
	 * @since   1.6
	 */
	protected function link()
	{
		return '<a href="' . $this->fileURL . '">' . htmlspecialchars($this->podmanparams->get('linktitle', 'Listen Now!')) . '</a>';
	}

	/**
	 * Function to generate a media player
	 *
	 * @return  object  A media player containing the podcast episode
	 *
	 * @since   1.6
	 */
	protected function player()
	{
		// Player height and width
		$width = $this->podmanparams->get('playerwidth', 400);
		$audioheight = $this->podmanparams->get('playerheight', 15);
		$videoheight = $this->podmanparams->get('videoheight', 400);

		// Valid extensions to determine correct player
		$validAudio = array('m4a', 'mp3');
		$validVideo = array('m4v', 'mov', 'mp4');

		// Get the file's extension
		$extension = strtolower(substr($this->fileURL, -3, 3));

		// Set the element's ID
		$ID = 'player-' . $this->podcastID;

		// Process audio file
		if (in_array($extension, $validAudio))
		{
			$player = '<audio src="' . $this->fileURL . '" id="' . $ID . '" height="' . $audioheight . '" width="' . $width . '" controls="controls" preload="none">';
		}
		// Process video file
		elseif (in_array($extension, $validVideo))
		{
			$player = '<video src="' . $this->fileURL . '" id="' . $ID . '" height="' . $videoheight . '" width="' . $width . '" controls="controls" preload="none">';
		}
		// Invalid file type
		else
		{
			throw new RuntimeException('Invalid File Type', 500);
		}

		// Add the media
		$document = JFactory::getDocument();

		// Check if we should load jQuery
		if ($this->pluginParams->get('loadJQuery', '1') == '1')
		{
			$document->addScript('http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js');
		}

		// Ensure jQuery.noConflict() is set, just in case ;-)
		JHtml::script('mediaelements/jquery-noconflict.js', false, true);

		// And finally, load in MediaElements.JS
		JHtml::script('mediaelements/mediaelement-and-player.js', false, true);
		JHtml::stylesheet('mediaelements/mediaelementplayer.css', false, true, false);
		$player .= "<br /><script>
				var player = new MediaElementPlayer('#" . $ID . "');
			</script>";

		return $player;
	}
}
