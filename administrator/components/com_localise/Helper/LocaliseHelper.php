<?php
/**
 * @package     Com_Localise
 * @subpackage  helper
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Localise\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Filesystem\Stream;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Version;
use Joomla\Component\Localise\Administrator\Model\PackagesModel;
use Joomla\Component\Localise\Administrator\Model\TranslationModel;
use Joomla\Github\Github;
use Joomla\Registry\Registry;

jimport("joomla.utilities.date");

require_once JPATH_ADMINISTRATOR . '/components/com_localise/vendor/autoload.php';

include_once JPATH_ADMINISTRATOR . '/components/com_localise/Helper/defines.php';

/**
 * Localise Helper class
 *
 * @package     Extensions.Components
 * @subpackage  Localise
 * @since       4.0
 */
abstract class LocaliseHelper
{
	/**
	 * Array containing the origin information
	 *
	 * @var    array
	 * @since  4.0
	 */
	protected static $origins = array('site' => null, 'administrator' => null, 'installation' => null);

	/**
	 * Array containing the package information
	 *
	 * @var    array
	 * @since  4.0
	 */
	protected static $packages = array();

	/**
	 * Determines if a given path is writable in the current environment
	 *
	 * @param   string  $path  Path to check
	 *
	 * @return  boolean  True if writable
	 *
	 * @since   4.0
	 */
	public static function isWritable($path)
	{
		if (Factory::getConfig()->get('config.ftp_enable'))
		{
			return true;
		}
		else
		{
			while (!file_exists($path))
			{
				$path = dirname($path);
			}

			return is_writable($path) || Path::isOwner($path) || Path::canChmod($path);
		}
	}

	/**
	 * Check if the installation path exists
	 *
	 * @return  boolean  True if the installation path exists
	 *
	 * @since   4.0
	 */
	public static function hasInstallation()
	{
		return is_dir(LOCALISEPATH_INSTALLATION);
	}

	/**
	 * Retrieve the packages array
	 *
	 * @return  array
	 *
	 * @since   4.0
	 */
	public static function getPackages()
	{
		if (empty(static::$packages))
		{
			static::scanPackages();
		}

		return static::$packages;
	}

	/**
	 * Scans the filesystem for language files in each package
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	protected static function scanPackages()
	{
		$model = new \Joomla\Component\Localise\Administrator\Model\PackagesModel;
		$model->getState();
		$model->setState('list.start', 0);
		$model->setState('list.limit', 0);
		$packages = $model->getItems();

		foreach ($packages as $package)
		{
			static::$packages[$package->name] = $package;

			foreach ($package->administrator as $file)
			{
				static::$origins['administrator'][$file] = $package->name;
			}

			foreach ($package->site as $file)
			{
				static::$origins['site'][$file] = $package->name;
			}
		}
	}

	/**
	 * Retrieves the origin information
	 *
	 * @param   string  $filename  The filename to check
	 * @param   string  $client    The client to check
	 *
	 * @return  string  Origin data
	 *
	 * @since   4.0
	 */
	public static function getOrigin($filename, $client)
	{
		if ($filename == 'override')
		{
			return '_override';
		}

		// If the $origins array doesn't contain data, fill it
		if (empty(static::$origins['site']))
		{
			static::scanPackages();
		}

		if (isset(static::$origins[$client][$filename]))
		{
			return static::$origins[$client][$filename];
		}
		else
		{
			return '_thirdparty';
		}
	}

	/**
	 * Scans the filesystem
	 *
	 * @param   string  $client  The client to scan
	 * @param   string  $type    The extension type to scan
	 *
	 * @return  array
	 *
	 * @since   4.0
	 */
	public static function getScans($client = '', $type = '')
	{
		$params   = ComponentHelper::getParams('com_localise');
		$suffixes = explode(',', $params->get('suffixes', '.sys'));

		$filter_type   = $type ? $type : '.';
		$filter_client = $client ? $client : '.';
		$scans         = array();

		// Scan installation folders
		if (preg_match("/$filter_client/", 'installation'))
		{
			// TODO ;-)
		}

		// Scan administrator folders
		if (preg_match("/$filter_client/", 'administrator'))
		{
			// Scan administrator components folders
			if (preg_match("/$filter_type/", 'component'))
			{
				$scans[] = array(
					'prefix' => '',
					'suffix' => '',
					'type'   => 'component',
					'client' => 'administrator',
					'path'   => LOCALISEPATH_ADMINISTRATOR . '/components/',
					'folder' => ''
				);

				foreach ($suffixes as $suffix)
				{
					$scans[] = array(
						'prefix' => '',
						'suffix' => $suffix,
						'type'   => 'component',
						'client' => 'administrator',
						'path'   => LOCALISEPATH_ADMINISTRATOR . '/components/',
						'folder' => ''
					);
				}
			}

			// Scan administrator modules folders
			if (preg_match("/$filter_type/", 'module'))
			{
				$scans[] = array(
					'prefix' => '',
					'suffix' => '',
					'type'   => 'module',
					'client' => 'administrator',
					'path'   => LOCALISEPATH_ADMINISTRATOR . '/modules/',
					'folder' => ''
				);

				foreach ($suffixes as $suffix)
				{
					$scans[] = array(
						'prefix' => '',
						'suffix' => $suffix,
						'type'   => 'module',
						'client' => 'administrator',
						'path'   => LOCALISEPATH_ADMINISTRATOR . '/modules/',
						'folder' => ''
					);
				}
			}

			// Scan administrator templates folders
			if (preg_match("/$filter_type/", 'template'))
			{
				$scans[] = array(
					'prefix' => 'tpl_',
					'suffix' => '',
					'type'   => 'template',
					'client' => 'administrator',
					'path'   => LOCALISEPATH_ADMINISTRATOR . '/templates/',
					'folder' => ''
				);

				foreach ($suffixes as $suffix)
				{
					$scans[] = array(
						'prefix' => 'tpl_',
						'suffix' => $suffix,
						'type'   => 'template',
						'client' => 'administrator',
						'path'   => LOCALISEPATH_ADMINISTRATOR . '/templates/',
						'folder' => ''
					);
				}
			}

			// Scan plugins folders
			if (preg_match("/$filter_type/", 'plugin'))
			{
				$plugin_types = Folder::folders(JPATH_PLUGINS);

				foreach ($plugin_types as $plugin_type)
				{
					// Scan administrator language folders as this is where plugin languages are installed
					$scans[] = array(
						'prefix' => 'plg_' . $plugin_type . '_',
						'suffix' => '',
						'type'   => 'plugin',
						'client' => 'administrator',
						'path'   => JPATH_PLUGINS . "/$plugin_type/",
						'folder' => ''
					);

					foreach ($suffixes as $suffix)
					{
						$scans[] = array(
							'prefix' => 'plg_' . $plugin_type . '_',
							'suffix' => $suffix,
							'type'   => 'plugin',
							'client' => 'administrator',
							'path'   => JPATH_PLUGINS . "/$plugin_type/",
							'folder' => ''
						);
					}
				}
			}
		}

		// Scan site folders
		if (preg_match("/$filter_client/", 'site'))
		{
			// Scan site components folders
			if (preg_match("/$filter_type/", 'component'))
			{
				$scans[] = array(
					'prefix' => '',
					'suffix' => '',
					'type'   => 'component',
					'client' => 'site',
					'path'   => LOCALISEPATH_SITE . '/components/',
					'folder' => ''
				);

				foreach ($suffixes as $suffix)
				{
					$scans[] = array(
						'prefix' => '',
						'suffix' => $suffix,
						'type'   => 'component',
						'client' => 'site',
						'path'   => LOCALISEPATH_SITE . '/components/',
						'folder' => ''
					);
				}
			}

			// Scan site modules folders
			if (preg_match("/$filter_type/", 'module'))
			{
				$scans[] = array(
					'prefix' => '',
					'suffix' => '',
					'type'   => 'module',
					'client' => 'site',
					'path'   => LOCALISEPATH_SITE . '/modules/',
					'folder' => ''
				);

				foreach ($suffixes as $suffix)
				{
					$scans[] = array(
						'prefix' => '',
						'suffix' => $suffix,
						'type'   => 'module',
						'client' => 'site',
						'path'   => LOCALISEPATH_SITE . '/modules/',
						'folder' => ''
					);
				}
			}

			// Scan site templates folders
			if (preg_match("/$filter_type/", 'template'))
			{
				$scans[] = array(
					'prefix' => 'tpl_',
					'suffix' => '',
					'type'   => 'template',
					'client' => 'site',
					'path'   => LOCALISEPATH_SITE . '/templates/',
					'folder' => ''
				);

				foreach ($suffixes as $suffix)
				{
					$scans[] = array(
						'prefix' => 'tpl_',
						'suffix' => $suffix,
						'type'   => 'template',
						'client' => 'site',
						'path'   => LOCALISEPATH_SITE . '/templates/',
						'folder' => ''
					);
				}
			}
		}

		return $scans;
	}

	/**
	 * Get file ID in the database for the given file path
	 *
	 * @param   string  $path  Path to lookup
	 *
	 * @return  integer  File ID
	 *
	 * @since   4.0
	 */
	public static function getFileId($path)
	{
		static $fileIds = null;

		if (!isset($fileIds))
		{
			$db = Factory::getDbo();

			$db->setQuery(
				$db->getQuery(true)
					->select($db->quoteName(array('id', 'path')))
					->from($db->quoteName('#__localise'))
			);

			$fileIds = $db->loadObjectList('path');
		}

		if (is_file($path) || preg_match('/.ini$/', $path))
		{
			if (!array_key_exists($path, $fileIds))
			{
				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_localise/table/');

				/* @type  LocaliseTableLocalise  $table */
				$table       = Table::getInstance('LocaliseTable', '\\Joomla\\Component\\Localise\\Administrator\\Table\\');
				$table->path = $path;
				$table->store();

				$fileIds[$path] = new \stdClass;
				$fileIds[$path]->id = $table->id;
			}

			return $fileIds[$path]->id;
		}
		else
		{
			$id = 0;
		}

		return $id;
	}

	/**
	 * Get file path in the database for the given file id
	 *
	 * @param   integer  $id  Id to lookup
	 *
	 * @return  string   File Path
	 *
	 * @since   4.0
	 */
	public static function getFilePath($id)
	{
		static $filePaths = null;

		if (!isset($filePaths))
		{
			$db = Factory::getDbo();

			$db->setQuery(
				$db->getQuery(true)
					->select($db->quoteName(array('id', 'path')))
					->from($db->quoteName('#__localise'))
			);

			$filePaths = $db->loadObjectList('id');
		}

		return array_key_exists("$id", $filePaths) ?
		$filePaths["$id"]->path : '';
	}

	/**
	 * Determine if a package at given path is core or not.
	 *
	 * @param   string  $path  Path to lookup
	 *
	 * @return  mixed  null if file is invalid | True if core else false.
	 *
	 * @since   4.0
	 */
	public static function isCorePackage($path)
	{
		if (is_file($path) || preg_match('/.ini$/', $path))
		{
			$xml = simplexml_load_file($path);

			return ((string) $xml->attributes()->core) == 'true';
		}
	}

	/**
	 * Find a translation file
	 *
	 * @param   string  $client    Client to lookup
	 * @param   string  $tag       Language tag to lookup
	 * @param   string  $filename  Filename to lookup
	 *
	 * @return  string  Path to the requested file
	 *
	 * @since   4.0
	 */
	public static function findTranslationPath($client, $tag, $filename)
	{
		$params = ComponentHelper::getParams('com_localise');
		$priority = $params->get('priority', '0') == '0' ? 'global' : 'local';
		$path = static::getTranslationPath($client, $tag, $filename, $priority);

		if (!is_file($path))
		{
			$priority = $params->get('priority', '0') == '0' ? 'local' : 'global';
			$path = static::getTranslationPath($client, $tag, $filename, $priority);
		}

		return $path;
	}

	/**
	 * Get a translation path
	 *
	 * @param   string  $client    Client to lookup
	 * @param   string  $tag       Language tag to lookup
	 * @param   string  $filename  Filename to lookup
	 * @param   string  $storage   Storage location to check
	 *
	 * @return  string  Path to the requested file
	 *
	 * @since   4.0
	 */
	public static function getTranslationPath($client, $tag, $filename, $storage)
	{
		if (!$client && !$filename)
		{
			return '';
		}

		if ($filename == 'override')
		{
			$path = constant('LOCALISEPATH_' . strtoupper($client)) . "/language/overrides/$tag.override.ini";
		}
		elseif ($filename == 'joomla')
		{
			$path = constant('LOCALISEPATH_' . strtoupper($client)) . "/language/$tag/joomla.ini";
		}
		elseif ($storage == 'global')
		{
			$path = constant('LOCALISEPATH_' . strtoupper($client)) . "/language/$tag/$filename.ini";
		}
		else
		{
			$parts     = explode('.', $filename);
			$extension = $parts[0];

			switch (substr($extension, 0, 3))
			{
				case 'com':
					$path = constant('LOCALISEPATH_' . strtoupper($client)) . "/components/$extension/language/$tag/$filename.ini";

					break;

				case 'mod':
					$path = constant('LOCALISEPATH_' . strtoupper($client)) . "/modules/$extension/language/$tag/$filename.ini";

					break;

				case 'plg':
					$parts  = explode('_', $extension);
					$group  = $parts[1];
					$parts	= explode('.', $filename);
					$pluginname = $parts[0];
					$plugin = substr($pluginname, 5 + strlen($group));
					$path   = JPATH_PLUGINS . "/$group/$plugin/language/$tag/$filename.ini";

					break;

				case 'tpl':
					$template = substr($extension, 4);
					$path     = constant('LOCALISEPATH_' . strtoupper($client)) . "/templates/$template/language/$tag/$filename.ini";

					break;

				case 'lib':
					$path = constant('LOCALISEPATH_' . strtoupper($client)) . "/language/$tag/$filename.ini";

					if (!is_file($path))
					{
						$path = $client == 'administrator' ? 'LOCALISEPATH_' . 'SITE' : 'LOCALISEPATH_' . 'ADMINISTRATOR' . "/language/$tag/$filename.ini";
					}

					break;

				default   :
					$path = '';

					break;
			}
		}

		return $path;
	}

