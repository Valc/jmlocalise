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

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('list');

/**
 * Form Field Place class.
 *
 * @package     Extensions.Components
 * @subpackage  Localise
 *
 * @since       1.0
 */
class DevelopField extends ListField
{
	/**
	 * The field type.
	 *
	 * @var    string
	 */
	protected $type = 'Develop';

	/**
	 * Method to get the field input.
	 *
	 * @return  string    The field input.
	 */
	protected function getOptions()
	{
		$attributes = '';

		if ($v = (string) $this->element['onchange'])
		{
			$attributes .= ' onchange="' . $v . '"';
		}

		$attributes .= ' class="' . (string) $this->element['class'] . ' iconlist-16-' . $this->value . '"';
		$options = array();

		foreach ($this->element->children() as $option)
		{
			$options[] = HTMLHelper::_('select.option', $option->attributes('value'), Text::_(trim($option)), array('option.attr' => 'attributes', 'attr' => ''));
		}

		$options[] = HTMLHelper::_('select.option', 'complete', Text::sprintf('COM_LOCALISE_OPTION_TRANSLATIONS_DEVELOP_COMPLETE'),
					array('option.attr' => 'attributes', 'attr' => 'class="iconlist-16-equal"')
					);
		$options[] = HTMLHelper::_('select.option', 'incomplete', Text::sprintf('COM_LOCALISE_OPTION_TRANSLATIONS_DEVELOP_INCOMPLETE'),
					array('option.attr' => 'attributes', 'attr' => 'class="iconlist-16-changed"')
					);
		$options[] = HTMLHelper::_('select.option', 'plural', Text::sprintf('COM_LOCALISE_OPTION_TRANSLATIONS_DEVELOP_PLURAL'),
					array('option.attr' => 'attributes', 'attr' => 'class="iconlist-16-plural"')
					);
		$options[] = HTMLHelper::_('select.option', 'issued', Text::sprintf('COM_LOCALISE_OPTION_TRANSLATIONS_DEVELOP_ISSUED'),
					array('option.attr' => 'attributes', 'attr' => 'class="iconlist-16-issued"')
					);

		return $options;
	}
}
