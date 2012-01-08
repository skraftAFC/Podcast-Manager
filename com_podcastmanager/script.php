<?php
/**
 * Podcast Manager for Joomla!
 *
 * @package     PodcastManager
 * @subpackage  com_podcastmanager
 *
 * @copyright   Copyright (C) 2011-2012 Michael Babker. All rights reserved.
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 *
 * Podcast Manager is based upon the ideas found in Podcast Suite created by Joe LeBlanc
 * Original copyright (c) 2005 - 2008 Joseph L. LeBlanc and released under the GPLv2 license
 */

/**
 * Installation class to perform additional changes during install/uninstall/update
 *
 * @package     PodcastManager
 * @subpackage  com_podcastmanager
 * @since       1.7
 */
class Com_PodcastManagerInstallerScript
{
	/**
	 * An array of supported database types
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $dbSupport = array('mysql', 'mysqli', 'postgresql', 'sqlsrv');

	/**
	 * Function to act prior to installation process begins
	 *
	 * @param   string  $type    The action being performed
	 * @param   string  $parent  The function calling this method
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.7
	 */
	public function preflight($type, $parent)
	{
		// Requires Joomla! 2.5
		$jversion = new JVersion;
		$jplatform = new JPlatform;
		if (version_compare($jversion->getShortVersion(), '2.5', 'lt'))
		{
			JError::raiseNotice(null, JText::_('COM_PODCASTMANAGER_ERROR_INSTALL_JVERSION'));
			return false;
		}

		// Check to see if the database type is supported
		$db = JFactory::getDbo();
		if (!in_array($db->name, $this->dbSupport))
		{
			JError::raiseNotice(null, JText::_('COM_PODCASTMANAGER_ERROR_DB_SUPPORT'));
			return false;
		}

		// Bugfix for "Can not build admin menus"
		if (in_array($type, array('install', 'discover_install')))
		{
			$this->_bugfixDBFunctionReturnedNoError();
		}
		else
		{
			$this->_bugfixCantBuildAdminMenus();
		}

		return true;
	}

	/**
	 * Function to perform changes during uninstall
	 *
	 * @param   string  $parent  The function calling this method
	 *
	 * @return  void
	 *
	 * @since   1.8
	 */
	public function uninstall($parent)
	{
		// Build a menu record for the media component to prevent the "cannot delete admin menu" error
		// Get the component's ID from the database
		$option = 'com_podcastmedia';
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('extension_id');
		$query->from('#__extensions');
		$query->where('element = ' . $db->quote($option));
		$db->setQuery($query);
		$component_id = $db->loadResult();

		// Add the record
		$table = JTable::getInstance('menu');

		$data = array();
		$data['menutype'] = 'main';
		$data['client_id'] = 1;
		$data['title'] = $option;
		$data['alias'] = $option;
		$data['link'] = 'index.php?option=' . $option;
		$data['type'] = 'component';
		$data['published'] = 0;
		$data['parent_id'] = 1;
		$data['component_id'] = $component_id;
		$data['img'] = 'class:component';
		$data['home'] = 0;

		// All the table processing without error checks since we're hacking to prevent an error message
		if (!$table->setLocation(1, 'last-child') || !$table->bind($data) || !$table->check() || !$table->store())
		{
			// Do nothing ;-)
		}
	}