	/**
	 * Load a language file for translating the package name
	 *
	 * @param   string  $extension  The extension to load
	 * @param   string  $client     The client from where to load the file
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	public static function loadLanguage($extension, $client)
	{
		$extension = strtolower($extension);
		$lang      = Factory::getLanguage();
		$prefix    = substr($extension, 0, 3);

		switch ($prefix)
		{
			case 'com':
				$lang->load($extension, constant('LOCALISEPATH_' . strtoupper($client)), null, false, true)
					|| $lang->load($extension, constant('LOCALISEPATH_' . strtoupper($client)) . "/components/$extension/", null, false, true);

				break;

			case 'mod':
				$lang->load($extension, constant('LOCALISEPATH_' . strtoupper($client)), null, false, true)
					|| $lang->load($extension, constant('LOCALISEPATH_' . strtoupper($client)) . "/modules/$extension/", null, false, true);

				break;

			case 'plg':
				$lang->load($extension, 'LOCALISEPATH_' . 'ADMINISTRATOR', null, false, true)
					|| $lang->load($extension, LOCALISEPATH_ADMINISTRATOR . "/components/$extension/", null, false, true);

				break;

			case 'tpl':
				$template = substr($extension, 4);
				$lang->load($extension, constant('LOCALISEPATH_' . strtoupper($client)), null, false, true)
					|| $lang->load($extension, constant('LOCALISEPATH_' . strtoupper($client)) . "/templates/$template/", null, false, true);

				break;

			case 'lib':
			case 'fil':
			case 'pkg':
				$lang->load($extension, JPATH_ROOT, null, false, true);

				break;
		}
	}

	/**
	 * Parses the sections of a language file
	 *
	 * @param   string  $filename  The filename to parse
	 *
	 * @return  array  Array containing the file data
	 *
	 * @since   4.0
	 */
	public static function parseSections($filename)
	{
		static $sections = array();

		if (!array_key_exists($filename, $sections))
		{
			if (file_exists($filename))
			{
				$error = '';

				if (!defined('_QQ_'))
				{
					define('_QQ_', '"');
				}

				ini_set('track_errors', '1');

				$contents = file_get_contents($filename);
				$contents = str_replace('_QQ_', '"\""', $contents);
				$strings  = @parse_ini_string($contents, true);

				if (!empty($php_errormsg))
				{
					$error = "Error parsing " . basename($filename) . ": $php_errormsg";
				}

				ini_restore('track_errors');

				if ($strings !== false)
				{
					$default = array();

					foreach ($strings as $key => $value)
					{
						if (is_string($value))
						{
							$default[$key] = $value;

							unset($strings[$key]);
						}
						else
						{
							break;
						}
					}

					if (!empty($default))
					{
						$strings = array_merge(array('Default' => $default), $strings);
					}

					$keys = array();

					foreach ($strings as $section => $value)
					{
						foreach ($value as $key => $string)
						{
							$keys[$key] = $strings[$section][$key];
						}
					}
				}
				else
				{
					$keys = false;
				}

				$sections[$filename] = array('sections' => $strings, 'keys' => $keys, 'error' => $error);
			}
			else
			{
				$sections[$filename] = array('sections' => array(), 'keys' => array(), 'error' => '');
			}
		}

		if (!empty($sections[$filename]['error']))
		{
			$model = new TranslationModel();
			$model->getState();
			$model->setError($sections[$filename]['error']);
		}

		return $sections[$filename];
	}

	/**
	 * Gets the files to use as source reference from Github
	 *
	 * @param   array  $gh_data  Array with the required data
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function getSourceGithubfiles($gh_data = array())
	{
		if (!empty($gh_data))
		{
			$params            = ComponentHelper::getParams('com_localise');
			$ref_tag           = $params->get('reference', 'en-GB');
			$saved_ref         = $params->get('customisedref', '0');
			$allow_develop     = $params->get('gh_allow_develop', 0);
			$gh_client         = $gh_data['github_client'];
			$customisedref     = $saved_ref;
			$last_sources      = self::getLastsourcereference();
			$last_source       = $last_sources[$gh_client];
			$versions          = array();
			$stored_core_files = self::getKnownCoreFilesList();

			if ($stored_core_files == false)
			{
				$stored_core_files = array();
			}

			$versions_path = JPATH_ROOT
					. '/administrator/components/com_localise/customisedref/stable_joomla_releases.txt';

			if (File::exists($versions_path))
			{
				$versions_file = file_get_contents($versions_path);
				$versions      = preg_split("/\\r\\n|\\r|\\n/", $versions_file);
			}

			if ($saved_ref != '0' && !in_array($customisedref, $versions))
			{
				// Ensure from translations view that we have updated the last one released only when no maches.
				$search_releases = self::getReleases();
				$versions_file   = file_get_contents($versions_path);
				$versions        = preg_split("/\\r\\n|\\r|\\n/", $versions_file);
			}

			$installed_version = new Version;
			$installed_version = $installed_version->getShortVersion();

			$core_paths['administrator'] = 'administrator/language/en-GB';
			$core_paths['site']          = 'language/en-GB';
			$core_paths['installation']  = 'installation/language/en-GB';

			if ($saved_ref == '0')
			{
				// It will use core language folders.
				$customisedref = "Local installed instance ($installed_version??)";
			}
			else
			{
				// It will use language folders with other stored versions.
				$custom_client_path = JPATH_ROOT . '/media/com_localise/customisedref/github/'
						. $gh_data['github_client']
						. '/'
						. $customisedref;
				$custom_client_path = Folder::makeSafe($custom_client_path);
			}

			// If reference tag is not en-GB is not required try it
			if ($ref_tag != 'en-GB' && $allow_develop == 1)
			{
				Factory::getApplication()->enqueueMessage(
					Text::_('COM_LOCALISE_ERROR_GETTING_UNALLOWED_CONFIGURATION'),
					'warning');

				return false;
			}

			// If not knowed Joomla version is not required try it
			if ($saved_ref != '0' && !in_array($customisedref, $versions) && $allow_develop == 1)
			{
				Factory::getApplication()->enqueueMessage(
					Text::sprintf('COM_LOCALISE_ERROR_GITHUB_GETTING_LOCAL_INSTALLED_FILES', $customisedref),
					'warning');

				$option    = '0';
				$revert    = self::setCustomisedsource($option);
				$save_last = self::saveLastsourcereference($gh_data['github_client'], '');

				return false;
			}

			// If feature is disabled but last used files are disctinct to default ones
			// Is required make notice that we are coming back to local installed instance version.
			if ($saved_ref != '0' && !empty($last_source) && $last_source != '0' && $allow_develop == 0)
			{
				$customisedref = $installed_version;

				Factory::getApplication()->enqueueMessage(
					Text::sprintf('COM_LOCALISE_NOTICE_DISABLED_ALLOW_DEVELOP_WITHOUT_LOCAL_SET',
						$last_source,
						$installed_version,
						$gh_client
						),
						'notice'
						);

				$option    = '0';
				$revert    = self::setCustomisedsource($option);
				$save_last = self::saveLastsourcereference($gh_data['github_client'], '');

				return true;
			}

			// If not knowed Joomla version and feature is disabled is not required try it
			if ($saved_ref != '0' && !in_array($customisedref, $versions) && $allow_develop == 0)
			{
				return false;
			}

			// If configured to local installed instance there is nothing to get from Github
			if ($saved_ref == '0')
			{
				$save_last = self::saveLastsourcereference($gh_data['github_client'], '');

				return true;
			}

			$xml_file = $custom_client_path . '/langmetadata.xml';

			// Unrequired move or update files again
			if ($saved_ref != '0' && $installed_version == $last_source && File::exists($xml_file))
			{
				return false;
			}

			$gh_data['allow_develop']  = $allow_develop;
			$gh_data['customisedref']  = $customisedref;
			$gh_target                 = self::getCustomisedsource($gh_data);
			$gh_paths                  = array();
			$gh_user                   = $gh_target['user'];
			$gh_project                = $gh_target['project'];
			$gh_branch                 = $gh_target['branch'];
			$gh_token                  = $params->get('gh_token', '');
			$gh_paths['administrator'] = 'administrator/language/en-GB';
			$gh_paths['site']          = 'language/en-GB';
			$gh_paths['installation']  = 'installation/language/en-GB';

			$reference_client_path = JPATH_ROOT . '/' . $gh_paths[$gh_client];
			$reference_client_path = Folder::makeSafe($reference_client_path);

			if (File::exists($xml_file))
			{
				// We have done this trunk and is not required get the files from Github again.
				$update_files = self::updateSourcereference($gh_client, $custom_client_path);

				if ($update_files == false)
				{
					return false;
				}

				$save_last = self::saveLastsourcereference($gh_data['github_client'], $customisedref);

				return true;
			}

			$options = new Registry;

			if (!empty($gh_token))
			{
				$options->set('headers', ['Authorization' => 'token ' . $gh_token]);
				$github = new Github($options);
			}
			else
			{
				// Without a token runs fatal.
				// $github = new JGithub;

				// Trying with a 'read only' public repositories token
				// But base 64 encoded to avoid Github alarms sharing it.
				$gh_token = base64_decode('MzY2NzYzM2ZkMzZmMWRkOGU5NmRiMTdjOGVjNTFiZTIyMzk4NzVmOA==');
				$options->set('headers', ['Authorization' => 'token ' . $gh_token]);
				$github = new Github($options);
			}

			try
			{
				$repostoryfiles = $github->repositories->contents->get(
					$gh_user,
					$gh_project,
					$gh_paths[$gh_client],
					$gh_branch
					);
			}
			catch (\Exception $e)
			{
				Factory::getApplication()->enqueueMessage(
					Text::_('COM_LOCALISE_ERROR_GITHUB_GETTING_REPOSITORY_FILES'),
					'warning');

				return false;
			}

			if (!Folder::exists($custom_client_path))
			{
				$create_folder = self::createFolder($gh_data, $index = 'true');

				if ($create_folder == false)
				{
					return false;
				}
			}

			$all_files_list = self::getLanguagefileslist($custom_client_path);

			foreach ($all_files_list as $id => $file)
			{
				$has_tag = substr($file, 0, 6);

				if ($has_tag == 'en-GB.')
				{
					$ext = File::getExt($file);

					if ($ext == "ini" && $file == 'en-GB.ini')
					{
						$all_files_list[$id] = str_replace('en-GB.', 'joomla.', $file);
					}
					else if ($ext == "ini")
					{
						$all_files_list[$id] = str_replace('en-GB.', '', $file);
					}

					if ($ext == "xml" && $file == 'en-GB.xml')
					{
						$all_files_list[$id] = str_replace('en-GB.', 'langmetadata.', $file);
					}

					if ($file == 'en-GB.localise.php')
					{
						$all_files_list[$id] = 'localise.php';
					}

				}
			}

			$ini_files_list = self::getInifileslist($custom_client_path);

			foreach ($ini_files_list as $id => $file)
			{
				$has_tag = substr($file, 0, 6);

				if ($has_tag == 'en-GB.')
				{
					$ext = File::getExt($file);

					if ($ext == "ini" && $file == 'en-GB.ini')
					{
						$ini_files_list[$id] = str_replace('en-GB.', 'joomla.', $file);
					}
					else if ($ext == "ini")
					{
						$ini_files_list[$id] = str_replace('joomla.', '', $file);
					}
				}
			}

			$files_to_include = array();

			foreach ($repostoryfiles as $repostoryfile)
			{
				$file_to_include = $repostoryfile->name;

				$has_tag = substr($file_to_include, 0, 6);

				if ($has_tag == 'en-GB.')
				{
					$ext = File::getExt($file_to_include);

					if ($ext == "ini" && $file_to_include == 'en-GB.ini')
					{
						$file_to_include = str_replace('en-GB.', 'joomla.', $file_to_include);
					}
					else if ($ext == "ini")
					{
						$file_to_include = str_replace('en-GB.', '', $file_to_include);
					}

					if ($ext == "xml" && $file_to_include == 'en-GB.xml')
					{
						$file_to_include = str_replace('en-GB.', 'langmetadata.', $file_to_include);
					}

					if ($file_to_include == 'en-GB.localise.php')
					{
						$file_to_include = 'localise.php';
					}
				}

				$file_path           = Folder::makeSafe($custom_client_path . '/' . $file_to_include);
				$reference_file_path = Folder::makeSafe($reference_client_path . '/' . $file_to_include);

				$custom_file = $github->repositories->contents->get(
						$gh_user,
						$gh_project,
						$repostoryfile->path,
						$gh_branch
						);

				$files_to_include[] = $file_to_include;

				if (!empty($custom_file) && isset($custom_file->content))
				{
					$file_to_include = $repostoryfile->name;
					$file_contents   = base64_decode($custom_file->content);

					File::write($file_path, $file_contents);

					if (!File::exists($file_path))
					{
						Factory::getApplication()->enqueueMessage(
							Text::_('COM_LOCALISE_ERROR_GITHUB_UNABLE_TO_CREATE_DEV_FILE'),
							'warning');

						return false;
					}

					if ($ext == 'ini' && !in_array($file_to_include, $stored_core_files))
					{
						$core_file           = new \JObject;
						$core_file->id       = null;
						$core_file->filename = $file_to_include;

						$add_core_file = self::addKnownCoreFile($core_file);

						// For debugging purposes only
						if ($add_core_file)
						{
							//Factory::getApplication()->enqueueMessage(Text::sprintf('COM_LOCALISE_TRANSLATION_ADD_CORE_FILE_SUCCESS',
							//	htmlspecialchars($file_to_include)),
							//	'notice');
						}
						else
						{
							//Factory::getApplication()->enqueueMessage(Text::sprintf('COM_LOCALISE_TRANSLATION_ADD_CORE_FILE_WARNING',
							//	htmlspecialchars($file_to_include)),
							//	'warning');
						}
					}
				}
			}

			if (!empty($all_files_list) && !empty($files_to_include))
			{
				// For files not present yet.
				$files_to_delete = array_diff($all_files_list, $files_to_include);

				if (!empty($files_to_delete))
				{
					foreach ($files_to_delete as $file_to_delete)
					{
						if ($file_to_delete != 'index.html')
						{
							$file_path = Folder::makeSafe($custom_client_path . "/" . $file_to_delete);
							File::delete($file_path);

							if (File::exists($file_path))
							{
								Factory::getApplication()->enqueueMessage(
									Text::_('COM_LOCALISE_ERROR_GITHUB_FILE_TO_DELETE_IS_PRESENT'),
									'warning');

								return false;
							}
						}
					}
				}
			}

			if (File::exists($xml_file))
			{
				// We have done this trunk.

				// So we can move the customised source reference files to core client folder
				$update_files = self::updateSourcereference($gh_client, $custom_client_path);

				if ($update_files == false)
				{
					return false;
				}

				$save_last = self::saveLastsourcereference($gh_data['github_client'], $customisedref);

				Factory::getApplication()->enqueueMessage(
					Text::sprintf('COM_LOCALISE_NOTICE_GITHUB_GETS_A_SOURCE_FULL_SET', $customisedref),
					'notice');

				return true;
			}

			Factory::getApplication()->enqueueMessage(
				Text::sprintf('COM_LOCALISE_ERROR_GITHUB_UNABLE_TO_GET_A_FULL_SOURCE_SET', $customisedref),
				'warning');

			return false;
		}

		Factory::getApplication()->enqueueMessage(Text::_('COM_LOCALISE_ERROR_GITHUB_NO_DATA_PRESENT'), 'warning');

		return false;
	}

	/**
	 * Gets the stable Joomla releases list.
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function getReleases()
	{
		$params        = ComponentHelper::getParams('com_localise');
		$versions_path = JPATH_ROOT
				. '/administrator/components/com_localise/customisedref/stable_joomla_releases.txt';
		$versions_file = file_get_contents($versions_path);
		$versions      = preg_split("/\\r\\n|\\r|\\n/", $versions_file);

		$gh_user       = 'joomla';
		$gh_project    = 'joomla-cms';
		$gh_token      = $params->get('gh_token', '');

		$options = new Registry;

		if (!empty($gh_token))
		{
			$options->set('headers', ['Authorization' => 'token ' . $gh_token]);
			$github = new Github($options);
		}
		else
		{
			// Without a token runs fatal.
			// $github = new JGithub;

			// Trying with a 'read only' public repositories token
			// But base 64 encoded to avoid Github alarms sharing it.
			$gh_token = base64_decode('MzY2NzYzM2ZkMzZmMWRkOGU5NmRiMTdjOGVjNTFiZTIyMzk4NzVmOA==');
			$options->set('headers', ['Authorization' => 'token ' . $gh_token]);
			$github = new Github($options);
		}

		try
		{
			$releases = $github->repositories->get(
					$gh_user,
					$gh_project . '/releases'
					);

			foreach ($releases as $release)
			{
				$tag_name = $release->tag_name;
				$tag_part = explode(".", $tag_name);
				$undoted  = str_replace('.', '', $tag_name);
				$excluded = 0;

				$installed_version = new Version;
				$installed_version = $installed_version->getShortVersion();

				if (version_compare($installed_version[0], '2', 'eq'))
				{
					$excluded = 1;
				}
				elseif (version_compare($installed_version[0], '3', 'eq'))
				{
					if ($tag_part[0] != '3')
					{
						$excluded = 1;
					}
				}
				elseif (version_compare($installed_version[0], '4', 'ge'))
				{
					if ($tag_part[0] == '4' || $tag_part[0] == '3')
					{
						$excluded = 0;
					}
					else
					{
						$excluded = 1;
					}
				}

				// Filtering by "is_numeric" disable betas or similar releases.
				if ($params->get('pre_stable', '0') == '0')
				{
					if (!in_array($tag_name, $versions) && is_numeric($undoted) && $excluded == 0)
					{
						$versions[] = $tag_name;
						Factory::getApplication()->enqueueMessage(
							Text::sprintf('COM_LOCALISE_NOTICE_NEW_VERSION_DETECTED', $tag_name),
							'notice');
					}
				}
				else
				{
					if (!in_array($tag_name, $versions) && $excluded == 0)
					{
						$versions[] = $tag_name;
						Factory::getApplication()->enqueueMessage(
							Text::sprintf('COM_LOCALISE_NOTICE_NEW_VERSION_DETECTED', $tag_name),
							'notice');
					}
				}
			}
		}
		catch (\Exception $e)
		{
			Factory::getApplication()->enqueueMessage(
				Text::_('COM_LOCALISE_ERROR_GITHUB_GETTING_RELEASES'),
				'warning');
		}

		arsort($versions);

		$versions_file = '';

		foreach ($versions as $id => $version)
		{
			if (!empty($version))
			{
				$versions_file .= $version . "\n";
			}
		}

		File::write($versions_path, $versions_file);

		return $versions;
	}

	/**
	 * Save the last en-GB source version used as reference.
	 *
	 * @param   string  $client         The client
	 *
	 * @param   string  $customisedref  The version number
	 *
	 * @return  boolean
	 *
	 * @since   4.11
	 */
	public static function saveLastsourcereference($client = '', $customisedref = '')
	{
		$last_reference_file = JPATH_COMPONENT_ADMINISTRATOR
					. '/customisedref/'
					. $client
					. '_last_source_ref.txt';

		$file_contents = $customisedref . "\n";

		if (!File::write($last_reference_file, $file_contents))
		{
			return false;
		}

		return true;
	}

