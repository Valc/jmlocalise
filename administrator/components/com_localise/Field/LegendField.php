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

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

/**
 * Form Field Legend class.
 *
 * @package     Extensions.Components
 * @subpackage  Localise
 *
 * @since       1.0
 */
class LegendField extends FormField
{
	/**
	 * The field type.
	 *
	 * @var    string
	 */
	protected $type = 'Legend';

	/**
	 * Method to get the field input.
	 *
	 * @return  string    The field input.
	 */
	protected function getInput()
	{
		$return = '<table class="pull-left">';
		$return .= '<tr><td><input class="translated" size="30" type="text" value="' . Text::_('COM_LOCALISE_TEXT_TRANSLATION_TRANSLATED')
					. '" readonly="readonly"/></td></tr>';
		$return .= '<tr><td><input class="unchanged" size="30"  type="text" value="' . Text::_('COM_LOCALISE_TEXT_TRANSLATION_UNCHANGED')
					. '" readonly="readonly"/></td></tr>';
		$return .= '<tr><td><input class="untranslated" size="30"  type="text" value="' . Text::_('COM_LOCALISE_TEXT_TRANSLATION_UNTRANSLATED')
					. '" readonly="readonly"/></td></tr>';
		$return .= '<tr><td><p>' . Text::_('COM_LOCALISE_TEXT_TRANSLATION_NOTINREF_CASES')
					. '</p></td></tr>';
		$return .= '<tr><td><input class="plural" size="30" type="text" value="' . Text::_('COM_LOCALISE_TEXT_TRANSLATION_PERSONALISED_PLURAL')
					. '" readonly="readonly"/></td></tr>';
		$return .= '<tr><td><input class="renamed" size="30" type="text" value="' . Text::_('COM_LOCALISE_TEXT_TRANSLATION_RENAMED')
					. '" readonly="readonly"/></td></tr>';
		$return .= '<tr><td><input class="deleted" size="30" type="text" value="' . Text::_('COM_LOCALISE_TEXT_TRANSLATION_DELETED')
					. '" readonly="readonly"/></td></tr>';
		$return .= '<tr><td><input class="extra" size="30" type="text" value="' . Text::_('COM_LOCALISE_TEXT_TRANSLATION_EXTRA')
					. '" readonly="readonly"/></td></tr>';
		$return .= '</table>';

		return $return;
	}
}