	/**
	 * Function to get the currently installed version from the manifest cache
	 *
	 * @return  string  The version that is installed
	 *
	 * @since   1.7
	 */
	protected function getVersion()
	{
		// Get the record from the database
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('manifest_cache');
		$query->from('#__extensions');
		$query->where('element = ' . $db->quote('com_podcastmanager'));
		$db->setQuery($query);
		if (!$db->loadObject())
		{
			JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));
			$version = 'Error';
			return $version;
		}
		else
		{
			$manifest = $db->loadObject();
		}

		// Decode the JSON
		$record = json_decode($manifest->manifest_cache);

		// Get the version
		$version = $record->version;

		return $version;
	}

	/**
	 * Joomla! 1.6+ bugfix for "DB function returned no error"
	 *
	 * @author	Nicholas K. Dionysopoulos (https://www.akeebabackup.com)
	 *
	 * @return  void
	 *
	 * @since   1.8
	 */
	private function _bugfixDBFunctionReturnedNoError()
	{
		$db = JFactory::getDbo();

		// Fix broken #__assets records
		$query = $db->getQuery(true);
		$query->select('id');
		$query->from('#__assets');
		$query->where('name = ' . $db->quote('com_podcastmanager'));
		$db->setQuery($query);
		$ids = $db->loadColumn();
		if (!empty($ids))
		{
			foreach ($ids as $id)
			{
				$query->clear();
				$query->delete('#__assets');
				$query->where('id = ' . $db->quote($id));
				$db->setQuery($query);
				$db->query();
			}
		}

		// Fix broken #__extensions records
		$query->clear();
		$query->select('extension_id');
		$query->from('#__extensions');
		$query->where('element = ' . $db->quote('com_podcastmanager'));
		$db->setQuery($query);
		$ids = $db->loadColumn();
		if (!empty($ids))
		{
			foreach ($ids as $id)
			{
				$query->clear();
				$query->delete('#__extensions');
				$query->where('extension_id = ' . $db->quote($id));
				$db->setQuery($query);
				$db->query();
			}
		}

		// Fix broken #__menu records
		$query->clear();
		$query->select('id');
		$query->from('#__menu');
		$query->where('type = ' . $db->quote('component'));
		$query->where('menutype = ' . $db->quote('main'));
		$query->where('link LIKE ' . $db->quote('index.php?option=com_podcastmanager%'));
		$db->setQuery($query);
		$ids = $db->loadColumn();
		if (!empty($ids))
		{
			foreach ($ids as $id)
			{
				$query->clear();
				$query->delete('#__menu');
				$query->where('id = ' . $db->quote($id));
				$db->setQuery($query);
				$db->query();
			}
		}
	}

	/**
	 * Joomla! 1.6+ bugfix for "Can not build admin menus"
	 *
	 * @author	Nicholas K. Dionysopoulos (https://www.akeebabackup.com)
	 *
	 * @return  void
	 *
	 * @since   1.8
	 */
	private function _bugfixCantBuildAdminMenus()
	{
		$db = JFactory::getDbo();

		// If there are multiple #__extensions record, keep one of them
		$query = $db->getQuery(true);
		$query->select('extension_id');
		$query->from('#__extensions');
		$query->where('element = ' . $db->quote('com_podcastmanager'));
		$db->setQuery($query);
		$ids = $db->loadColumn();
		if (count($ids) > 1)
		{
			asort($ids);
			$extension_id = array_shift($ids); // Keep the oldest id

			foreach ($ids as $id)
			{
				$query->clear();
				$query->delete('#__extensions');
				$query->where('extension_id = ' . $db->quote($id));
				$db->setQuery($query);
				$db->query();
			}
		}

		// @todo

		// If there are multiple assets records, delete all except the oldest one
		$query->clear();
		$query->select('id');
		$query->from('#__assets');
		$query->where('name = ' . $db->quote('com_podcastmanager'));
		$db->setQuery($query);
		$ids = $db->loadObjectList();
		if (count($ids) > 1)
		{
			asort($ids);
			$asset_id = array_shift($ids); // Keep the oldest id

			foreach ($ids as $id)
			{
				$query->clear();
				$query->delete('#__assets');
				$query->where('id = ' . $db->quote($id));
				$db->setQuery($query);
				$db->query();
			}
		}

		// Remove #__menu records for good measure!
		$query->clear();
		$query->select('id');
		$query->from('#__menu');
		$query->where('type = ' . $db->quote('component'));
		$query->where('menutype = ' . $db->quote('main'));
		$query->where('link LIKE ' . $db->quote('index.php?option=com_podcastmanager%'));
		$db->setQuery($query);
		$ids = $db->loadColumn();
		if (!empty($ids))
		{
			foreach ($ids as $id)
			{
				$query->clear();
				$query->delete('#__menu');
				$query->where('id = ' . $db->quote($id));
				$db->setQuery($query);
				$db->query();
			}
		}
	}
}