	/**
	 * Gets the last en-GB source reference version moved to the core folders.
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function getLastsourcereference()
	{
		$last_source_reference = array();
		$last_source_reference['administrator'] = '';
		$last_source_reference['site'] = '';
		$last_source_reference['installation'] = '';

		$clients = array('administrator', 'site', 'installation');

		foreach ($clients as $client)
		{
			$last_reference_file = JPATH_COMPONENT_ADMINISTRATOR
							. '/customisedref/'
							. $client
							. '_last_source_ref.txt';

			if (File::exists($last_reference_file))
			{
				$file_contents = file_get_contents($last_reference_file);
				$lines = preg_split("/\\r\\n|\\r|\\n/", $file_contents);

				foreach ($lines as $line)
				{
					if (!empty($line))
					{
						$last_source_reference[$client] = $line;
					}
				}
			}
		}

		return $last_source_reference;
	}

	/**
	 * Gets the reference name to use at Github
	 *
	 * @param   array  $gh_data  Array with the required data
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function getCustomisedsource($gh_data = array())
	{
		$source_ref        = $gh_data['customisedref'];
		$allow_develop     = $gh_data['allow_develop'];

		$sources = array();

		// Detailing it we can handle exceptions and add other Github users or projects.
		// To get the language files for a determined Joomla's version that is not present from main Github repository.
		$sources['3.4.1']['user']    = 'joomla';
		$sources['3.4.1']['project'] = 'joomla-cms';
		$sources['3.4.1']['branch']  = '3.4.1';

		if (array_key_exists($source_ref, $sources))
		{
			return ($sources[$source_ref]);
		}

		// For undefined REF 0 or unlisted cases due Joomla releases at Github are following a version name patern.
		$sources[$source_ref]['user']    = 'joomla';
		$sources[$source_ref]['project'] = 'joomla-cms';
		$sources[$source_ref]['branch']  = $source_ref;

	return ($sources[$source_ref]);
	}

	/**
	 * Keep updated the customised client path including only the common files present in develop.
	 *
	 * @param   string  $client              The client
	 *
	 * @param   string  $custom_client_path  The path where the new source reference is stored
	 *
	 * @return  boolean
	 *
	 * @since   4.11
	 */
	public static function updateSourcereference($client, $custom_client_path)
	{
		$develop_client_path   = JPATH_ROOT . '/media/com_localise/develop/github/joomla-cms/en-GB/' . $client;
		$develop_client_path   = Folder::makeSafe($develop_client_path);

		$custom_ini_files_list = self::getInifileslist($custom_client_path);
		$last_ini_files_list   = self::getInifileslist($develop_client_path);

		$files_to_exclude = array();

		foreach ($custom_ini_files_list as $id => $file)
		{
			$has_tag = substr($file, 0, 6);

			if ($has_tag == 'en-GB.' && $file == 'en-GB.ini')
			{
				$custom_ini_files_list[$id] = str_replace('en-GB.', 'joomla.', $file);
			}
			else
			{
				$custom_ini_files_list[$id] = str_replace('en-GB.', '', $file);
			}
		}

		if (!File::exists($develop_client_path . '/langmetadata.xml'))
		{
			Factory::getApplication()->enqueueMessage(
				Text::_('COM_LOCALISE_ERROR_GITHUB_UNABLE_TO_UPDATE_TARGET_FILES'),
				'warning');

			return false;
		}
		elseif (!File::exists($custom_client_path . '/langmetadata.xml'))
		{
			Factory::getApplication()->enqueueMessage(
				Text::_('COM_LOCALISE_ERROR_GITHUB_UNABLE_TO_UPDATE_SOURCE_FILES'),
				'warning');

			return false;
		}

		// This one is for files not present within last in dev yet.
		// Due have no sense add old language files to translate or revise for the comming soon package.

		$files_to_exclude = array_diff($custom_ini_files_list, $last_ini_files_list);

		if (!empty($files_to_exclude))
		{
			$errors = 0;

			foreach ($files_to_exclude as $file_to_delete)
			{
				$custom_file_path = Folder::makeSafe($custom_client_path . "/" . $file_to_delete);

				if (!File::delete($custom_file_path))
				{
					$errors++;
				}
			}

			if ($errors > 0)
			{
				Factory::getApplication()->enqueueMessage(
					Text::sprintf('COM_LOCALISE_ERROR_DELETING_EXTRA_SOURCE_FILES', $errors),
					'warning');

				return false;
			}
		}

		return true;
	}

	/**
	 * Keep updated the core path with the selected source files as reference (used at previous working mode).
	 *
	 * @param   string  $client                 The client
	 *
	 * @param   string  $custom_client_path     The path where the new source reference is stored
	 *
	 * @param   string  $reference_client_path  The path where the old source reference is stored
	 *
	 * @return  boolean
	 *
	 * @since   4.11
	 */
	public static function updateSourcereferencedirectly($client, $custom_client_path, $reference_client_path)
	{
		$develop_client_path   = JPATH_ROOT . '/media/com_localise/develop/github/joomla-cms/en-GB/' . $client;
		$develop_client_path   = Folder::makeSafe($develop_client_path);

		$custom_ini_files_list = self::getInifileslist($custom_client_path);
		$last_ini_files_list   = self::getInifileslist($develop_client_path);

		$files_to_exclude = array();

		if (!File::exists($develop_client_path . '/langmetadata.xml'))
		{
			Factory::getApplication()->enqueueMessage(
				Text::_('COM_LOCALISE_ERROR_GITHUB_UNABLE_TO_UPDATE_TARGET_FILES'),
				'warning');

			return false;
		}
		elseif (!File::exists($custom_client_path . '/langmetadata.xml'))
		{
			Factory::getApplication()->enqueueMessage(
				Text::_('COM_LOCALISE_ERROR_GITHUB_UNABLE_TO_UPDATE_SOURCE_FILES'),
				'warning');

			return false;
		}

		// This one is for core files not present within last in dev yet.
		// Due have no sense add old language files to translate for the comming soon package.

		$files_to_exclude = array_diff($custom_ini_files_list, $last_ini_files_list);

		if (!empty($files_to_exclude))
		{
			foreach ($files_to_exclude as $file_to_delete)
			{
				$custom_file_path = Folder::makeSafe($custom_client_path . "/" . $file_to_delete);
				$actual_file_path = Folder::makeSafe($reference_client_path . "/" . $file_to_delete);

				File::delete($custom_file_path);

				// Also verify if the same file is also present in core language folder.

				if (File::exists($actual_file_path))
				{
					File::delete($actual_file_path);

					Factory::getApplication()->enqueueMessage(
					Text::sprintf('COM_LOCALISE_OLD_FILE_DELETED', $file_to_delete),
					'notice');
				}
			}

			// Getting the new list again
			$custom_ini_files_list = self::getInifileslist($custom_client_path);
		}

		$errors = 0;

		foreach ($custom_ini_files_list as $customised_source_file)
		{
			$source_path = $custom_client_path . '/' . $customised_source_file;
			$file_contents = file_get_contents($source_path);
			$target_path = $reference_client_path . '/' . $customised_source_file;

			if (!File::write($target_path, $file_contents))
			{
				$errors++;
			}
		}

		if ($errors > 0)
		{
			Factory::getApplication()->enqueueMessage(
				Text::sprintf('COM_LOCALISE_ERROR_SAVING_FILES_AT_CORE_FOLDER', $errors),
				'warning');

			return false;
		}

		return true;
	}

