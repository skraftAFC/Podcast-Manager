<?php
/**
* Podcast Manager for Joomla!
*
* @copyright	Copyright (C) 2011 Michael Babker. All rights reserved.
* @license		GNU/GPL - http://www.gnu.org/copyleft/gpl.html
* 
* Podcast Manager is based upon the ideas found in Podcast Suite created by Joe LeBlanc
* Original copyright (c) 2005 - 2008 Joseph L. LeBlanc and released under the GPLv2 license
*/

// Restricted access
defined('_JEXEC') or die;

$params = new JRegistry;
$dispatcher	= JDispatcher::getInstance();
$dispatcher->trigger('onContentBeforeDisplay', array('com_podcastmedia.file', &$this->_tmp_audio, &$params));
?>
		<div class="item">
			<a href="javascript:AudioManager.populateFields('<?php echo $this->_tmp_audio->path_relative; ?>')" title="<?php echo $this->_tmp_audio->name; ?>" >
				<?php echo JHtml::_('image',$this->_tmp_audio->icon_32, $this->_tmp_audio->name, null, true, true) ? JHtml::_('image',$this->_tmp_audio->icon_32, $this->_tmp_audio->title, NULL, true) : JHtml::_('image','media/con_info.png', $this->_tmp_audio->name, NULL, true) ; ?>
				<span title="<?php echo $this->_tmp_audio->name; ?>"><?php echo $this->_tmp_audio->title; ?></span></a>
		</div>
<?php
$dispatcher->trigger('onContentAfterDisplay', array('com_podcastmedia.file', &$this->_tmp_audio, &$params));
?>
