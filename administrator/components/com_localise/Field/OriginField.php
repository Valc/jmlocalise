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

use Joomla\Component\Localise\Administrator\Helper\LocaliseHelper;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;

/**
 * Form Field Place class.
 *
 * @package     Extensions.Components
 * @subpackage  Localise
 *
 * @since       1.0
 */
class OriginField extends FormField
{
	/**
	 * The field type.
	 *
	 * @var    string
	 */
	protected $type = 'Origin';

	/**
	 * Method to get the field input.
	 *
	 * @return  string    The field input.
	 */
	protected function getInput()
	{
		$attributes = '';

		if ($v = (string) $this->element['onchange'])
		{
			$attributes .= ' onchange="' . $v . '"';
		}

		if ($this->value == '_thirdparty')
		{
			$attributes .= ' class="' . (string) $this->element['class'] . '"';
		}
		elseif ($this->value == '_override')
		{
			$attributes .= ' class="' . (string) $this->element['class'] . '"';
		}
		elseif ($this->value == 'core')
		{
			$attributes .= ' class="' . (string) $this->element['class'] . '"';
		}
		else
		{
			$attributes .= ' class="' . (string) $this->element['class'] . '"';
		}

		$options = array();

		foreach ($this->element->children() as $option)
		{
			$options[] = HTMLHelper::_('select.option', $option->attributes('value'), Text::_(trim($option)), array('option.attr' => 'attributes', 'attr' => ''));
		}

		//$packages         = LocaliseHelper::getPackages();
		//$packages_options = array();

		/** We took off the packages icons (due to bootstrap implementation)
		 * @Todo: this may need review
		foreach ($packages as $package)
		{
			$packages_options[] = HTMLHelper::_(
				'select.option',
				$package->name,
				Text::_($package->title),
				array(
					'option.attr' => 'attributes',
					'attr' => 'class="localise-icon" style="background-image: url(' . JURI::root(true) . $package->icon . ');"'
				)
			);

			if ($this->value == $package->name)
			{
				$attributes .= ' style="background-image: url(' . JURI::root(true) . $package->icon . ');"';
			}
		}
		*/

		//$packages_options = ArrayHelper::sortObjects($packages_options, 'text');
		$thirdparty       = HTMLHelper::_('select.option', '_thirdparty', Text::sprintf('COM_LOCALISE_OPTION_TRANSLATIONS_ORIGIN_THIRDPARTY'),
							array('option.attr' => 'attributes', 'attr' => 'class=""')
							);
		$override         = HTMLHelper::_('select.option', '_override', Text::sprintf('COM_LOCALISE_OPTION_TRANSLATIONS_ORIGIN_OVERRIDE'),
							array('option.attr' => 'attributes', 'attr' => 'class=""')
							);
		$core             = HTMLHelper::_('select.option', 'core', Text::sprintf('COM_LOCALISE_OPTION_TRANSLATIONS_ORIGIN_CORE'),
							array('option.attr' => 'attributes', 'attr' => 'class=""')
							);
		$return           = HTMLHelper::_('select.genericlist', array_merge($options, array($thirdparty), array($override), array($core)),
							$this->name, array('id' => $this->id, 'list.select' => $this->value, 'option.attr' => 'attributes',
							'list.attr' => $attributes, 'group.items' => null)
							);

		return $return;
	}
}