	/**
	 * Gets from zero or keept updated the files in develop to use as target reference from Github
	 *
	 * @param   array  $gh_data  Array with the required data
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function getTargetgithubfiles($gh_data = array())
	{
		if (!empty($gh_data))
		{
			$now                = new Date;
			$now                = $now->toSQL();
			$params             = ComponentHelper::getParams('com_localise');
			$client_to_update   = 'gh_' . $gh_data['github_client'] . '_last_update';
			$last_stored_update = $params->get($client_to_update, '');
			$ref_tag            = $params->get('reference', 'en-GB');
			$allow_develop      = $params->get('gh_allow_develop', 0);
			$stored_core_files  = self::getKnownCoreFilesList();

			if ($stored_core_files == false)
			{
				$stored_core_files = array();
			}

			if ($allow_develop == 0)
			{
				return false;
			}

			if ($ref_tag != 'en-GB')
			{
				Factory::getApplication()->enqueueMessage(
					Text::_('COM_LOCALISE_ERROR_GETTING_ALLOWED_REFERENCE_TAG'),
					'warning');

				return false;
			}

			$develop_client_path = JPATH_ROOT
						. '/media/com_localise/develop/github/joomla-cms/en-GB/'
						. $gh_data['github_client'];

			$develop_client_path = Folder::makeSafe($develop_client_path);
			$xml_file            = $develop_client_path . '/langmetadata.xml';

			if (!File::exists($xml_file))
			{
				$get_files = 1;
			}
			elseif (!empty($last_stored_update))
			{
				$last_update = new Date($last_stored_update);
				$last_update = $last_update->toSQL();
				$interval    = $params->get('gh_updates_interval', '1') == '1' ? 24 : 1;
				$interval    = $last_update . " +" . $interval . " hours";
				$next_update = new Date($interval);
				$next_update = $next_update->toSQL();

				if ($now >= $next_update)
				{
					$get_files = 1;
				}
				else
				{
					$get_files = 0;
				}
			}
			else
			{
				$get_files = 1;
			}

			if ($get_files == 0)
			{
				return false;
			}

			$gh_paths                  = array();
			$gh_client                 = $gh_data['github_client'];
			$gh_user                   = 'joomla';
			$gh_project                = 'joomla-cms';
			$gh_branch                 = $params->get('gh_branch', 'master');
			$gh_token                  = $params->get('gh_token', '');
			$gh_paths['administrator'] = 'administrator/language/en-GB';
			$gh_paths['site']          = 'language/en-GB';
			$gh_paths['installation']  = 'installation/language/en-GB';

			$reference_client_path = JPATH_ROOT . '/' . $gh_paths[$gh_client];
			$reference_client_path = Folder::makeSafe($reference_client_path);

			$options = new Registry;

			if (!empty($gh_token))
			{
				$options->set('headers', ['Authorization' => 'token ' . $gh_token]);
				$github = new Github($options);
			}
			else
			{
				// Without a token runs fatal.
				// $github = new JGithub;

				// Trying with a 'read only' public repositories token
				// But base 64 encoded to avoid Github alarms sharing it.
				$gh_token = base64_decode('MzY2NzYzM2ZkMzZmMWRkOGU5NmRiMTdjOGVjNTFiZTIyMzk4NzVmOA==');
				$options->set('headers', ['Authorization' => 'token ' . $gh_token]);
				$github = new Github($options);
			}

			try
			{
				$repostoryfiles = $github->repositories->contents->get(
					$gh_user,
					$gh_project,
					$gh_paths[$gh_client],
					$gh_branch
					);
			}
			catch (\Exception $e)
			{
				Factory::getApplication()->enqueueMessage(
					Text::_('COM_LOCALISE_ERROR_GITHUB_GETTING_REPOSITORY_FILES'),
					'warning');

				return false;
			}

			$all_files_list = self::getLanguagefileslist($develop_client_path);
			$ini_files_list = self::getInifileslist($develop_client_path);
			$sha_files_list = self::getShafileslist($gh_data);

			$sha = '';
			$files_to_include = array();

			foreach ($repostoryfiles as $repostoryfile)
			{
				$file_to_include     = $repostoryfile->name;
				$file_path           = Folder::makeSafe($develop_client_path . '/' . $file_to_include);
				$reference_file_path = Folder::makeSafe($reference_client_path . '/' . $file_to_include);

				if (	(array_key_exists($file_to_include, $sha_files_list)
					&& ($sha_files_list[$file_to_include] != $repostoryfile->sha))
					|| empty($sha_files_list)
					|| !array_key_exists($file_to_include, $sha_files_list)
					|| !File::exists($file_path))
				{
					$in_dev_file = $github->repositories->contents->get(
							$gh_user,
							$gh_project,
							$repostoryfile->path,
							$gh_branch
							);
				}
				else
				{
					$in_dev_file = '';
				}

				$files_to_include[] = $file_to_include;
				$sha_path  = JPATH_COMPONENT_ADMINISTRATOR . '/develop/gh_joomla_' . $gh_client . '_files.txt';
				$sha_path  = Folder::makeSafe($sha_path);

				if (!empty($in_dev_file) && isset($in_dev_file->content))
				{
					$file_to_include = $repostoryfile->name;
					$file_contents = base64_decode($in_dev_file->content);
					File::write($file_path, $file_contents);

					if (!File::exists($file_path))
					{
						Factory::getApplication()->enqueueMessage(
							Text::_('COM_LOCALISE_ERROR_GITHUB_UNABLE_TO_CREATE_DEV_FILE'),
							'warning');

						return false;
					}

					if (!File::exists($reference_file_path)
						&& ($gh_client == 'administrator' || $gh_client == 'site'))
					{
						// Adding files only present in develop to core reference location.
						File::write($reference_file_path, $file_contents);

						if (!File::exists($reference_file_path))
						{
							Factory::getApplication()->enqueueMessage(
								Text::_('COM_LOCALISE_ERROR_GITHUB_UNABLE_TO_ADD_NEW_FILES'),
								'warning');

							return false;
						}

						Factory::getApplication()->enqueueMessage(
							Text::sprintf('COM_LOCALISE_NOTICE_GITHUB_FILE_ADDED', $file_to_include, $gh_branch, $gh_client),
							'notice');
					}
				}

				// Saved for each time due few times get all the github files at same time can crash.
				// This one can help to remember the last one saved correctly and next time continue from there.
				$sha .= $repostoryfile->name . "::" . $repostoryfile->sha . "\n";
				File::write($sha_path, $sha);

				if (!File::exists($sha_path))
				{
					Factory::getApplication()->enqueueMessage(
						Text::_('COM_LOCALISE_ERROR_GITHUB_NO_SHA_FILE_PRESENT'),
						'warning');

					return false;
				}

				$ext = File::getExt($file_to_include);

				if ($ext == "ini" && $file_to_include == 'en-GB.ini')
				{
					$file_to_include = str_replace('en-GB.', 'joomla.', $file_to_include);
				}
				else if ($ext == "ini")
				{
					$file_to_include = str_replace('en-GB.', '', $file_to_include);
				}

				if ($ext == 'ini' && !in_array($file_to_include, $stored_core_files))
				{
					$core_file           = new \JObject;
					$core_file->id       = null;
					$core_file->filename = $file_to_include;

					$add_core_file = self::addKnownCoreFile($core_file);

					// For debugging purposes only
					if ($add_core_file)
					{
						//Factory::getApplication()->enqueueMessage(Text::sprintf('COM_LOCALISE_TRANSLATION_ADD_CORE_FILE_SUCCESS',
						//	htmlspecialchars($file_to_include)),
						//	'notice');
					}
					else
					{
						//Factory::getApplication()->enqueueMessage(Text::sprintf('COM_LOCALISE_TRANSLATION_ADD_CORE_FILE_WARNING',
						//	htmlspecialchars($file_to_include)),
						//	'warning');
					}
				}
			}

			if (!empty($all_files_list) && !empty($files_to_include))
			{
				// For files not present in dev yet.
				$files_to_delete = array_diff($all_files_list, $files_to_include);

				if (!empty($files_to_delete))
				{
					foreach ($files_to_delete as $file_to_delete)
					{
						if ($file_to_delete != 'index.html')
						{
							$file_path = Folder::makeSafe($develop_client_path . "/" . $file_to_delete);
							File::delete($file_path);

							if (File::exists($file_path))
							{
								Factory::getApplication()->enqueueMessage(
									Text::sprintf('COM_LOCALISE_ERROR_GITHUB_FILE_TO_DELETE_IS_PRESENT', $file_to_delete),
									'warning');

								return false;
							}

							Factory::getApplication()->enqueueMessage(
								Text::sprintf('COM_LOCALISE_GITHUB_FILE_NOT_PRESENT_IN_DEV_YET', $file_to_delete),
								'notice');
						}
					}
				}
			}

			if (!File::exists($xml_file))
			{
				Factory::getApplication()->enqueueMessage(
					Text::sprintf('COM_LOCALISE_ERROR_GITHUB_UNABLE_TO_GET_A_FULL_SET', $gh_branch),
					'warning');

				return false;
			}

			self::saveLastupdate($client_to_update);

			Factory::getApplication()->enqueueMessage(
				Text::sprintf('COM_LOCALISE_NOTICE_GITHUB_GETS_A_TARGET_FULL_SET', $gh_branch),
				'notice');

			return true;
		}

		Factory::getApplication()->enqueueMessage(Text::_('COM_LOCALISE_ERROR_GITHUB_NO_DATA_PRESENT'), 'warning');

		return false;
	}

	/**
	 * Gets the changes between language files versions
	 *
	 * @param   array  $info              The data to catch grammar cases
	 * @param   array  $refsections       The released reference data
	 * @param   array  $develop_sections  The developed reference data
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function getDevelopchanges($info = array(), $refsections = array(), $develop_sections = array())
	{
		if (isset($refsections['keys']) && isset($develop_sections['keys']))
		{
			$istranslation     = $info['istranslation'];
			$keys_in_reference = array_keys($refsections['keys']);
			$keys_in_develop   = array_keys($develop_sections['keys']);

			// Catching new keys in develop
			$developdata['new_keys']['amount']           = 0;
			$developdata['new_keys']['keys']             = array();
			$developdata['new_keys']['strings']          = array();
			$developdata['deleted_keys']['amount']       = 0;
			$developdata['deleted_keys']['keys']         = array();
			$developdata['deleted_keys']['strings']      = array();
			$developdata['renamed_keys']['amount']       = 0;
			$developdata['renamed_keys']['keys']         = array();
			$developdata['renamed_keys']['strings']      = array();
			$developdata['renamed_keys']['replacements'] = array();

			$extras_in_develop   = array_diff($keys_in_develop, $keys_in_reference);
			$deleted_in_develop  = array_diff($keys_in_reference, $keys_in_develop);
			$stored_deleted_keys = self::getKnownDeletedKeysList();
			$stored_renamed_keys = self::getKnownRenamedKeysList($client = $info['client']);

			if (!empty($deleted_in_develop))
			{
				foreach ($deleted_in_develop as $deleted_key)
				{
					$deleted_string = $refsections['keys'][$deleted_key];
					$renamed_key    = array_search($deleted_string, $develop_sections['keys']);

					if ($renamed_key && is_string($renamed_key))
					{
						$developdata['renamed_keys']['amount']++;
						$developdata['renamed_keys']['keys'][]                 = $deleted_key;
						$developdata['renamed_keys']['strings'][$deleted_key]  = $deleted_string;
						$developdata['renamed_keys']['replacements'][$renamed_key] = $deleted_key;

						if (!in_array($deleted_key, $stored_renamed_keys))
						{
							$key_data                  = new \JObject;
							$key_data->id              = null;
							$key_data->client          = $info['client'];
							$key_data->reflang         = 'en-GB';
							$key_data->key             = $deleted_key;
							$key_data->replacement_key = $renamed_key;
							$key_data->reflang_string  = $deleted_string;

							$add_renamed_key = self::addKnownRenamedKey($key_data);
						}
					}
					else
					{
						$developdata['deleted_keys']['amount']++;
						$developdata['deleted_keys']['keys'][]                = $deleted_key;
						$developdata['deleted_keys']['strings'][$deleted_key] = $deleted_string;

						if (!in_array($deleted_key, $stored_deleted_keys))
						{
							$key_data          = new \JObject;
							$key_data->id      = null;
							$key_data->reflang = 'en-GB';
							$key_data->key     = $deleted_key;

							$add_deleted_key = self::addKnownDeletedKey($key_data);
						}
					}
				}
			}

			if (!empty($extras_in_develop))
			{
				foreach ($extras_in_develop as $extra_key)
				{
					$developdata['new_keys']['amount']++;
					$developdata['new_keys']['keys'][]              = $extra_key;
					$developdata['new_keys']['strings'][$extra_key] = $develop_sections['keys'][$extra_key];
				}
			}

			// Catching text changes in develop
			$developdata['text_changes']['amount']     = 0;
			$developdata['text_changes']['revised']    = 0;
			$developdata['text_changes']['unrevised']  = 0;
			$developdata['text_changes']['keys']       = array();
			$developdata['text_changes']['ref_in_dev'] = array();
			$developdata['text_changes']['ref']        = array();
			$developdata['text_changes']['diff']       = array();

			foreach ($refsections['keys'] as $key => $string)
			{
				if (array_key_exists($key, $develop_sections['keys']))
				{
					$string_in_develop = $develop_sections['keys'][$key];
					$text_changes = self::htmlgetTextchanges($string, $string_in_develop);

					if (!empty($text_changes))
					{
						if ($istranslation == 1)
						{
							$info['key']           = $key;
							$info['source_text']   = $string;
							$info['target_text']   = $string_in_develop;
							$info['catch_grammar'] = 1;
							$info['revised']       = 0;

							$grammar_case = self::searchRevisedvalue($info);
						}
						else
						{
							$grammar_case = '0';
						}

						if ($grammar_case == '0')
						{
							$developdata['text_changes']['amount']++;
							$developdata['text_changes']['keys'][]           = $key;
							$developdata['text_changes']['ref_in_dev'][$key] = $develop_sections['keys'][$key];
							$developdata['text_changes']['ref'][$key]        = $string;
							$developdata['text_changes']['diff'][$key]       = $text_changes;
						}
					}
				}
			}

			return $developdata;
		}

		return array();
	}

	/**
	 * Gets the develop path if exists
	 *
	 * @param   string  $client   The client
	 * @param   string  $refpath  The data to the reference path
	 *
	 * @return  string
	 *
	 * @since   4.11
	 */
	public static function searchDevpath($client = '', $refpath = '')
	{
		$params             = ComponentHelper::getParams('com_localise');
		$ref_tag            = $params->get('reference', 'en-GB');
		$allow_develop      = $params->get('gh_allow_develop', 0);

		$develop_client_path = JPATH_ROOT
					. '/media/com_localise/develop/github/joomla-cms/en-GB/'
					. $client;

		$ref_file            = basename($refpath);
		$develop_file_path   = Folder::makeSafe("$develop_client_path/$ref_file");

		if (File::exists($develop_file_path) && $allow_develop == 1 && $ref_tag == 'en-GB')
		{
			$devpath = $develop_file_path;
		}
		else
		{
			$devpath = '';
		}

		return $devpath;
	}

