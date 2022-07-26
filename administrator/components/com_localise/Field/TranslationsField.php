<?php
/**
 * @package     Com_Localise
 * @subpackage  models
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Joomla\Component\Localise\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Form\Field\GroupedlistField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Component\Localise\Administrator\Helper\LocaliseHelper;

include_once JPATH_ADMINISTRATOR . '/components/com_localise/Helper/defines.php';


/**
 * Form Field Translations class.
 *
 * @package     Extensions.Components
 * @subpackage  Localise
 *
 * @since       1.0
 */
class TranslationsField extends GroupedlistField
{
	/**
	 * The field type.
	 *
	 * @var    string
	 */
	protected $type = 'Translations';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return  array    An array of JHtml options.
	 */
	protected function getGroups()
	{
		HTMLHelper::_('stylesheet', 'com_localise/localise.css', array('version' => 'auto', 'relative' => true));

		$params = ComponentHelper::getParams('com_localise');
		$reftag	= $params->get('reference', '');

		if (empty($reftag))
		{
			$reftag = 'en-GB';
		}

		// Form priority
		$formdata = $this->form->getData();
		$langtag  = $formdata["language"];

		// Ajax priority
		$ajaxlangtag = (string) $this->element['langtag'];

		if (!empty($ajaxlangtag))
		{
			$langtag = $ajaxlangtag;
		}

		if (empty($langtag))
		{
			$langtag = $reftag;
		}

		$istranslation  = $reftag != $langtag;

		$known_corefiles = LocaliseHelper::getKnownCoreFilesList();

		if (!is_array($known_corefiles))
		{
			$known_corefiles = array();
		}

		$coreadminfiles = array();
		$coresitefiles  = array();
		$noncorefiles   = array();
		$allfiles       = array();
		$missingfiles   = array();
		$extrafiles     = array();

		$requiredtags   = array($reftag);

		if ($istranslation)
		{
			$requiredtags[] = $langtag;
		}

		// Remove '.ini' from values
		if (is_array($this->value))
		{
			foreach ($this->value as $key => $val)
			{
				$ext = File::getExt($val);

				if ($ext == "ini")
				{
					$this->value[$key] = substr($val, 0, -4);
				}
			}
		}

		$xml            = simplexml_load_file(JPATH_ROOT . '/media/com_localise/packages/core.xml');
		$coreadminfiles = (array) $xml->administrator->children();
		$coresitefiles  = (array) $xml->site->children();

		$coresitefiles  = $coresitefiles['filename'];
		$coreadminfiles = $coreadminfiles['filename'];

		$coreadminfiles = self::suffix_array_values($coreadminfiles, '.ini');
		$coresitefiles  = self::suffix_array_values($coresitefiles, '.ini');

		$package = (string) $this->element['package'];
		$groups  = array('Site' => array(), 'Administrator' => array(), 'Installation' => array());

		foreach (array('Site', 'Administrator', 'Installation') as $client)
		{
			$allfiles[$client]     = array();
			$noncorefiles[$client] = array();
			$extrafiles[$client]   = array();

			$path = constant('LOCALISEPATH_' . strtoupper($client)) . '/language';

			if (Folder::exists($path))
			{
				$tags = Folder::folders($path, '.', false, false, array('overrides', '.svn', 'CVS', '.DS_Store', '__MACOSX'));

				if ($tags)
				{
					foreach ($tags as $tag)
					{
						if (!in_array($tag, $requiredtags))
						{
							continue;
						}

						$allfiles[$client][$tag] = array();
						$files                   = Folder::files("$path/$tag", ".ini$");

						if ($client == 'Site')
						{
							$noncorefiles[$client] = array_diff($files, $coresitefiles);
							$noncorefiles[$client] = array_diff($files, $known_corefiles);

						}
						elseif ($client == 'Administrator')
						{
							$noncorefiles[$client] = array_diff($files, $coreadminfiles);
							$noncorefiles[$client] = array_diff($files, $known_corefiles);
						}

						foreach ($files as $file)
						{
							if ($file == 'joomla.ini')
							{
								$key      = 'joomla';
								$value    = Text::_('COM_LOCALISE_TEXT_TRANSLATIONS_JOOMLA');
							}
							else
							{
								$key      = substr($file, 0, strlen($file) - 4);
								$value    = $key;
							}

							if (!in_array($key, $allfiles[$client][$tag]))
							{
								$allfiles[$client][$tag][] = $key;
							}

							if (!array_key_exists($key, $groups[$client]))
							{
								$groups[$client][$key] = HTMLHelper::_('select.option', strtolower($client) . '_' . $key, $value, 'value', 'text', false);

								if (in_array($file, $noncorefiles[$client]))
								{
									$groups[$client][$key]->class = 'not-in-core-list';
								}
								else
								{
									$groups[$client][$key]->class = 'core-file';
								}
							}
						}
					}
				}
			}
		}

		if ($istranslation)
		{
			foreach (array('Site', 'Administrator', 'Installation') as $client)
			{
				$missingfiles[$client] = array();
				$extrafiles[$client]   = array();

				if (!empty($allfiles[$client][$reftag]) && !empty($allfiles[$client][$langtag]))
				{
					$missingfiles[$client] = array_diff($allfiles[$client][$reftag], $allfiles[$client][$langtag]);
					$extrafiles[$client]   = array_diff($allfiles[$client][$langtag], $allfiles[$client][$reftag]);

					if (!empty($missingfiles[$client]))
					{
						foreach ($missingfiles[$client] as $id => $file)
						{
							$prevclass = $groups[$client][$file]->class;
							$groups[$client][$file]->class = $prevclass . " missing";
						}
					}

					if (!empty($extrafiles[$client]))
					{
						foreach ($extrafiles[$client] as $id => $file)
						{
							$prevclass = $groups[$client][$file]->class;
							$groups[$client][$file]->class = $prevclass . " extra";
						}
					}
				}
				elseif (!empty($allfiles[$client][$reftag]) && empty($allfiles[$client][$langtag]))
				{
					foreach ($allfiles[$client][$reftag] as $id => $file)
					{
						$prevclass = $groups[$client][$file]->class;
						$groups[$client][$file]->class = $prevclass . " missing";
					}
				}
				elseif (empty($allfiles[$client][$reftag]) && !empty($allfiles[$client][$langtag]))
				{
					foreach ($allfiles[$client][$langtag] as $id => $file)
					{
						$prevclass = $groups[$client][$file]->class;
						$groups[$client][$file]->class = $prevclass . " extra";
					}
				}
			}
		}

		foreach ($groups as $client => $extensions)
		{
			ksort($groups[$client]);
		}

		// Merge any additional options in the XML definition.
		$groups = array_merge(parent::getGroups(), $groups);

		return $groups;
	}

	/**
	 * Method to add a suffix to an array.
	 *
	 * @param   array   $array   An array of core files.
	 * @param   string  $suffix  The suffix to add to each file.
	 *
	 * @return  array   The modified array
	 */
	public static function suffix_array_values($array, $suffix = '')
	{
		if (!is_array($array))
		{
			return false;
		}

		// Add a suffix to the values without changing the keys.
		// For example when $suffix = '.ini', if the stored key in array is "com_localise", the assigned value for that key will be changed to "com_localise.ini".
		// Useful when we get the "$xml = simplexml_load_file(JPATH_ROOT . '/media/com_localise/packages/core.xml');"
		// as the files names in "core.xml" have no '.ini' suffix.
		foreach ($array as $key => $value)
		{
			if (!is_string($value))
			{
				continue;
			}

			$array[$key] = $value . $suffix;
		}

		return $array;
	}
}
