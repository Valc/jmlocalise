<?php
/**
 * @package     Com_Localise
 * @subpackage  model
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Localise\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\Archive\Archive;
use Joomla\CMS\Access\Rules as JAccessRules;
use Joomla\CMS\Client\ClientHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\Component\Localise\Administrator\Helper\LocaliseHelper;
use Joomla\CMS\Object\CMSObject;

/**
 * Package Model class for the Localise component
 *
 * @package     Extensions.Components
 * @subpackage  Localise
 *
 * @since       1.0
 */
class PackageFileModel extends AdminModel
{
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Get the application
		$app = Factory::getApplication('administrator');

		// Load the User state.
		$name = $app->getUserState('com_localise.packagefile.name');
		$this->setState('packagefile.name', $name);

		$id = $app->getUserState('com_localise.edit.packagefile.id');
		$this->setState('packagefile.id', $id);
	}

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable  A database object
	 */
	public function getTable($type = 'LocaliseTable', $prefix = '\\Joomla\\Component\\Localise\\Administrator\\Table\\', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$id   = $this->getState('packagefile.id');
		$name = $this->getState('packagefile.name');
		$form = $this->loadForm('com_localise.packagefile', 'packagefile', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		$form->setFieldAttribute('translations', 'packagefile', $name, 'translations');

		// Check for an error.
		if ($form instanceof Exception)
		{
			$this->setError($form->getMessage());

			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return   mixed  The data for the form.
	 */
	protected function loadFormData()
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// Check the session for previously entered form data.
		$data = $app->getUserState('com_localise.edit.packagefile.data', array());

		// Get the package data.
		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to get the ftp form.
	 *
	 * @return  mixed  A JForm object on success, false on failure or not ftp
	 */
	public function getFormFtp()
	{
		// Get the form.
		$form = $this->loadForm('com_localise.ftp', 'ftp');

		if (empty($form))
		{
			return false;
		}

		// Check for an error.
		if ($form instanceof Exception)
		{
			$this->setError($form->getMessage());

			return false;
		}

		return $form;
	}

	/**
	 * Method to get the package.
	 *
	 * @param   integer  $pk  The ID of the primary key.
	 *
	 * @return CMSObject the package
	 */
	public function getItem($pk = null)
	{
		// Initialise variables.
		$app       = Factory::getApplication();
		$input     = $app->input;
		$jformdata = $input->get('jform', array(), 'array');

		$id = $this->getState('packagefile.id');
		$id = is_array($id) ? (count($id) > 0 ? $id[0] : 0) : $id;
		$package = new CMSObject;
		$package->checked_out = 0;
		$package->standalone  = true;
		$package->manifest    = null;
		$package->title       = null;
		$package->description = null;
		$package->id          = $id;

		if (!empty($id))
		{
			// If the package exists get it
			$table = $this->getTable();

			if (is_array($id))
			{
				$id = $id[0];
			}

			$table->load($id);

			$package->setProperties($table->getProperties());

			// Get the manifest
			$xml = simplexml_load_file($table->path);

			if ($xml)
			{
				$manifest = (string) $xml->manifest;

				// $client   = (string) $xml->manifest->attributes()->client;

				// LocaliseHelper::loadLanguage($manifest, $client);

				// Set up basic information
				$name = basename($table->path);
				$name = substr($name, 0, strlen($name) - 4);

				$package->id          = $id;
				$package->name        = $name;
				$package->manifest    = $manifest;

				// $package->client      = $client;

				// $package->standalone  = substr($manifest, 0, 4) == 'fil_';
				$package->core          = ((string) $xml->attributes()->core) == 'true';
				$package->title         = (string) $xml->title;
				$package->version       = (string) $xml->version;
				$package->packversion   = (string) $xml->packversion;
				$package->description   = (string) $xml->description;
				$package->language      = (string) $xml->language;
				$package->license       = (string) $xml->license;
				$package->copyright     = (string) $xml->copyright;
				$package->author        = (string) $xml->author;
				$package->authoremail   = (string) $xml->authoremail;
				$package->authorurl     = (string) $xml->authorurl;
				$package->packager      = (string) $xml->packager;
				$package->packagerurl   = (string) $xml->packagerurl;
				$package->servername    = (string) $xml->servername;
				$package->serverurl     = (string) $xml->serverurl;
				$package->writable      = LocaliseHelper::isWritable($package->path);

				$user = Factory::getUser($table->checked_out);
				$package->setProperties($table->getProperties());

				if ($package->checked_out == Factory::getUser()->id)
				{
					$package->checked_out = 0;
				}

				$package->editor = Text::sprintf('COM_LOCALISE_TEXT_PACKAGE_EDITOR', $user->name, $user->username);

				// Get the translations
				$package->translations  = array();
				$package->administrator = array();

				if ($xml->administrator && empty($jformdata['translations']))
				{
					foreach ($xml->administrator->children() as $file)
					{
						$data = (string) $file;
						$key  = substr($file, 0, strlen($file) - 4);

						if ($data)
						{
							if (!in_array("administrator_$key", $package->translations))
							{
								$package->translations[] = "administrator_$key";
							}
						}
						else
						{
							if (!in_array("administrator_joomla", $package->translations))
							{
								$package->translations[] = "administrator_joomla";
							}
						}

						if (!in_array($data, $package->administrator))
						{
							$package->administrator[] = $data;
						}
					}
				}
				elseif (!empty($jformdata['translations']))
				{
					foreach ($jformdata['translations'] as $file)
					{
						if (preg_match('/^administrator_(.*)$/', $file, $matches))
						{
							if (!in_array($matches[1], $package->administrator))
							{
								$package->administrator[] = $matches[1] . '.ini';
							}
						}
					}
				}

				$package->site = array();

				if ($xml->site && empty($jformdata['translations']))
				{
					foreach ($xml->site->children() as $file)
					{
						$data = (string) $file;
						$key  = substr($file, 0, strlen($file) - 4);

						if ($data)
						{
							if (!in_array("site_$key", $package->translations))
							{
								$package->translations[] = "site_$key";
							}
						}
						else
						{
							if (!in_array("site_joomla", $package->translations))
							{
								$package->translations[] = "site_joomla";
							}
						}

						if (!in_array($data, $package->site))
						{
							$package->site[] = $data;
						}
					}
				}
				elseif (!empty($jformdata['translations']))
				{
					foreach ($jformdata['translations'] as $file)
					{
						if (preg_match('/^site_(.*)$/', $file, $matches))
						{
							if (!in_array($matches[1], $package->site))
							{
								$package->administrator[] = $matches[1] . '.ini';
							}
						}
					}
				}

				if (!empty($jformdata['translations']))
				{
					$package->translations = $jformdata['translations'];
				}
			}
			else
			{
				$package = null;
				$this->setError(Text::sprintf('COM_LOCALISE_ERROR_PACKAGE_FILEEDIT'), $table->path);
			}
		}

		return $package;
	}

	/**
	 * Method to save data
	 *
	 * @param   array  $data  the data to save
	 *
	 * @return  boolean  success or failure
	 */
	public function save($data)
	{
		// When editing a package, find the original path
		$app = Factory::getApplication('administrator');
		$originalId = $app->getUserState('com_localise.edit.packagefile.id');
		$oldpath = null;

		$originalId = is_array($originalId) && count($originalId) > 0 ?
						$originalId[0] : $originalId;

		if (!empty($originalId))
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->select($db->quoteName('path'))
				->from($db->quoteName('#__localise'))
				->where($db->quoteName('id') . ' = ' . $originalId);
			$db->setQuery($query);

			$oldpath = $db->loadResult('path');
		}

		// Get the package name
		$name = $data['name'];

		// Get the package
		$package  = $this->getItem();
		$path     = JPATH_COMPONENT_ADMINISTRATOR . '/packages/' . $name . '.xml';
		$manifest = $name;

		// $client   = $package->client ? $package->client : 'site';

		if ($package->standalone)
		{
			$title = $name;
			$description = $data['description'];

			$dom = new \DOMDocument('1.0', 'utf-8');

			// Create simple XML element and base package tag
			$packageXml = $dom->createElement('package');

			// Add main package information
			$titleElement = $dom->createElement('title', $title);
			$descriptionElement = $dom->createElement('description', $description);
			$manifestElement = $dom->createElement('manifest', $manifest);
			$versionElement = $dom->createElement('version', $data['version']);
			$packversionElement = $dom->createElement('packversion', $data['packversion']);
			$authorElement = $dom->createElement('author', $data['author']);
			$licenseElement = $dom->createElement('license', $data['license']);
			$authorEmailElement = $dom->createElement('authoremail', $data['authoremail']);
			$authorUrlElement = $dom->createElement('authorurl', $data['authorurl']);
			$languageElement = $dom->createElement('language', $data['language']);
			$copyrightElement = $dom->createElement('copyright', $data['copyright']);
			$packagerElement = $dom->createElement('packager', $data['packager']);
			$packagerUrlElement = $dom->createElement('packagerurl', $data['packagerurl']);
			$servernameElement = $dom->createElement('servername', $data['servername']);
			$serverurlElement = $dom->createElement('serverurl', $data['serverurl']);

			// Set the client attribute on the manifest element

			// $clientAttribute = $dom->createAttribute('client');

			// $clientAttribute->value = $client;

			// $manifestElement->appendChild($clientAttribute);

			// Add all the elements to the parent <package> tag
			$packageXml->appendChild($titleElement);
			$packageXml->appendChild($descriptionElement);
			$packageXml->appendChild($manifestElement);
			$packageXml->appendChild($versionElement);
			$packageXml->appendChild($packversionElement);
			$packageXml->appendChild($authorElement);
			$packageXml->appendChild($copyrightElement);
			$packageXml->appendChild($licenseElement);
			$packageXml->appendChild($authorEmailElement);
			$packageXml->appendChild($authorUrlElement);
			$packageXml->appendChild($languageElement);
			$packageXml->appendChild($copyrightElement);
			$packageXml->appendChild($packagerElement);
			$packageXml->appendChild($packagerUrlElement);
			$packageXml->appendChild($servernameElement);
			$packageXml->appendChild($serverurlElement);

			$administrator = array();
			$site          = array();

			foreach ($data['translations'] as $translation)
			{
				if (preg_match('/^site_(.*)$/', $translation, $matches))
				{
					if (!in_array($matches[1], $site))
					{
						$site[] = $matches[1];
					}
				}

				if (preg_match('/^administrator_(.*)$/', $translation, $matches))
				{
					if (!in_array($matches[1], $administrator))
					{
						$administrator[] = $matches[1];
					}
				}
			}

			// Add the site language files
			if (count($site))
			{
				$siteXml = $dom->createElement('site');

				foreach ($site as $translation)
				{
					$fileElement = $dom->createElement('filename', $translation . '.ini');
					$siteXml->appendChild($fileElement);
				}

				$packageXml->appendChild($siteXml);
			}

			// Add the administrator language files
			if (count($administrator))
			{
				$adminXml = $dom->createElement('administrator');

				foreach ($administrator as $translation)
				{
					$fileElement = $dom->createElement('filename', $translation . '.ini');
					$adminXml->appendChild($fileElement);
				}

				$packageXml->appendChild($adminXml);
			}

			$dom->appendChild($packageXml);

			// Set FTP credentials, if given.
			ClientHelper::setCredentialsFromRequest('ftp');
			$ftp = ClientHelper::getCredentials('ftp');

			// Try to make the file writeable.
			if (File::exists($path) && !$ftp['enabled'] && PATH::isOwner($path) && !\JPATH::setPermissions($path, '0644'))
			{
				$this->setError(Text::sprintf('COM_LOCALISE_ERROR_PACKAGE_WRITABLE', $path));

				return false;
			}

			// Make the XML look pretty
			$dom->formatOutput = true;
			$formattedXML = $dom->saveXML();

			$return = File::write($path, $formattedXML);

			// Try to make the file unwriteable.
			if (!$ftp['enabled'] && PATH::isOwner($path) && !PATH::setPermissions($path, '0444'))
			{
				$this->setError(Text::sprintf('COM_LOCALISE_ERROR_PACKAGE_UNWRITABLE', $path));

				return false;
			}
			elseif (!$return)
			{
				$this->setError(Text::sprintf('COM_LOCALISE_ERROR_PACKAGE_FILESAVE', $path));

				return false;
			}
		}

		/** @TODO: Check ftp code
		// Save the title and the description in the language file
		$translation_path  = LocaliseHelper::findTranslationPath($client, Factory::getLanguage()->getTag(), $manifest);
		$translation_id    = LocaliseHelper::getFileId($translation_path);
		$translation_model = \JModelLegacy::getInstance('Translation', 'LocaliseModel', array('ignore_request' => true));

		if ($translation_model->checkout($translation_id))
		{
			$translation_model->setState('translation.path', $translation_path);
			$translation_model->setState('translation.client', $client);
			$translation = $translation_model->getItem();
			$sections    = LocaliseHelper::parseSections($translation_path);
		}
		else
		{
		}

		$text = '';
		//$text .= strtoupper($title) . '="' . str_replace('"', '"_QQ_"', $data['title']) . "\"\n";
		//$text .= strtoupper($description) . '="' . str_replace('"', '"_QQ_"', $data['description']) . "\"\n";
		//$tag  = Factory::getLanguage()->getTag();
		//$languagePath = JPATH_SITE . "/language/$tag/$tag.$manifest.ini";

		// Try to make the file writeable.
		if (!$ftp['enabled'] && PATH::isOwner($languagePath) && !PATH::setPermissions($languagePath, '0644'))
		{
			$this->setError(Text::sprintf('COM_LOCALISE_ERROR_PACKAGE_WRITABLE', $languagePath));

			return false;
		}

		//$return = File::write($languagePath, $text);

		// Try to make the file unwriteable.
		if (!$ftp['enabled'] && PATH::isOwner($languagePath) && JPATH::setPermissions($languagePath, '0444'))
		{
			$this->setError(Text::sprintf('COM_LOCALISE_ERROR_PACKAGE_UNWRITABLE', $languagePath));

			return false;
		}
		elseif (!$return)
		{
			$this->setError(Text::sprintf('COM_LOCALISE_ERROR_PACKAGE_FILESAVE', $languagePath));

			return false;
		}

		*/
		if ($path == $oldpath || (!empty($path) && $oldpath == NULL))
		{
			$id = LocaliseHelper::getFileId($path);
			$this->setState('packagefile.id', $id);

			// Bind the rules.
			$table = $this->getTable();
			$table->load($id);
		}
		else
		{
			$table = $this->getTable();

			if (!$table->delete((int) $originalId))
			{
				$this->setError($table->getError());

				return false;
			}

			$table->store();

			$id = LocaliseHelper::getFileId($path);
			$this->setState('packagefile.id', $id);
			$app->setUserState('com_localise.edit.packagefile.id', $id);
		}

		if (isset($data['rules']))
		{
			$rules = new JAccessRules($data['rules']);
			$table->setRules($rules);
		}

		// Check the data.
		if (!$table->check())
		{
			$this->setError($table->getError());

			return false;
		}

		// Store the data.
		if (!$table->store())
		{
			$this->setError($table->getError());

			return false;
		}

		// Delete the older file and redirect
		if ($path !== $oldpath && file_exists($oldpath))
		{
			if (!File::delete($oldpath))
			{
				$app->enqueueMessage(Text::_('COM_LOCALISE_ERROR_OLDFILE_REMOVE'), 'notice');
			}

			$task = Factory::getApplication()->input->get('task');

			if ($task == 'save')
			{
				$app->redirect(Route::_('index.php?option=com_localise&view=packages', false));
			}
			else
			{
				// Redirect to the new $id as name has changed
				$app->redirect(Route::_('index.php?option=com_localise&view=packagefile&layout=edit&id=' . $this->getState('packagefile.id'), false));
			}
		}

		$this->cleanCache();

		return true;
	}

	/**
	 * Method to generate and download a package
	 *
	 * @param   array  $data  the data to generate the package
	 *
	 * @return  boolean  success or failure
	 */
	public function download($data)
	{
		// The data could potentially be loaded from the file with $this->getItem() instead of using directly the data from the post
		$app = Factory::getApplication();

		// Prevent generating and downloading Master package
		if (strpos($data['name'], 'master_') !== false)
		{
			$app->enqueueMessage(Text::sprintf('COM_LOCALISE_ERROR_MASTER_PACKAGE_DOWNLOAD_FORBIDDEN', $data['name']), 'warning');
			$app->redirect(Route::_('index.php?option=com_localise&view=packagefile&layout=edit&id=' . $this->getState('packagefile.id'), false));

			return false;
		}

		$administrator = array();
		$site          = array();
		$msg           = null;

		// Delete old files
		$delete = Folder::files(JPATH_ROOT . '/tmp/', 'com_localise_', false, true);

		if (!empty($delete))
		{
			if (!File::delete($delete))
			{
				// File::delete throws an error
				$this->setError(Text::_('COM_LOCALISE_ERROR_EXPORT_ZIPDELETE'));

				return false;
			}
		}

		foreach ($data['translations'] as $translation)
		{
			if (preg_match('/^site_(.*)$/', $translation, $matches))
			{
				$site[] = $matches[1];
			}

			if (preg_match('/^administrator_(.*)$/', $translation, $matches))
			{
				$administrator[] = $matches[1];
			}
		}

		$parts = explode('.', $data['version']);
		$small_version = implode('.', array($parts[0],$parts[1]));

		// Prepare text to save for the xml package description
		$text = '';
		$text .= '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$text .= '<extension type="file" version="' . $small_version . '" method="upgrade">' . "\n";
		$text .= "\t" . '<name>' . $data['name'] . $data['language'] . '</name>' . "\n";
		$text .= "\t" . '<version>' . $data['version'] . (!empty($data['packversion']) ? '.' . $data['packversion'] : '') . '</version>' . "\n";
		$text .= "\t" . '<creationDate>' . date('d/m/Y') . '</creationDate>' . "\n";
		$text .= "\t" . '<author>' . $data['author'] . '</author>' . "\n";
		$text .= "\t" . '<authorEmail>' . $data['authoremail'] . '</authorEmail>' . "\n";
		$text .= "\t" . '<authorUrl>' . $data['authorurl'] . '</authorUrl>' . "\n";
		$text .= "\t" . '<copyright>' . $data['copyright'] . '</copyright>' . "\n";
		$text .= "\t" . '<license>' . $data['license'] . '</license>' . "\n";
		$text .= "\t" . '<packager>' . $data['packager'] . '</packager>' . "\n";
		$text .= "\t" . '<packagerurl>' . $data['packagerurl'] . '</packagerurl>' . "\n";
		$text .= "\t" . '<description><![CDATA[' . $data['description'] . ']]></description>' . "\n";
		$text .= "\t" . '<fileset>' . "\n";

		if (count($site))
		{
			$text .= "\t\t" . '<files ';
			$text .= 'folder="site/' . $data['language'] . '"';
			$text .= ' target="language/' . $data['language'] . '">' . "\n";
			$site_package_files = array();

			// $site_package_zip_path = JPATH_ROOT . '/tmp/' . uniqid('com_localise_') . '.zip';

			foreach ($site as $translation)
			{
				$path = LocaliseHelper::findTranslationPath($client = 'site', $tag = $data['language'], $filename = $translation);

				if (File::exists($path))
				{
					$file_data = file_get_contents($path);
				}

				if (File::exists($path) && !empty($file_data))
				{
					$text .= "\t\t\t" . '<filename>' . $translation . '.ini</filename>' . "\n";
					$site_package_files[] = array('name' => $translation . '.ini','data' => $file_data);
				}
				else
				{
					$msg .= Text::sprintf('COM_LOCALISE_FILE_NOT_TRANSLATED', $translation . '.ini', Text::_('JSITE'));
				}
			}

			/**
			$site_txt .= "\t\t".'<filename file="meta">install.xml</filename>' . "\n";
			$site_txt .= "\t\t".'<filename file="meta">' . $data['language'] . '.xml</filename>' . "\n";
			$site_txt .= "\t".'</files>' . "\n";
			$site_txt .= "\t".'<params />' . "\n";
			$site_txt .= "\t".'</extension>' . "\n";
			$site_package_files[] = array('name'=>'install.xml','data'=>$site_txt);
			$language_data = file_get_contents(JPATH_ROOT . '/language/' . $data['language'] . '/' . $data['language'] . '.xml');
			$site_package_files[] = array('name' => $data['language'] . '.xml','data'=>$language_data);
			$language_data = file_get_contents(JPATH_ROOT . '/language/' . $data['language'] . '/' . $data['language'] . '.localise.php');
			$site_package_files[] = array('name' => $data['language'] . '.localise.php','data' => $language_data);

			$site_zip_path = JPATH_ROOT . '/tmp/' . uniqid('com_localise_') . '.zip';
			if (!$packager = Archive::getAdapter('zip'))
			{
				$this->setError(Text::_('COM_LOCALISE_ERROR_EXPORT_ADAPTER'));

				return false;
			}
			else
			{
				if (!$packager->create($site_zip_path, $site_package_files))
				{
					$this->setError(Text::_('COM_LOCALISE_ERROR_EXPORT_ZIPCREATE'));

					return false;
				}
			}
			*/

			$text .= "\t\t" . '</files>' . "\n";

			if ($msg)
			{
				$msg .= '<p>...</p>';
			}

			foreach ($site_package_files as $file)
			{
				$main_package_files[] = array('name' => 'site/' . $data['language'] . '/' . $file['name'], 'data' => $file['data']);
			}
		}

		if (count($administrator))
		{
			$text .= "\t\t" . '<files ';
			$text .= 'folder="admin/' . $data['language'] . '"';
			$text .= ' target="administrator/language/' . $data['language'] . '">' . "\n";

			$admin_package_files = array();

			foreach ($administrator as $translation)
			{
				$path = LocaliseHelper::findTranslationPath($client = 'administrator', $tag = $data['language'], $filename = $translation);

				if (\JFile::exists($path))
				{
					$file_data = file_get_contents($path);
				}

				if (\JFile::exists($path) && !empty($file_data))
				{
					$text .= "\t\t\t" . '<filename>' . $translation . '.ini</filename>' . "\n";
					$admin_package_files[] = array('name' => $translation . '.ini','data' => $file_data);
				}
				else
				{
					$msg .= Text::sprintf('COM_LOCALISE_FILE_NOT_TRANSLATED', $translation . '.ini', Text::_('JADMINISTRATOR'));
				}
			}

			/**
			$admin_txt .= "\t\t".'<filename file="meta">install.xml</filename>' . "\n";
			$admin_txt .= "\t\t".'<filename file="meta">' . $data['language'].'.xml</filename>' . "\n";
			$admin_txt .= "\t".'</files>' . "\n";
			$admin_txt .= "\t".'<params />' . "\n";
			$admin_txt .= "\t".'</extension>' . "\n";
			$admin_package_files[] = array('name'=>'install.xml','data'=>$admin_txt);
			$language_data = file_get_contents(JPATH_ROOT . '/administrator/language/' . $data['language'] . '/' . $data['language'] . '.xml');
			$admin_package_files[] = array('name'=>$data['language'] . '.xml','data' => $language_data);
			$language_data = file_get_contents(JPATH_ROOT . '/administrator/language/' . $data['language'] . '/' . $data['language'] . '.localise.php');
			$admin_package_files[] = array('name'=>$data['language'] . '.localise.php','data' => $language_data);


			$admin_zip_path = JPATH_ROOT . '/tmp/' . uniqid('com_localise_') . '.zip';
			if (!$packager = Archive::getAdapter('zip'))
			{
				$this->setError(Text::_('COM_LOCALISE_ERROR_EXPORT_ADAPTER'));

				return false;
			}
			else
			{
				if (!$packager->create($admin_zip_path, $admin_package_files))
				{
					$this->setError(Text::_('COM_LOCALISE_ERROR_EXPORT_ZIPCREATE'));

					return false;
				}
			}
			*/
			$text .= "\t\t" . '</files>' . "\n";

			foreach ($admin_package_files as $file)
			{
				$main_package_files[] = array('name' => 'admin/' . $data['language'] . '/' . $file['name'], 'data' => $file['data']);
			}
		}

		if ($msg)
		{
			$msg .= '<p>...</p>';
			$msg .= Text::_('COM_LOCALISE_UNTRANSLATED');
			$app->enqueueMessage($msg, 'error');
			$app->redirect(Route::_('index.php?option=com_localise&view=packagefile&layout=edit&id=' . $this->getState('packagefile.id'), false));

			return false;
		}

		$text .= "\t" . '</fileset>' . "\n";

		if (!empty($data['serverurl']))
		{
			$text .= "\t" . '<updateservers>' . "\n";
			$text .= "\t\t" . '<server type="collection" priority="1" name="' . $data['servername'] . '">' . $data['serverurl'] . '</server>' . "\n";
			$text .= "\t" . '</updateservers>' . "\n";
		}

		$text .= '</extension>' . "\n";

		$main_package_files[] = array('name' => $data['name'] . $data['language'] . '.xml', 'data' => $text);

		$ziproot = JPATH_ROOT . '/tmp/' . uniqid('com_localise_main_') . '.zip';

		// Run the packager

		$archive = new Archive;

		if (!$packager = $archive->getAdapter('zip'))
		{
			$this->setError(Text::_('COM_LOCALISE_ERROR_EXPORT_ADAPTER'));

			return false;
		}
		else
		{
			if (!$packager->create($ziproot, $main_package_files))
			{
				$this->setError(Text::_('COM_LOCALISE_ERROR_EXPORT_ZIPCREATE'));

				return false;
			}
		}

		ob_clean();
		$zipdata = file_get_contents($ziproot);
		header("Expires: 0");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="'
				. $data['name'] . '_' . $data['language'] . '_' . $data['version'] . 'v' . $data['packversion'] . '.zip"');
		header('Content-Length: ' . strlen($zipdata));
		header("Cache-Control: maxage=1");
		header("Pragma: public");
		header("Content-Transfer-Encoding: binary");
		echo $zipdata;
		exit;
	}

	/**
	 * Method to get the HTML output required to update the translations field list by the selected language tag.
	 *
	 * @return   object  The data for the jform_translations
	 */
	public function updateTranslationsList($data)
	{
		// Ready to enqueue message if required
		$app = Factory::getApplication();

		// Sample
		//$app->enqueueMessage(Text::_('Fake test returning false from updateTranslationsList function at packagefile model'), 'warning');
		//return false;

		// Getting params
		$params = ComponentHelper::getParams('com_localise');
		$reftag = $params->get('reference', '');

		if (empty($reftag))
		{
			$reftag = 'en-GB';
		}

		// Getting the data from ajax call
		$packagename = htmlspecialchars($data[0]->packagename);
		$langtag     = htmlspecialchars($data[0]->languagetag);

		// Initiating form instance
		$filepath     = JPATH_ADMINISTRATOR . "/components/com_localise/forms/packagefile.xml";
		$form_package = Form::getInstance("packagefile", $filepath, array("control" => "jform"));

		//Form::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_localise/Field');
		//Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_localise/forms');

		// Adding the params below at required fields only when Ajax call.
		$form_package->setFieldAttribute($name = 'translations', 'reftag', $reftag);
		$form_package->setFieldAttribute($name = 'translations', 'langtag', $langtag);

		$html_output = new CMSObject;
		$html_output->translations = $form_package->renderField('translations');

		// The highligthted cases to set as "selected" getting it from the package xml file, if the package filename exists.
		$xml     = false;
		$xmlpath = JPATH_ADMINISTRATOR . '/components/com_localise/packages/' . $packagename . '.xml';

		if (!empty($packagename) && File::exists($xmlpath))
		{
			$xml = simplexml_load_file($xmlpath);
		}

		if ($xml)
		{
			$lines = preg_split("/\\r\\n|\\r|\\n/", $html_output->translations);

			if ($xml->administrator)
			{
				foreach ($xml->administrator->children() as $file)
				{
					$key    = substr($file, 0, strlen($file) - 4);
					$string = preg_quote('value="administrator_' . $key . '"');

					foreach ($lines as $index => $line)
					{
						if (!empty($line))
						{
							if (preg_match("/^(.*)$string(.*)$/", $line, $matches) && !preg_match("/^(.*)disabled(.*)$/", $line, $matches))
							{
								$content       = 'value="administrator_' . $key .'"';
								$newcontent    = 'value="administrator_' . $key .'" selected="selected" ';
								$lines[$index] = str_replace($content, $newcontent, $line);

								break;
							}
						}
					}
				}
			}

			if ($xml->site)
			{
				foreach ($xml->site->children() as $file)
				{
					$key    = substr($file, 0, strlen($file) - 4);
					$string = preg_quote('value="site_' . $key . '"');

					foreach ($lines as $index => $line)
					{
						if (!empty($line))
						{
							if (preg_match("/^(.*)$string(.*)$/", $line, $matches) && !preg_match("/^(.*)disabled(.*)$/", $line, $matches))
							{
								$content       = 'value="site_' . $key .'"';
								$newcontent    = 'value="site_' . $key .'" selected="selected" ';
								$lines[$index] = str_replace($content, $newcontent, $line);

								break;
							}
						}
					}
				}
			}

			$html_output->translations = implode($lines);
		}

		return $html_output;
	}
}