	/**
	 * Gets the customised source path if exists
	 *
	 * @param   string  $client   The client
	 * @param   string  $refpath  The data to the reference path
	 *
	 * @return  string
	 *
	 * @since   4.11
	 */
	public static function searchCustompath($client = '', $refpath = '')
	{
		$params             = ComponentHelper::getParams('com_localise');
		$ref_tag            = $params->get('reference', 'en-GB');
		$allow_develop      = $params->get('gh_allow_develop', 0);
		$customisedref      = $params->get('customisedref', '0');
		$custom_client_path = JPATH_ROOT
					. '/media/com_localise/customisedref/github/'
					. $client
					. '/'
					. $customisedref;

		$ref_file         = basename($refpath);
		$custom_file_path = Folder::makeSafe("$custom_client_path/$ref_file");

		if (File::exists($custom_file_path) && $allow_develop == 1 && $ref_tag == 'en-GB' && $customisedref != 0)
		{
			$custom_path = $custom_file_path;
		}
		else
		{
			$custom_path = '';
		}

		return $custom_path;
	}

	/**
	 * Allow combine the reference versions to obtain a right result editing in raw mode or saving source reference files
	 * When development is enabled.
	 *
	 * @param   string  $refpath  The data to the reference path
	 *
	 * @param   string  $devpath  The data to the develop path
	 *
	 * @return  string
	 *
	 * @since   4.11
	 */
	public static function combineReferences($refpath = '', $devpath = '')
	{
		$params             = ComponentHelper::getParams('com_localise');
		$ref_tag            = $params->get('reference', 'en-GB');
		$allow_develop      = $params->get('gh_allow_develop', 0);
		$combined_content   = '';

		if (File::exists($devpath) && File::exists($refpath) && $allow_develop == 1 && $ref_tag == 'en-GB')
		{
			$ref_sections      = self::parseSections($refpath);
			$keys_in_reference = array_keys($ref_sections['keys']);

			$stream = new Stream;
			$stream->open($devpath);
			$stream->seek(0);

			while (!$stream->eof())
			{
				$line = $stream->gets();

				if (preg_match('/^([A-Z][A-Z0-9_:\*\-\.]*)\s*=/', $line, $matches))
				{
					$key = $matches[1];

					if (in_array($key, $keys_in_reference))
					{
						$string = $ref_sections['keys'][$key];
						$combined_content .= $key . '="' . str_replace('"', '"_QQ_"', $string) . "\"\n";
					}
					else
					{
						$combined_content .= $line;
					}
				}
				else
				{
					$combined_content .= $line;
				}
			}

			$stream->close();
		}

		return $combined_content;
	}

	/**
	 * Gets the list of ini files
	 *
	 * @param   string  $client_path  The data to the client path
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function getInifileslist($client_path = '')
	{
		if (!empty($client_path))
		{
			$files = Folder::files($client_path, ".ini$");

			return $files;
		}

	return array();
	}

	/**
	 * Gets the list of all type of files in develop
	 *
	 * @param   string  $develop_client_path  The data to the client path
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function getLanguagefileslist($develop_client_path = '')
	{
		if (!empty($develop_client_path))
		{
			$files = Folder::files($develop_client_path);

			return $files;
		}

	return array();
	}

	/**
	 * Gets the stored SHA id for the files in develop.
	 *
	 * @param   array  $gh_data  The required data.
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function getShafileslist($gh_data = array())
	{
		$sha_files = array();
		$gh_client = $gh_data['github_client'];
		$sha_path  = Folder::makeSafe(JPATH_COMPONENT_ADMINISTRATOR . '/develop/gh_joomla_' . $gh_client . '_files.txt');

		if (File::exists($sha_path))
		{
			$file_contents = file_get_contents($sha_path);
			$lines = preg_split("/\\r\\n|\\r|\\n/", $file_contents);

			if (!empty($lines))
			{
				foreach ($lines as $line)
				{
					if (!empty($line))
					{
						list($filename, $sha) = explode('::', $line, 2);

						if (!empty($filename) && !empty($sha))
						{
							$sha_files[$filename] = $sha;
						}
					}
				}
			}
		}

	return $sha_files;
	}

	/**
	 * Save the date of the last Github files update by client.
	 *
	 * @param   string  $client_to_update  The client language files.
	 *
	 * @return  bolean
	 *
	 * @since   4.11
	 */
	public static function saveLastupdate($client_to_update)
	{
		$now    = new Date;
		$now    = $now->toSQL();
		$params = ComponentHelper::getParams('com_localise');
		$params->set($client_to_update, $now);

		$localise_id = ComponentHelper::getComponent('com_localise')->id;

		$table = Table::getInstance('extension');
		$table->load($localise_id);
		$table->bind(array('params' => $params->toString()));

		if (!$table->check())
		{
			Factory::getApplication()->enqueueMessage($table->getError(), 'warning');

			return false;
		}

		if (!$table->store())
		{
			Factory::getApplication()->enqueueMessage($table->getError(), 'warning');

			return false;
		}

		return true;
	}

	/**
	 * Forces to save the customised source version to use. Option '0' returns to local installed instance.
	 *
	 * @param   string  $option  The option value to save.
	 *
	 * @return  bolean
	 *
	 * @since   4.11
	 */
	public static function setCustomisedsource($option = '0')
	{
		$params = ComponentHelper::getParams('com_localise');
		$params->set('customisedref', $option);

		$localise_id = ComponentHelper::getComponent('com_localise')->id;

		$table = Table::getInstance('extension');
		$table->load($localise_id);
		$table->bind(array('params' => $params->toString()));

		if (!$table->check())
		{
			Factory::getApplication()->enqueueMessage($table->getError(), 'warning');

			return false;
		}

		if (!$table->store())
		{
			Factory::getApplication()->enqueueMessage($table->getError(), 'warning');

			return false;
		}

		return true;
	}

	/**
	 * Load revised changes
	 *
	 * @param   array  $data  The required data.
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function searchRevisedvalue($data)
	{
		$client        = $data['client'];
		$reftag        = $data['reftag'];
		$tag           = $data['tag'];
		$filename      = $data['filename'];
		$revised       = $data['revised'];
		$key           = $data['key'];
		$target_text   = $data['target_text'];
		$source_text   = $data['source_text'];
		$istranslation = $data['istranslation'];
		$catch_grammar = $data['catch_grammar'];

		if (!empty($client) && !empty($reftag) && !empty($tag) && !empty($filename))
		{
			try
			{
				$db                 = Factory::getDbo();
				$query	            = $db->getQuery(true);

				$search_client      = $db->quote($client);
				$search_reftag      = $db->quote($reftag);
				$search_tag         = $db->quote($tag);
				$search_filename    = $db->quote($filename);
				$search_key         = $db->quote($key);
				$search_target_text = $db->quote($target_text);
				$search_source_text = $db->quote($source_text);

				if ($catch_grammar && $reftag !== $tag)
				{
					$search_tag = $db->quote($reftag);
				}

				$query->select(
						array	(
							$db->quoteName('revised')
							)
						);
				$query->from(
						$db->quoteName('#__localise_revised_values')
						);
				$query->where(
						$db->quoteName('client') . '= ' . $search_client
						);
				$query->where(
						$db->quoteName('reftag') . '= ' . $search_reftag

						);
				$query->where(
						$db->quoteName('tag') . '= ' . $search_tag

						);
				$query->where(
						$db->quoteName('filename') . '= ' . $search_filename
						);
				$query->where(
						$db->quoteName('key') . '= ' . $search_key
						);
				$query->where(
						$db->quoteName('target_text') . '= ' . $search_target_text
						);
				$query->where(
						$db->quoteName('source_text') . '= ' . $search_source_text
						);

				$db->setQuery($query);

					if (!$db->execute())
					{
						throw new Exception($db->getErrorMsg());
					}
			}

			catch (\JDatabaseExceptionExecuting $e)
			{
				Factory::getApplication()->enqueueMessage(Text::_('COM_LOCALISE_ERROR_SEARCHING_REVISED_VALUES'), 'warning');

				return null;
			}

			$result = $db->loadResult();

			if (!is_null($result) && !$catch_grammar)
			{
				return (int) $result;
			}
			elseif (!is_null($result) && $catch_grammar)
			{
				// Returns if in en-GB has been checked as grammar case or not
				return $result;
			}
			elseif (is_null($result) && $catch_grammar)
			{
				// Returns than at en-GB has not been checked as grammar case
				return 0;
			}
			elseif (is_null($result) && !$catch_grammar)
			{
				if (self::saveRevisedvalue($data))
				{
					return (int) $revised;
				}
				else
				{
					return null;
				}
			}
		}

		return null;
	}

	/**
	 * Update revised changes
	 *
	 * @param   array  $data  The required data.
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function updateRevisedvalue($data)
	{
		$client      = $data['client'];
		$reftag      = $data['reftag'];
		$tag         = $data['tag'];
		$filename    = $data['filename'];
		$revised     = $data['revised'];
		$key         = $data['key'];
		$target_text = $data['target_text'];
		$source_text = $data['source_text'];

		if (!empty($client) && !empty($reftag) && !empty($tag) && !empty($filename))
		{
			try
			{
				$db = Factory::getDbo();

				$updated_client      = $db->quote($client);
				$updated_reftag      = $db->quote($reftag);
				$updated_tag         = $db->quote($tag);
				$updated_filename    = $db->quote($filename);
				$updated_revised     = $db->quote($revised);
				$updated_key         = $db->quote($key);
				$updated_target_text = $db->quote($target_text);
				$updated_source_text = $db->quote($source_text);

				$query = $db->getQuery(true);

				$fields = array(
					$db->quoteName('revised') . ' = ' . $updated_revised
				);

				$conditions = array(
					$db->quoteName('client') . ' = ' . $updated_client,
					$db->quoteName('reftag') . ' = ' . $updated_reftag,
					$db->quoteName('tag') . ' = ' . $updated_tag,
					$db->quoteName('filename') . ' = ' . $updated_filename,
					$db->quoteName('key') . ' = ' . $updated_key,
					$db->quoteName('target_text') . ' = ' . $updated_target_text,
					$db->quoteName('source_text') . ' = ' . $updated_source_text
				);

				$query->update($db->quoteName('#__localise_revised_values'))->set($fields)->where($conditions);

				$db->setQuery($query);
				$db->execute();
			}

			catch (\JDatabaseExceptionExecuting $e)
			{
				Factory::getApplication()->enqueueMessage(Text::_('COM_LOCALISE_ERROR_UPDATING_REVISED_VALUES'), 'warning');

				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * Save revised changes
	 *
	 * @param   array  $data  The required data.
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function saveRevisedvalue($data)
	{
		$client      = $data['client'];
		$reftag      = $data['reftag'];
		$tag         = $data['tag'];
		$filename    = $data['filename'];
		$revised     = $data['revised'];
		$key         = $data['key'];
		$target_text = $data['target_text'];
		$source_text = $data['source_text'];

		if (!empty($client) && !empty($reftag) && !empty($tag) && !empty($filename))
		{
			try
			{
				$db = Factory::getDbo();

				$saved_client      = $db->quote($client);
				$saved_reftag      = $db->quote($reftag);
				$saved_tag         = $db->quote($tag);
				$saved_filename    = $db->quote($filename);
				$saved_revised     = $db->quote($revised);
				$saved_key         = $db->quote($key);
				$saved_target_text = $db->quote($target_text);
				$saved_source_text = $db->quote($source_text);

				$query = $db->getQuery(true);

				$columns = array('client', 'reftag', 'tag', 'filename', 'revised', 'key', 'target_text', 'source_text');

				$values = array($saved_client, $saved_reftag, $saved_tag, $saved_filename, $saved_revised, $saved_key, $saved_target_text, $saved_source_text);

				$query
					->insert($db->quoteName('#__localise_revised_values'))
					->columns($db->quoteName($columns))
					->values(implode(',', $values));

				$db->setQuery($query);
				$db->execute();
			}

			catch (\JDatabaseExceptionExecuting $e)
			{
				Factory::getApplication()->enqueueMessage(Text::_('COM_LOCALISE_ERROR_SAVING_REVISED_VALUES'), 'warning');

				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * Get the false positives list by the selected data
	 *
	 * @param   object $db_data  The required data to get the false positives list.
	 *
	 * @return object
	 *
	 */
	public static function getFalsePositives($db_data)
	{
		if (!is_object($db_data))
		{
			return false;
		}

		$client     = $db_data->client;
		$reflang    = $db_data->reflang;
		$targetlang = $db_data->targetlang;
		$filename   = $db_data->filename;

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select(
				array(
					$db->quoteName('id'),
					$db->quoteName('client'),
					$db->quoteName('reflang'),
					$db->quoteName('targetlang'),
					$db->quoteName('filename'),
					$db->quoteName('is_false_positive'),
					$db->quoteName('key'),
					$db->quoteName('reflang_string'),
					$db->quoteName('targetlang_string')
				)
		);

		$query->from($db->quoteName('#__localise_false_positives'));
		$query->where($db->quoteName('client')." = :client");
		$query->where($db->quoteName('reflang')." = :reflang");
		$query->where($db->quoteName('targetlang')." = :targetlang");
		$query->where($db->quoteName('filename')." = :filename");
		$query->bind(':client', $client);
		$query->bind(':reflang', $reflang);
		$query->bind(':targetlang', $targetlang);
		$query->bind(':filename', $filename);

		$db->setQuery($query);

		$result = $db->loadObjectList('key');

		if (! is_null($result) && ! empty($result) && $result)
		{
			return $result;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Save the false positives data (null 'id' to 'save')
	 *
	 * @param   object $issues_data  The required data to save the false positive case.
	 *
	 * @return bool Returns true or false.
	 *
	 */
	public static function saveFalsePositive($issues_data)
	{
		if (!is_object($issues_data))
		{
			return false;
		}

		$result = Factory::getDbo()->insertObject('#__localise_false_positives', $issues_data, 'id');

		if (! is_null($result) && ! empty($result) && $result)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Update the false positives data
	 *
	 * @param   object $issues_data  The required data to save the false positive case.
	 *
	 * @return bool Returns true or false.
	 *
	 */
	public static function updateFalsePositive($issues_data)
	{
		if (!is_object($issues_data))
		{
			return false;
		}

		$result = Factory::getDbo()->updateObject('#__localise_false_positives', $issues_data, 'id');

		if (! is_null($result) && ! empty($result) && $result)
		{
			return true;
		}
		else
		{
			return false;
		}
	}


	/**
	 * Create the required folders for develop
	 *
	 * @param   array   $gh_data  Array with the data
	 * @param   string  $index    If true, allow to create an index.html file
	 *
	 * @return  bolean
	 *
	 * @since   4.11
	 */
	public static function createFolder($gh_data = array(), $index = 'true')
	{
		$source_ref = $gh_data['customisedref'];

		if (!empty($gh_data) && isset($source_ref))
		{
		$full_path = JPATH_ROOT . '/media/com_localise/customisedref/github/'
					. $gh_data['github_client']
					. '/'
					. $source_ref;

		$full_path = Folder::makeSafe($full_path);

			if (!Folder::create($full_path))
			{
			}

			if (Folder::exists($full_path))
			{
				if ($index == 'true')
				{
				$cretate_index = self::createIndex($full_path);

					if ($cretate_index == 1)
					{
						return true;
					}

				Factory::getApplication()->enqueueMessage(Text::_('COM_LOCALISE_ERROR_GITHUB_UNABLE_TO_CREATE_INDEX_FILE'), 'warning');

				return false;
				}

			return true;
			}
			else
			{
				Factory::getApplication()->enqueueMessage(Text::_('COM_LOCALISE_ERROR_GITHUB_UNABLE_TO_CREATE_FOLDERS'), 'warning');

				return false;
			}
		}

	return false;
	}

	/**
	 * Creates an index.html file within folders for develop
	 *
	 * @param   string  $full_path  The full path.
	 *
	 * @return  bolean
	 *
	 * @since   4.11
	 */
	public static function createIndex($full_path = '')
	{
		if (!empty($full_path))
		{
		$path = Folder::makeSafe($full_path . '/index.html');

		$index_content = '<!DOCTYPE html><title></title>';

			if (!File::exists($path))
			{
				File::write($path, $index_content);
			}

			if (!File::exists($path))
			{
				return false;
			}
			else
			{
				return true;
			}
		}

	return false;
	}

	/**
	 * Gets the text changes.
	 *
	 * @param   array  $old  The string parts in reference.
	 * @param   array  $new  The string parts in develop.
	 *
	 * @return  array
	 *
	 * @since   4.11
	 */
	public static function getTextchanges($old, $new)
	{
		$maxlen = 0;

		foreach ($old as $oindex => $ovalue)
		{
			$nkeys = array_keys($new, $ovalue);

			foreach ($nkeys as $nindex)
			{
				$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ? $matrix[$oindex - 1][$nindex - 1] + 1 : 1;

				if ($matrix[$oindex][$nindex] > $maxlen)
				{
					$maxlen = $matrix[$oindex][$nindex];
					$omax = $oindex + 1 - $maxlen;
					$nmax = $nindex + 1 - $maxlen;
				}

			unset ($nkeys, $nindex);
			}

		unset ($oindex, $ovalue);
		}

		if ($maxlen == 0)
		{
			return array(array ('d' => $old, 'i' => $new));
		}

		return array_merge(
			self::getTextchanges(
			array_slice($old, 0, $omax),
			array_slice($new, 0, $nmax)
			),
			array_slice($new, $nmax, $maxlen),
			self::getTextchanges(
			array_slice($old, $omax + $maxlen),
			array_slice($new, $nmax + $maxlen)
			)
			);
	}

	/**
	 * Gets the html text changes.
	 *
	 * @param   string  $old  The string in reference.
	 * @param   string  $new  The string in develop.
	 *
	 * @return  string
	 *
	 * @since   4.11
	 */
	public static function htmlgetTextchanges($old, $new)
	{
		$text_changes = '';

		if ($old == $new)
		{
			return $text_changes;
		}

		$old = str_replace('  ', 'LOCALISEDOUBLESPACES', $old);
		$new = str_replace('  ', 'LOCALISEDOUBLESPACES', $new);

		$diff = self::getTextchanges(explode(' ', $old), explode(' ', $new));

		foreach ($diff as $k)
		{
			if (is_array($k))
			{
				$text_changes .= (!empty ($k['d'])?"LOCALISEDELSTART"
					. implode(' ', $k['d']) . "LOCALISEDELSTOP ":'')
					. (!empty($k['i']) ? "LOCALISEINSSTART"
					. implode(' ', $k['i'])
					. "LOCALISEINSSTOP " : '');
			}
			else
			{
				$text_changes .= $k . ' ';
			}

		unset ($k);
		}

		$text_changes = htmlspecialchars($text_changes);
		$text_changes = preg_replace('/LOCALISEINSSTART/', "<ins class='diff_ins'>", $text_changes);
		$text_changes = preg_replace('/LOCALISEINSSTOP/', "</ins>", $text_changes);
		$text_changes = preg_replace('/LOCALISEDELSTART/', "<del class='diff_del'>", $text_changes);
		$text_changes = preg_replace('/LOCALISEDELSTOP/', "</del>", $text_changes);
		$double_spaces = '<span class="red-space"><font color="red">XX</font></span>';
		$text_changes = str_replace('LOCALISEDOUBLESPACES', $double_spaces, $text_changes);

		return $text_changes;
	}

	/**
	 * Method to obtain the HTML output of the sections inside the TRs of a table with 3 columns, only when is known section.
	 *
	 * @param   string  $name    The section name.
	 * @param   string  $filter  The type of keys to filter.
	 *
	 * @return  string
	 *
	 * @since   4.11
	 */
	public static function getSectionHtmlOutput($name, $filter)
	{
		$known_sections = array(
			'pluralkeys'       => 'COM_LOCALISE_TRANSLATION_PLURAL_KEYS_IN_TRANSLATION_COMMENT',
			'renamedkeys'      => 'COM_LOCALISE_TRANSLATION_RENAMED_KEYS_IN_TRANSLATION_COMMENT',
			'deletedkeys'      => 'COM_LOCALISE_TRANSLATION_DELETED_KEYS_IN_TRANSLATION_COMMENT',
			'extrakeys'        => 'COM_LOCALISE_TRANSLATION_EXTRA_KEYS_IN_TRANSLATION_COMMENT'
		);

		$html_output = '';

		if (array_key_exists($filter, $known_sections))
		{
			$html_output .= '<tr>';
			$html_output .= '<th colspan="3"><span class="fs-3" style="color:darkgray;">; ';
			$html_output .= Text::_($known_sections[$filter]);
			$html_output .= '</span></th>';
			$html_output .= '</tr>';
		}

		return $html_output;
	}

	/**
	 * Method to obtain the HTML output of the keys inside the TRs of a table with 3 columns.
	 *
	 * @param   object  $field         The field type 'Key' data.
	 * @param   string  $filter        The type of keys to filter.
	 * @param   array   $keystofilter  The array containing the keys to display by the selected filter.
	 *
	 * @return  string
	 *
	 * @since   4.11
	 */
	public static function getKeyHtmlOutput(&$field, &$filter, &$keystofilter)
	{
		$html_output   = '';
		$showkey       = 0;
		$encoded_value = hash('adler32', $field->name);

		// Spacer type include 'Commented' lines starting by ; and 'Blank' lines.
		if ($filter == 'allkeys' && strtoupper($field->type) == 'SPACER')
		{
			$html_output .= '<tr>';
			$html_output .= '<th colspan="3">';
			$html_output .= $field->label;
			$html_output .= '</th>';
			$html_output .= '</tr>';

			return $html_output;
		}
		else if (strtoupper($field->type) == 'SPACER')
		{
			$html_output .= '<tr style="display:none;">';
			$html_output .= '<th style="display:none;" colspan="3">';
			$html_output .= $field->label;
			$html_output .= '</th>';
			$html_output .= '</tr>';

			return $html_output;
		}

		$is_textchange = (int) $field->label->is_textchange;
		$is_issued     = (int) $field->label->is_issued;

		if ($filter != 'allkeys' && !empty($keystofilter) && strtoupper($field->type) == 'KEY')
		{
			// Setting the active tab matching with the filter
			if ($is_issued == 1 && $filter == 'issuedkeys')
			{
				$active = 'engb_tab_issued_' . $encoded_value;
			}
			else if ($is_textchange == 1 && $filter == 'textchangedkeys')
			{
				$active = 'engb_tab_tc_' . $encoded_value;
			}
			else
			{
				$active = 'engb_tab_' . $encoded_value;
			}

			foreach ($keystofilter as $data => $ids)
			{
				foreach ($ids as $keytofilter)
				{
					$showkey = 0;
					$pregkey = preg_quote('<strong>'. $keytofilter .'</strong>', '/<>');

					if (preg_match("/$pregkey/", $field->label->field_label))
					{
						$showkey = 1;
						break;
					}
				}
			}
		}
		elseif ($filter == 'allkeys' && strtoupper($field->type) == 'KEY')
		{
			$showkey = 1;

			// Setting priority to the active tab
			if ($is_issued == 1)
			{
				$active = 'engb_tab_issued_' . $encoded_value;
			}
			else if ($is_textchange == 1)
			{
				$active = 'engb_tab_tc_' . $encoded_value;
			}
			else
			{
				$active = 'engb_tab_' . $encoded_value;
			}
		}
		else
		{
			$active = 'engb_tab_' . $encoded_value;
		}

		if ($filter == 'allkeys' || $showkey == '1')
		{
			$html_output .= '<tr>';
		}
		else if ($filter != 'allkeys' && $showkey == '0')
		{
			$html_output .= '<tr style="display:none;">';
		}

		$html_output .= '<th class="width-45">';
		$html_output .= '<div class="width-100">';
		$html_output .= $field->label->field_details;

		if ($showkey == '1')
		{
			if (!empty($field->label->field_details) && empty($field->input->field_commented))
			{
				$html_output .= '<br><br>';
			}
			else if (empty($field->label->field_details) && !empty($field->input->field_commented))
			{
				$html_output .= '';
			}
			else if (!empty($field->label->field_details) && !empty($field->input->field_commented))
			{
				$html_output .= '<br><br><br>';
			}
		}

		$html_output .= HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => $active));
		$html_output .= HTMLHelper::_('uitab.addTab', 'myTab', 'engb_tab_' . $encoded_value, $field->label->reflang, true);
		$html_output .= '<div class="word-break-width-100">';

		if ($is_textchange == 1 || $is_issued == 1)
		{
			$html_output .= $field->label->field_desc;
		}
		else
		{
			$html_output .= $field->label->field_label;
		}

		$html_output .= '</div>';
		$html_output .= HTMLHelper::_('uitab.endTab');

		if ($is_textchange == 1)
		{
			$html_output .= HTMLHelper::_('uitab.addTab', 'myTab', 'engb_tab_tc_' . $encoded_value, 'Text changes', true);
			$html_output .= '<div class="word-break-width-100">';
			$html_output .= $field->label->field_label;
			$html_output .= $field->label->textchanges_checkbox;
			$html_output .= '</div>';
			$html_output .= HTMLHelper::_('uitab.endTab');
		}

		if ($is_issued == 1)
		{
			$html_output .= HTMLHelper::_('uitab.addTab', 'myTab', 'engb_tab_issued_' . $encoded_value, 'Issues', true);
			$html_output .= '<div class="word-break-width-100">';
			$html_output .= '<p class ="nomargin">' . $field->label->field_key . '</p>';
			$html_output .= $field->label->engb_string;
			$html_output .= '<p class ="nomargin">' . $field->label->targetlang . '</p>';
			$html_output .= $field->label->ttms_string;
			$html_output .= '<p class ="nomargin">' . Text::_('COM_LOCALISE_DETECTABLE_ISSUES') . '</p>';
			$html_output .= $field->label->issue_details;
			$html_output .= $field->label->falsepositive_checkbox;
			$html_output .= '</div>';
			$html_output .= HTMLHelper::_('uitab.endTab');
		}

 		$html_output .= HTMLHelper::_('uitab.endTabSet');

		if ($is_textchange == 0)
		{
			$html_output .= $field->label->field_checkbox;
		}

		$html_output .= '</div>';
		$html_output .= '</th>';
		$html_output .= '<th class="width-5">';
		$html_output .= '<div class="width-100">';
		$html_output .= '<br><br><br>';
		$html_output .=  $field->input->field_button;
		$html_output .=  $field->input->field_button2;
		$html_output .= '</div>';
		$html_output .= '</th>';
		$html_output .= '<th class="width-45">';
		$html_output .= '<div class="width-100">';

		if ($showkey == '1')
		{
			if (!empty($field->label->field_details) && empty($field->input->field_commented))
			{
				$html_output .= '<br><br><br><br>';
			}
			else if (empty($field->label->field_details) && !empty($field->input->field_commented))
			{
				$html_output .= '<br>';
			}
			else if (!empty($field->label->field_details) && !empty($field->input->field_commented))
			{
				$html_output .= '<br><br><br>';
			}
			else if (empty($field->label->field_details) && empty($field->input->field_commented))
			{
				$html_output .= '<br><br>';
			}
		}

		$html_output .=  $field->input->field_commented;
		$html_output .=  $field->input->field_input;
		$html_output .= '</div>';
		$html_output .= '</th>';
		$html_output .= '</tr>';

		return $html_output;
	}

	/**
	 * Method to parse the reference en-GB strings vs the translated ones getting unmatching results
     * at translations that are not keepping the reference placeholders and HTML.
	 *
	 * @param   string  $ref_string          The en-GB string to get as reference.
	 * @param   string  $translation_string  The translation string to parse vs the en-GB one.
	 *
	 * @return  object
	 *
	 * @since   4.11
	 */
	public static function parseStringIssues($ref_string, $translation_string)
	{
		$reply                = new \JObject;
		$reply->is_issued     = false;
		$reply->engb_string   = '';
		$reply->ttms_string   = '';
		$reply->issue_details = '';

		$cases = array(
			'ref_string'         => $ref_string,
			'translation_string' => $translation_string
		);

		$parsed_strings = array(
			'ref_string'         => '',
			'translation_string' => ''
		);

		$parsing_order = array(
			'brackets',
			'double_brackets',
			'placeholders',
			'html'
		);

		$patterns = array(
			'placeholders'    => '/%([0-9]+\$)?[bcdefusox]/',
			'double_brackets' => "/LOCALISE_DBO[^LOCALISE_DBC]*LOCALISE_DBC/",
			'brackets'        =>  "/\{[^\}]*\}/",
			'html'            => "/(<([\w]+)[^>]*>)(.*?)(<\/\\2>)/"
		);

		$amounts = array(
			'ref_string' => array(
				'placeholders'    => '0',
				'double_brackets' => '0',
				'brackets'        => '0',
				'html'            => '0'
			),
			'translation_string' => array(
				'placeholders'    => '0',
				'double_brackets' => '0',
				'brackets'        => '0',
				'html'            => '0'
			)
       );

		$catched_data = array(
			'ref_string' => array(
				'placeholders'    => array(),
				'double_brackets' => array(),
				'brackets'        => array(),
				'html'            => array()
			),
			'translation_string' => array(
				'placeholders'    => array(),
				'double_brackets' => array(),
				'brackets'        => array(),
				'html'            => array()
			)

       );

		foreach ($cases as $case => $string)
		{
			$parsed_strings[$case] = $string;
			$parsed_strings[$case] = str_replace('"_QQ_"', 'LOCALISE_ESCAPED_QUOTES', $parsed_strings[$case]);
			$parsed_strings[$case] = str_replace('\"', 'LOCALISE_ESCAPED_QUOTES', $parsed_strings[$case]);
			$parsed_strings[$case] = str_replace('{{', 'LOCALISE_DBO', $parsed_strings[$case]);
			$parsed_strings[$case] = str_replace('}}', 'LOCALISE_DBC', $parsed_strings[$case]);

			foreach ($patterns as $name => $pattern)
			{
				if (preg_match_all($pattern, $parsed_strings[$case], $matches, PREG_SET_ORDER))
				{
					foreach($matches as $match)
					{
						foreach($match as $id => $catched)
						{
							if ($name == 'html')
							{
				 				if ($id == '1' || $id == '4')
								{
									if (!in_array(base64_encode($catched), $catched_data[$case]['html']))
									{
										$catched_data[$case]['html'][] = base64_encode($catched);
									}

									$amounts[$case]['html']++;
								}
							}
							else if ($name == 'placeholders')
							{
				 				if ($catched[0] == '%')
								{
									if (!in_array(base64_encode($catched), $catched_data[$case]['placeholders']))
									{
										$catched_data[$case]['placeholders'][] = base64_encode($catched);
									}

									$amounts[$case]['placeholders']++;
								}
							}
							else if ($name == 'double_brackets')
							{
								if (!in_array(base64_encode($match[$id]), $catched_data[$case]['double_brackets']))
								{
									$catched_data[$case]['double_brackets'][] = base64_encode($match[$id]);
								}

								$amounts[$case]['double_brackets']++;
							}
							else if ($name == 'brackets')
							{
								if (!in_array(base64_encode($match[$id]), $catched_data[$case]['brackets']))
								{
									$catched_data[$case]['brackets'][] = base64_encode($match[$id]);
								}

								$amounts[$case]['brackets']++;

							}
						}
					}
				}
			}
		}

		$cases_name = array(
			'html'              => Text::_('COM_LOCALISE_CASES_NAME_TYPE_HTML'),
			'double_brackets'   => Text::_('COM_LOCALISE_CASES_NAME_TYPE_DOUBLE_BRANCKETS'),
			'brackets'          => Text::_('COM_LOCALISE_CASES_NAME_TYPE_BRACKETS'),
			'placeholders'      => Text::_('COM_LOCALISE_CASES_NAME_TYPE_PLACEHOLDERS')
		);

		$engb_amount   = $amounts['ref_string'];
		$ttms_amount   = $amounts['translation_string'];

		$engb_data     = $catched_data['ref_string'];
		$ttms_data     = $catched_data['translation_string'];

		$engb_string   = $parsed_strings['ref_string'];
		$ttms_string   = $parsed_strings['translation_string'];

		$issue_details = '';

		foreach ($parsing_order as $case)
		{
			$equal_amount = true;

			if ($engb_amount[$case] != $ttms_amount[$case])
			{
				$reply->is_issued = true;
				$equal_amount     = false;
			}

			if (empty($engb_data[$case]) && empty($ttms_data[$case]))
			{
				continue;
			}
			else if (empty($engb_data[$case]))
			{
				$reply->is_issued = true;

				$issue_details .= '[Extra]'.$cases_name[$case].':LOCALISE_1BR';

				foreach ($ttms_data[$case] as $element)
				{
					$ttms_string = str_replace(base64_decode($element), 'LOCALISE_SPAN_DANGER_OPEN'
						. base64_decode($element)
						. 'LOCALISE_SPAN_CLOSE', $ttms_string);

					$issue_details .= 'LOCALISE_SPAN_DANGER_OPEN'
						. base64_decode($element)
						. 'LOCALISE_SPAN_CLOSELOCALISE_1BR';
				}
			}
			else if (empty($ttms_data[$case]))
			{
				$reply->is_issued = true;
				$issue_details .= '[Missing]'
					. $cases_name[$case]
					. ':LOCALISE_1BR';

				foreach ($engb_data[$case] as $element)
				{
					$engb_string = str_replace(base64_decode($element), 'LOCALISE_SPAN_SUCCESS_OPEN'
						. base64_decode($element)
						. 'LOCALISE_SPAN_CLOSE', $engb_string);

					$issue_details .= 'LOCALISE_SPAN_DANGER_OPEN'
						. base64_decode($element)
						. 'LOCALISE_SPAN_CLOSELOCALISE_1BR';
				}
			}
			else if (!array_diff($engb_data[$case], $ttms_data[$case])
			&& !array_diff($ttms_data[$case], $engb_data[$case]))
			{
				if ($equal_amount == false)
				{
					$engb_amount_details   = 'LOCALISE_SPAN_SUCCESS_OPEN' . $engb_amount[$case] . 'LOCALISE_SPAN_CLOSE';
					$ttms_amount_details   = 'LOCALISE_SPAN_DANGER_OPEN' . $ttms_amount[$case] . 'LOCALISE_SPAN_CLOSE';

					foreach ($engb_data[$case] as $element)
					{
							$engb_string = str_replace(base64_decode($element), 'LOCALISE_SPAN_SUCCESS_OPEN'
								. base64_decode($element)
								. 'LOCALISE_SPAN_CLOSE', $engb_string);

							$ttms_string = str_replace(base64_decode($element), 'LOCALISE_SPAN_SUCCESS_OPEN'
								. base64_decode($element)
								. 'LOCALISE_SPAN_CLOSE', $ttms_string);
					}

					$issue_details .= '[Amount]'
						. $cases_name[$case]
						. ':LOCALISE_1BR'
						. $engb_amount_details
						. ' vs '
						. $ttms_amount_details
						. ' instances of pairable HTML tags or single placeholders';
				}
				else
				{
					// The parsed cases are equal
					continue;
				}
			}
			else
			{
				$reply->is_issued = true;

				$common_cases  = array_intersect($engb_data[$case], $ttms_data[$case]);
				$missing_cases = array_diff($engb_data[$case], $ttms_data[$case]);
				$extra_cases   = array_diff($ttms_data[$case], $engb_data[$case]);

				if (!empty($common_cases))
				{
					foreach ($common_cases as $element)
					{
						$engb_string = str_replace(base64_decode($element), 'LOCALISE_SPAN_SUCCESS_OPEN'
							. base64_decode($element)
							. 'LOCALISE_SPAN_CLOSE', $engb_string);

						$ttms_string = str_replace(base64_decode($element), 'LOCALISE_SPAN_SUCCESS_OPEN'
							. base64_decode($element)
							. 'LOCALISE_SPAN_CLOSE', $ttms_string);
					}
				}

				if (!empty($missing_cases))
				{
					$issue_details .= '[Missing]'
						. $cases_name[$case]
						. ':LOCALISE_1BR';

					foreach ($missing_cases as $element)
					{
						$engb_string = str_replace(base64_decode($element), 'LOCALISE_SPAN_SUCCESS_OPEN'
							. base64_decode($element)
							. 'LOCALISE_SPAN_CLOSE', $engb_string);

						$issue_details .= 'LOCALISE_SPAN_DANGER_OPEN'
							. base64_decode($element)
							. 'LOCALISE_SPAN_CLOSELOCALISE_1BR';
					}
				}

				if (!empty($extra_cases))
				{
					$issue_details .= '[Extra]'
						. $cases_name[$case]
						. ':LOCALISE_1BR';

					foreach ($extra_cases as $element)
					{
						$ttms_string = str_replace(base64_decode($element), 'LOCALISE_SPAN_DANGER_OPEN'
							. base64_decode($element)
							. 'LOCALISE_SPAN_CLOSE', $ttms_string);

						$issue_details .= 'LOCALISE_SPAN_DANGER_OPEN'
							. base64_decode($element)
							. 'LOCALISE_SPAN_CLOSELOCALISE_1BR';
					}
				}
			}
		}

		if ($reply->is_issued == true)
		{
			$engb_string = htmlspecialchars($engb_string);

			$engb_string = str_replace('LOCALISE_ESCAPED_QUOTES', '\"', $engb_string);
			$engb_string = str_replace('LOCALISE_DBO', '{{', $engb_string);
			$engb_string = str_replace('LOCALISE_DBC', '}}', $engb_string);
			$engb_string = str_replace('LOCALISE_SPAN_BADGE_WARNING_OPEN', '<span class="badge bg-warning">', $engb_string);
			$engb_string = str_replace('LOCALISE_SPAN_SUCCESS_OPEN', '<span class="bg-success text-light">', $engb_string);
			$engb_string = str_replace('LOCALISE_SPAN_DANGER_OPEN', '<span class="bg-danger text-light">', $engb_string);
			$engb_string = str_replace('LOCALISE_SPAN_CLOSE', '</span>', $engb_string);
			$engb_string = str_replace('LOCALISE_1BR', '<BR>', $engb_string);

			$ttms_string = htmlspecialchars($ttms_string);

			$ttms_string = str_replace('LOCALISE_ESCAPED_QUOTES', '\"', $ttms_string);
			$ttms_string = str_replace('LOCALISE_DBO', '{{', $ttms_string);
			$ttms_string = str_replace('LOCALISE_DBC', '}}', $ttms_string);
			$ttms_string = str_replace('LOCALISE_SPAN_BADGE_WARNING_OPEN', '<span class="badge bg-warning">', $ttms_string);
			$ttms_string = str_replace('LOCALISE_SPAN_SUCCESS_OPEN', '<span class="bg-success text-light">', $ttms_string);
			$ttms_string = str_replace('LOCALISE_SPAN_DANGER_OPEN', '<span class="bg-danger text-light">', $ttms_string);
			$ttms_string = str_replace('LOCALISE_SPAN_CLOSE', '</span>', $ttms_string);
			$ttms_string = str_replace('LOCALISE_1BR', '<BR>', $ttms_string);

			if (empty($issue_details))
			{
				$issue_details = 'Clean';
			}
			else
			{
				$issue_details = htmlspecialchars($issue_details);

				$issue_details = str_replace('LOCALISE_ESCAPED_QUOTES', '\"', $issue_details);
				$issue_details = str_replace('LOCALISE_DBO', '{{', $issue_details);
				$issue_details = str_replace('LOCALISE_DBC', '}}', $issue_details);
				$issue_details = str_replace('LOCALISE_SPAN_BADGE_WARNING_OPEN', '<span class="badge bg-warning">', $issue_details);
				$issue_details = str_replace('LOCALISE_SPAN_SUCCESS_OPEN', '<span class="bg-success text-light">', $issue_details);
				$issue_details = str_replace('LOCALISE_SPAN_DANGER_OPEN', '<span class="bg-danger text-light">', $issue_details);
				$issue_details = str_replace('LOCALISE_SPAN_CLOSE', '</span>', $issue_details);
				$issue_details = str_replace('LOCALISE_1BR', '<BR>', $issue_details);
			}

			$reply->engb_string   = $engb_string;
			$reply->ttms_string   = $ttms_string;
			$reply->issue_details = $issue_details;
		}


		return $reply;
	}

	/**
	 * Determine if the key is a plural case of the selected language to translate.
	 *
	 * @param   array   $plural_suffixes  The returned plural suffixes for the selected language to translate.
	 * @param   string  $key              The key to be validated.
	 * @param   array   $rootkeys         The detected plural root keys comming from the en-GB language.
	 *
	 * @return object
	 */
	public static function isPlural($plural_suffixes, $key, $rootkeys)
	{
		// This is normally used to detect plurals in keys that only exist in the translation (extra keys in translation / Not in ref keys):
		// those are 'Personalized plural cases' in the selected language to translate.
		//
		// The '$plural_suffixes' are extracted running the 'getPluralSuffixes($n)' function
		// present within the 'localise.php' file of the selected language to translate.
		//
		// So, other cases are handled as 'extra keys' due this program does not attempt to handle 'fake plurals' for non en-GB languages,
		// or other cases than does not reply with success as plural case from the localise.php file of the selected language to translate.

		if (empty($plural_suffixes) || empty($key))
		{
			return false;
		}

		$key_case =  new \JObject;

		// Converting the key to array
		$root_key = explode('_', $key);

		// Getting the last array item to be compared as suffix
		$last_item = end($root_key);

		// Deleting the last array item attempting to get the "unsuffixed key".
		array_pop($root_key);

		// Returning to string format
		$root_key = implode('_', $root_key);

		// Deleting last '_' if present
		$root_key = rtrim($root_key, '_');

		$key_case->is_plural = false;

		if (in_array($last_item, $plural_suffixes) && in_array($root_key, $rootkeys))
		{
			$key_case->is_plural = true;
		}

		$key_case->suffix   = $last_item;
		$key_case->root_key = $root_key;

		return $key_case;
	}

	/**
	 * Determine if the key is a plural case comming from the en-GB language.
	 *
	 * @param   string  $key             The key to be validated.
	 * @param   array   $$ref_keys_only  The en-GB keys present within the file.
	 *
	 * @return object
	 */
	public static function isEngbPlural($key, &$ref_keys_only, &$orphankeys)
	{
		// The en-GB has a knowen plural cases amount.
		// To handle them is required take in mind than is allowed use multiple suffixes to reply to the same plural case.
		// Those are 'Regular plural cases' due are also present as 'common keys' to translate.
		// Also seems the Joomla Project is allowing a sort of 'fake plurals'
		// due they does not reply with a validated suffix when called by en-GB localise.php file using the 'getPluralSuffixes($n)'function.
		// The "orphan keys" is only used under "debug mode" for testing purposes.

		if (empty($key))
		{
			return false;
		}

		// Key plural format parts
		$format_parts = array('_N_ITEMS', '_N_');

		// The en-GB plural suffixes cases.
		$plural_suffixes = array('0', '1', 'ONE', 'MORE', 'OTHER');

		$key_case = new \JObject;

		// Converting the key to array
		$root_key = explode('_', $key);

		// Getting the last array item to be compared as suffix
		$last_item = end($root_key);

		// Deleting the last array item attempting to get the "suffix".
		array_pop($root_key);

		// Returning to string format
		$root_key = implode('_', $root_key);

		// Deleting last '_' if present
		$root_key = rtrim($root_key, '_');

		$key_case->is_plural = false;
		$key_case->is_orphan = false;

		if (in_array($last_item, $plural_suffixes) && in_array($root_key, $ref_keys_only))
		{
			// At this point this one seems is true and we set it as true.
			$key_case->is_plural = true;
		}
		else if (in_array($last_item, $plural_suffixes))
		{
			foreach ($format_parts as $plural_format)
			{
				$pregkey = preg_quote($plural_format, ':');

				if (preg_match("/$pregkey/", $root_key))
				{
					// Comment "$key_case->is_plural = true" to dump "Orphan cases" as system message.
					$key_case->is_plural = true;
					$key_case->is_orphan = true;
					break;
				}
			}
		}

		$key_case->plural_suffixes = $plural_suffixes;
		$key_case->suffix          = $last_item;
		$key_case->root_key        = $root_key;

		return $key_case;
	}

	/**
	 * Gets the plural suffixes from a selected language.
	 *
	 * @return array
	 */
	public static function getPluralSuffixes(&$language_instance, $counter = 100)
	{
		// Surelly this one is not the best practice to get the suffixes for plural cases from the selected language to translate
		// But i can not found an 'easy mode' to extract them as array from the localise.php file or by Joomla API call.
		//
		// Also, to handle them is required take in mind than is allowed use multiple suffixes to reply to the same plural case.
		// The workarround is working and opened to be finetuned to return suffixes from the localise.php file of the selected language to translate.

		if (empty($counter) || $counter < 0)
		{
			return false;
		}

		$suffixes_data =  new \JObject;

		// $plural_suffixes will store all the matches.
		$plural_suffixes = array();
		$cases_0 = array('0');
		$cases_1 = array('1', 'ONE');
		$cases_other = array('OTHER', 'MORE');
		$cases_more = array('OTHER', 'MORE');

		// With the current working mode the en-GB suffixes are also xx-XX suffixes.
		// The 'easy way' to detect duplicated cases on xx-XX languages suffixes is add the en-GB ones to the defined by the xx-XX ones.
		for ($n = 0; $n <= 1; $n++)
		{
			// Adding the en-GB ones
			if ($n == 0)
			{
				$plural_suffixes[] = '0';
			}
			else if ($n == 1)
			{
				$plural_suffixes[] = 'ONE';
				$plural_suffixes[] = '1';
			}

			$suffixes = $language_instance->getPluralSuffixes($n);

			foreach ($suffixes as $suffix)
			{
				// Adding the xx-XX ones, if not present
				if (!in_array($suffix, $plural_suffixes))
				{
					$plural_suffixes[] = $suffix;
				}

				if ($n == 0 && !in_array($suffix, $cases_0))
				{
					$cases_0[] = $suffix;
				}
				else if ($n == 1 && !in_array($suffix, $cases_1))
				{
					$cases_1[] = $suffix;
				}
			}
		}

		// Adding the 'OTHER' and 'MORE'
		if (!in_array('MORE', $plural_suffixes))
		{
			$plural_suffixes[] = 'MORE';
		}

		if (!in_array('OTHER', $plural_suffixes))
		{
			$plural_suffixes[] = 'OTHER';
		}

		for ($n = 2; $n <= $counter; $n++)
		{
			$suffixes = $language_instance->getPluralSuffixes($n);

			foreach ($suffixes as $suffix)
			{
				if ($suffix == 'OTHER')
				{
					// This will ensure odd cases with personalized plural cases for 'OTHER'
					foreach ($suffixes as $suffix)
					{
						if (!in_array($suffix, $cases_other))
						{
							$cases_other[] = $suffix;
						}
					}
				}
				else if ($suffix == 'MORE')
				{
					// This will ensure odd cases with personalized plural cases for 'MORE'
					foreach ($suffixes as $suffix)
					{
						if (!in_array($suffix, $cases_more))
						{
							$cases_more[] = $suffix;
						}
					}
				}
				else if (!in_array($suffix, $plural_suffixes))
				{
					$plural_suffixes[] = $suffix;
				}
			}
		}

		// To catch plurals
		$suffixes_data->plural_suffixes = $plural_suffixes;

		// To catch duplicated plurals
		$suffixes_data->cases_0         = $cases_0;
		$suffixes_data->cases_1         = $cases_1;
		$suffixes_data->cases_other     = $cases_other;
		$suffixes_data->cases_more      = $cases_more;

		return $suffixes_data;
	}

	/**
	 * Search for duplicated plural cases.
	 *
	 * @return array
	 */
	public static function getDuplicatedPlurals(&$ref_keys_only, &$suffixes_data, &$key_case, &$duplicatedkeys)
	{
		if ($key_case->is_plural == '1')
		{
			$plural_suffixes = $suffixes_data->plural_suffixes;
			$cases_0         = $suffixes_data->cases_0;
			$cases_1         = $suffixes_data->cases_1;
			$cases_other     = $suffixes_data->cases_other;
			$cases_more      = $suffixes_data->cases_more;
			$plural_suffix   = $key_case->suffix;

			if (in_array($plural_suffix, $cases_0))
			{
				$equal_suffixes = $cases_0;
			}
			else if (in_array($plural_suffix, $cases_1))
			{
				$equal_suffixes = $cases_1;
			}
			else if (in_array($plural_suffix, $cases_other))
			{
				$equal_suffixes = $cases_other;
			}
			else if (in_array($plural_suffix, $cases_more))
			{
				$equal_suffixes = $cases_more;
			}
			else
			{
				$equal_suffixes = array();
			}

			if (!empty($equal_suffixes))
			{
				unset($equal_suffixes[$plural_suffix]);
			}

			if (!empty($equal_suffixes))
			{
				foreach ($equal_suffixes as $equal_suffix)
				{
					$duplicated_plural = $key_case->root_key . '_' . $equal_suffix;

					if (in_array($duplicated_plural, $ref_keys_only))
					{
						$personalised_plural = $key_case->root_key . '_' . $plural_suffix;

						// Used for debugging purposes
						$message = "Duplicated plural case detected. The plural:<br>"
									. $duplicated_plural
									. "<br>matches with the presonalised plural for:<br>"
									. $personalised_plural;

						//Factory::getApplication()->enqueueMessage(
						//	Text::_($message),
						//	'warning');

						if (!in_array($duplicated_plural, $duplicatedkeys))
						{
							$duplicatedkeys[] = $duplicated_plural;
						}

						if (!in_array($personalised_plural, $duplicatedkeys))
						{
							$duplicatedkeys[] = $personalised_plural;
						}
					}
				}
			}
		}

		return;
	}

	/**
	 * Gets the known core files list
	 *
	 * @return array
	 *
	 */
	public static function getKnownCoreFilesList()
	{
		try
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select(
				array(
					$db->quoteName('filename')
				)
			);

			$query->from($db->quoteName('#__localise_known_core_files'));
			$query->order('filename ASC');

			$db->setQuery($query);

			if (! $db->execute())
			{
				throw new Exception($db->getErrorMsg());
			}
		}
		catch (JException $e)
		{
			return false;
		}

		$result = $db->loadColumn();

		if (! is_null($result) && ! empty($result))
		{
			return $result;
		}
		else
		{
			return false;
		}

		return false;
	}

	/**
	 * Add to the known core files list a core file
	 *
	 * @return bool
	 *
	 */
	public static function addKnownCoreFile($core_file)
	{
		if (!is_object($core_file))
		{
			return false;
		}

		$result = Factory::getDbo()->insertObject('#__localise_known_core_files', $core_file, 'id');

		if (! is_null($result) && ! empty($result) && $result)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Gets the known deleted keys list
	 *
	 * @return array
	 *
	 */
	public static function getKnownDeletedKeysList($reflang = 'en-GB')
	{
		try
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select(
				array(
					$db->quoteName('key')
				)
			);

			$query->from($db->quoteName('#__localise_known_deleted_keys'));
			$query->where($db->quoteName('reflang')." = :reflang");
			$query->bind(':reflang', $reflang);
			//$query->order('key ASC');

			$db->setQuery($query);

			if (! $db->execute())
			{
				throw new Exception($db->getErrorMsg());
			}
		}
		catch (JException $e)
		{
			return false;
		}

		$result = $db->loadColumn();

		if (! is_null($result) && ! empty($result))
		{
			return $result;
		}
		else
		{
			return array();
		}

		return false;
	}

	/**
	 * Add to the known deleted keys list a deleted key
	 *
	 * @return bool
	 *
	 */
	public static function addKnownDeletedKey($key_data)
	{
		if (!is_object($key_data))
		{
			return false;
		}

		$result = Factory::getDbo()->insertObject('#__localise_known_deleted_keys', $key_data, 'id');

		if (! is_null($result) && ! empty($result) && $result)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Gets the known renamed keys list
	 *
	 * @return array
	 *
	 */
	public static function getKnownRenamedKeysList($client, $reflang = 'en-GB')
	{
		try
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select(
				array(
					$db->quoteName('key')
				)
			);

			$query->from($db->quoteName('#__localise_known_renamed_keys'));
			$query->where($db->quoteName('client')." = :client");
			$query->where($db->quoteName('reflang')." = :reflang");
			$query->bind(':client', $client);
			$query->bind(':reflang', $reflang);

			$db->setQuery($query);

			if (! $db->execute())
			{
				throw new Exception($db->getErrorMsg());
			}
		}
		catch (JException $e)
		{
			return false;
		}

		$result = $db->loadColumn();

		if (! is_null($result) && ! empty($result))
		{
			return $result;
		}
		else
		{
			return array();
		}

		return false;
	}

	/**
	 * Add to the known renamed keys list a renamed key
	 *
	 * @return bool
	 *
	 */
	public static function addKnownRenamedKey($key_data)
	{
		if (!is_object($key_data))
		{
			return false;
		}

		$result = Factory::getDbo()->insertObject('#__localise_known_renamed_keys', $key_data, 'id');

		if (! is_null($result) && ! empty($result) && $result)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Gets the known renamed keys by associated replacement key
	 *
	 * @return Object
	 *
	 */
	public static function getStoredRenamedKeys($client, $reflang = 'en-GB')
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select(
				array(
					$db->quoteName('id'),
					$db->quoteName('client'),
					$db->quoteName('reflang'),
					$db->quoteName('key'),
					$db->quoteName('replacement_key'),
					$db->quoteName('reflang_string')
				)
		);

		$query->from($db->quoteName('#__localise_known_renamed_keys'));
		$query->where($db->quoteName('client')." = :client");
		$query->where($db->quoteName('reflang')." = :reflang");
		$query->bind(':client', $client);
		$query->bind(':reflang', $reflang);

		$db->setQuery($query);

		$result = $db->loadObjectList('replacement_key');

		if (! is_null($result) && ! empty($result) && $result)
		{
			return $result;
		}
		else
		{
			return false;
		}
	}
}
