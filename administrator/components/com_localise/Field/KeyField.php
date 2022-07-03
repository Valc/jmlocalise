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

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\Registry\Registry;

/**
 * Form Field Key class.
 *
 * @package     Extensions.Components
 * @subpackage  Localise
 *
 * @since       1.0
 */
class KeyField extends FormField
{
	/**
	 * The field type.
	 *
	 * @var  string
	 */
	protected $type = 'Key';

	/**
	 * Method to get the field label markup and related data.
	 *
	 * @return  object  The field label markup and related data.
	 *
	 * @since  1.6
	 */
	protected function getLabel()
	{
		$field_data                 = new \JObject;
		$field_data->field_label    = '';
		$field_data->field_checkbox = '';

		$reflang = (string) $this->element['reflang'];
		$is_rtl  = (int) $this->element['reflang_is_rtl'];

		$direction = "ltr";

		if ($is_rtl == '1')
		{
			$direction = "rtl";
		}

		// Set the class for the label.
		$class         = !empty($this->descText) ? "key-label hasTooltip $direction" : "key-label $direction";

		$istranslation = (int) $this->element['istranslation'];
		$status        = (string) $this->element['status'];
		$istextchange  = (int) $this->element['istextchange'];
		$is_comment    = false;

		if ($istextchange == '1')
		{
			$textchange_status     = (int) $this->element['changestatus'];
			$textchange_source     = (string) $this->element['sourcetext'];
			$textchange_target     = (string) $this->element['targettext'];
			$textchange_visible_id = "textchange_visible_id_" . $this->element['name'];
			$textchange_hidded_id  = "textchange_hidded_id_" . $this->element['name'];
			$textchange_source_id  = "textchange_source_id_" . $this->element['name'];
			$textchange_target_id  = "textchange_target_id_" . $this->element['name'];

			if ($textchange_status == '1')
			{
				$textchange_checked = ' checked="checked" ';
			}
			else
			{
				$textchange_checked = '';
			}

			$textchanges_onclick = "document.getElementById(
							'" . $textchange_hidded_id . "'
							)
							.setAttribute(
							'value', document.getElementById('" . $textchange_visible_id . "' ).checked
							);";

			if ($istranslation)
			{
				$title = Text::_('COM_LOCALISE_REVISED');
				$tip   = $title;
			}
			else
			{
				$title = Text::_('COM_LOCALISE_CHECKBOX_TRANSLATION_GRAMMAR_CASE');
				$tip   = '';
			}

			$textchanges_checkbox  = '';
			$textchanges_checkbox .= '<div><strong>' . $title . '</strong><input style="" id="';
			$textchanges_checkbox .= $textchange_visible_id;
			$textchanges_checkbox .= '" type="checkbox" ';
			$textchanges_checkbox .= ' name="jform[vtext_changes][]" class="' . $class . '"value="';
			$textchanges_checkbox .= $this->element['name'];
			$textchanges_checkbox .= '" title="' . $tip . '" onclick="';
			$textchanges_checkbox .= $textchanges_onclick;
			$textchanges_checkbox .= '" ';
			$textchanges_checkbox .= $textchange_checked;
			$textchanges_checkbox .= '></input></div>';
			$textchanges_checkbox .= '<input id="';
			$textchanges_checkbox .= $textchange_hidded_id;
			$textchanges_checkbox .= '" type="hidden" name="jform[text_changes][';
			$textchanges_checkbox .= $this->element['name'];
			$textchanges_checkbox .= ']" value="';
			$textchanges_checkbox .= $textchange_status;
			$textchanges_checkbox .= '" ></input>';
			$textchanges_checkbox .= '<input id="';
			$textchanges_checkbox .= $textchange_source_id;
			$textchanges_checkbox .= '" type="hidden" name="jform[source_text_changes][';
			$textchanges_checkbox .= $this->element['name'];
			$textchanges_checkbox .= ']" value="';
			$textchanges_checkbox .= htmlspecialchars($textchange_source, ENT_COMPAT, 'UTF-8');
			$textchanges_checkbox .= '" ></input>';
			$textchanges_checkbox .= '<input id="';
			$textchanges_checkbox .= $textchange_target_id;
			$textchanges_checkbox .= '" type="hidden" name="jform[target_text_changes][';
			$textchanges_checkbox .= $this->element['name'];
			$textchanges_checkbox .= ']" value="';
			$textchanges_checkbox .= htmlspecialchars($textchange_target, ENT_COMPAT, 'UTF-8');
			$textchanges_checkbox .= '" ></input>';

			$label  = '';
			$label .= '<div><label id="';
			$label .= $this->id;
			$label .= '-lbl" for="';
			$label .= $this->id;
			$label .= '" class="' . $class . '">';
			$label .= $this->element['label'];
			$label .= '</label></div>';

			$field_data->field_label = $label;
			$field_data->field_checkbox = '<div class="float-end">' . $textchanges_checkbox . '</div>';

			return $field_data;
		}
		else if ($status == 'extra' && $istranslation)
		{
			$class                = !empty($this->descText) ? "key-label hasTooltip $direction" : "key-label $direction";
			$tip                  = Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_NOTINREF');
			$title                = Text::_('COM_LOCALISE_DELETE');
			$notinref_key         = (string) $this->element['label'];
			$notinref_checkbox_id = "notinref_checkbox_id_" . str_replace(array("_", ":"), "", $this->element['name']);

			$notinref_onclick     = "javascript:";
			$notinref_onclick    .= "var checked_values = document.getElementsByName('jform[notinref]');
									var form           = $('#localise-translation-form');

									// Set to the hidden form field 'notinref' the value of the selected checkboxes.
									form.find('input[name=notinref]').val(checked_values);
									";

			$notinref_checkbox  = '';
			$notinref_checkbox .= '<div><strong>' . $title . '</strong><input style=""';
			$notinref_checkbox .= ' title="' . $tip . '"';
			$notinref_checkbox .= ' id="' . $notinref_checkbox_id . '"';
			$notinref_checkbox .= ' type="checkbox" ';
			$notinref_checkbox .= ' name="jform[notinref][]"';
			$notinref_checkbox .= ' value="' . $this->element['name'] . '"';
			$notinref_checkbox .= ' onclick="';
			$notinref_checkbox .= $notinref_onclick;
			$notinref_checkbox .= '" class="' . $class . '"';
			$notinref_checkbox .= '></input></div>';

			$label  = '';
			$label .= '<div><label id="';
			$label .= $this->id;
			$label .= '-lbl" for="';
			$label .= $this->id;
			$label .= '" class="' . $class . '">';
			$label .= $this->element['label'];
			$label .= '</label></div>';

			$field_data->field_label    = $label;
			$field_data->field_checkbox = '<div class="float-end">' . $notinref_checkbox . '</div>';

			return $field_data;
		}
		else if ($status == 'extra' && !$istranslation)
		{
			// Set the class for the label when it is an extra key in the en-GB language.
			$class = !empty($this->descText) ? "key-label hasTooltip $direction" : "key-label $direction";

			// If a description is specified, use it to build a tooltip.
			if (!empty($this->descText))
			{
				$label = '<label id="' . $this->id . '-lbl" for="' . $this->id . '" class="' . $class . '" title="'
						. htmlspecialchars(htmlspecialchars('::' . str_replace("\n", "\\n", $this->descText), ENT_QUOTES, 'UTF-8')) . '">';
			}
			else
			{
				$label = '<label id="' . $this->id . '-lbl" for="' . $this->id . '" class="' . $class . '">';
			}

			$label .= $this->element['label'];
			$label .= '</label>';

			$field_data->field_label = $label;

			return $field_data;
		}
		else
		{
			// Set the class for the label for any other case.
			$class = !empty($this->descText) ? "key-label hasTooltip $direction" : "key-label $direction";

			// If a description is specified, use it to build a tooltip.
			if (!empty($this->descText))
			{
				$label = '<label id="' . $this->id . '-lbl" for="' . $this->id . '" class="' . $class . '" title="'
						. htmlspecialchars(htmlspecialchars('::' . str_replace("\n", "\\n", $this->descText), ENT_QUOTES, 'UTF-8')) . '">';
			}
			else
			{
				$label = '<label id="' . $this->id . '-lbl" for="' . $this->id . '" class="' . $class . '">';
			}

			$label .= $this->element['label'];
			$label .= '</label>';

			$field_data->field_label = $label;

			return $field_data;
		}
	}

	/**
	 * Method to get the field input and related data.
	 *
	 * @return  object  The field input markup and related data.
	 */
	protected function getInput()
	{
		$field_data                  = new \JObject;
		$field_data->field_input     = '';
		$field_data->field_button    = '';
		$field_data->field_button2   = '';
		$field_data->field_commented = '';

		$targetlang    = (string) $this->element['targetlang'];
		$is_rtl        = (int) $this->element['targetlang_is_rtl'];

		$direction = "ltr";

		if ($is_rtl == '1')
		{
			$direction = "rtl";
		}

		// Set the class for the input for any other case.
		$class         = $direction;
		$istranslation = (int) $this->element['istranslation'];
		$istextchange  = (int) $this->element['istextchange'];
		$isextraindev  = (int) $this->element['isextraindev'];
		$status        = (string) $this->element['status'];
		$commented     = (string) $this->element['commented'];
		$label_id      = $this->id . '-lbl';
		$label_for     = $this->id;
		$textarea_name = $this->name;
		$textarea_id   = $this->id;
		$id            = $this->id;

		if (!empty($commented))
		{
			$commented = '<div> <span class="badge bg-info">' . $commented . '</span></div>';
		}
		else
		{
			$commented = '';
		}

		if ($istranslation)
		{
			$onclick  = '';
			$button   = '';

			$onclick2 = '';
			$button2  = '';

			$onfocus = "";

			if ($status == 'extra')
			{
				$class .= " width-100 $status";
				$input  = '';

				$notinref_key         = (string) $this->element['label'];
				$notinref_checkbox_id = "notinref_checkbox_id_" . str_replace(array("_", ":"), "", $this->element['name']);

				$notinref_onclick = "javascript:";
				$notinref_onclick = "var checked_values = document.getElementsByName('jform[notinref]');
									var form           = $('#localise-translation-form');

									// Set to the hidden form field 'notinref' the value of the selected checkboxes.
									form.find('input[name=notinref]').val(checked_values);
									";

				$button  = '';
				$button .= '<i class="icon-16-notinreference hasTooltip pointer" title="';
				$button .= Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_EXTRA_KEYS_IN_TRANSLATION_ICON');
				$button .= '" onclick="' . $onclick . '"></i><br>';

				$button2 = '';

				$input  = '';
				$input .= '<textarea name="' . $textarea_name . '" id="' . $textarea_id . '"';
				$input .= ' class="' . $class . '">';
				$input .= htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '</textarea>';

				$field_data->field_input     = $input;
				$field_data->field_button    = $button;
				$field_data->field_button2   = $button2;
				$field_data->field_commented = $commented;
				return $field_data;
			}
			else
			{
				$class .= " width-100 $status";

				$onclick  = "";
				$onclick .= "javascript:document.getElementById('" . $id . "').value='";
				$onclick .= addslashes(htmlspecialchars($this->element['description'], ENT_COMPAT, 'UTF-8'));
				$onclick .= "';";
				$onclick .= "document.getElementById('" . $id . "').setAttribute('class','width-100 untranslated " . $direction . "');";

				$onclick2 = "";

				$button   = '';
				$button  .= '<i class="icon-reset hasTooltip return pointer" title="';
				$button  .= Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_INSERT');
				$button  .= '" onclick="' . $onclick . '"></i><br>';

				$onkeyup = "javascript:";

				if ($istextchange == 1)
				{
					$onkeyup .= "if (this.getAttribute('value')=='')
							{
								this.setAttribute('class','width-100 untranslated " . $direction . "');
							}
							else if (this.getAttribute('value')=='"
							. addslashes(htmlspecialchars($this->element['description'], ENT_COMPAT, 'UTF-8'))
							. "')
							{
								this.setAttribute('class','width-100 untranslated " . $direction . "');
							}
							else if (this.getAttribute('value')=='"
							. addslashes(htmlspecialchars($this->element['frozen_task'], ENT_COMPAT, 'UTF-8'))
							. "')
							{
								this.setAttribute('class','width-100 untranslated " . $direction . "');
							}
							else
							{
								this.setAttribute('class','width-100 translated " . $direction . "');
							}";
				}
				else
				{
					$onkeyup .= "if (this.getAttribute('value')=='')
							{
								this.setAttribute('class','width-100 untranslated " . $direction . "');
							}
							else if (this.getAttribute('value')=='"
							. addslashes(htmlspecialchars($this->element['description'], ENT_COMPAT, 'UTF-8'))
							. "')
							{
								this.setAttribute('class','width-100 untranslated " . $direction . "');
							}
							else
							{
								this.setAttribute('class','width-100 translated " . $direction . "');
							}";
				}

				$onfocus = "javascript:this.select();";

				$input  = '';
				$input .= '<textarea name="' . $textarea_name . '" id="' . $textarea_id . '" onfocus="' . $onfocus;
				$input .= '" class="' . $class . '" onkeyup="';
				$input .= $onkeyup . '">' . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '</textarea>';
			}

			$field_data->field_input     = $input;
			$field_data->field_button    = $button;
			$field_data->field_button2   = $button2;
			$field_data->field_commented = $commented;
			return $field_data;
		}
		else
		{
			// This is not a translation. We are handling the en-GB reference output and is not handled as a translation case.
			//
			// Is allowed edit any key due maybe is required apply directly corrections to en-GB strings to show when other xx-XX language is called.
			//
			// Keys not in reference are "read only" cases at en-GB: that keys for sure are not present at next Joomla release.
			//
			// So, is not allowed delete not in ref keys at en-GB
			// due if applied have the same effect than lost that en-GB string in the actual installed instance of Joomla.
			//
			// Is allowed handle "Grammar cases" at en-GB, with the string as read-only.
			// The checked here is not showed as "changed text" at xx-XX. Not good idea with en-XX languages
			// , only with all others can to be useful if we wanna avoid show en-GB grammar cases as changed text at xx-XX languages.

			// Adjusting the stuff when all them are reference keys.
			$readonly  = '';
			$textvalue = htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8');

			$onclick  = "javascript:";
			$onclick .= "document.getElementById('" . $id . "').value='";
			$onclick .= addslashes(htmlspecialchars($this->element['description'], ENT_COMPAT, 'UTF-8'));
			$onclick .= "';";
			$onclick .= "document.getElementById('" . $id . "').setAttribute('class','width-100 untranslated " . $direction . "');";

			$button   = '';
			$button  .= '<i class="icon-reset hasTooltip return pointer" title="';
			$button  .= Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_INSERT');
			$button  .= '" onclick="' . $onclick . '"></i>';

			// No sense translate the reference keys by the same language.
			$onclick2 = '';
			$button2  = '';

			/*$button2  = '<span style="width:5%;">'
						. HTMLHelper::_('image', 'com_localise/icon-16-bing-gray.png', '', array('class' => 'pointer'), true) . '</span>';*/
			$onkeyup  = "javascript:";
			$onkeyup .= "if (this.getAttribute('value')=='') {this.setAttribute('class','width-100 untranslated " . $direction . "');}
						else {if (this.getAttribute('value')=='"
						. addslashes(htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8'))
						. "') this.setAttribute('class','width-100 "
						. $status . " " . $direction
						. "');"
						. "else this.setAttribute('class','width-100 translated " . $direction . "');}";

			if ($status == 'extra')
			{
				// There is no translation task in develop for the reference files in develop.
				$readonly  = ' readonly="readonly" ';
				$class    .= ' disabled ';
				$textvalue = htmlspecialchars($this->element['description'], ENT_COMPAT, 'UTF-8');

				// Is read only, so no changes.
				$onkeyup = "";
				$onclick = '';
				$button  = '';
				$button .= '<i class="icon-joomla hasTooltip pointer-not-allowed" title="';
				$button .= Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_KEY_TO_DELETE');
				$button .= '" onclick="' . $onclick . '"></i><br>';

				$input  = '';
				$input .= '<textarea name="' . $this->name . '" id="';
				$input .= $this->id . '"' . $readonly . ' onfocus="this.select()" class="width-100 pointer-not-allowed '. $direction . ' ';

				if ($isextraindev)
				{
					$input .= $status;
				}
				else
				{
					$input .= $class;
				}

				$input .= '" onkeyup="' . $onkeyup . '">' . $textvalue;
				$input .= '</textarea>';

				$field_data->field_input     = $input;
				$field_data->field_button    = $button;
				$field_data->field_button2   = $button2;
				$field_data->field_commented = $commented;
				return $field_data;
			}
			elseif ($istextchange)
			{
				// The string is read-only at en-GB file edition to avoid handle bugged counter results.
				$readonly  = ' readonly="readonly" ';
				$class    .= ' disabled ';
				$textvalue = htmlspecialchars($this->element['description'], ENT_COMPAT, 'UTF-8');
				$title     = '';
				$tip       = '<div> <span class="badge bg-warning text-dark grammar">' . Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_GRAMMAR_CASE') . '</span></div>';

				// Is read only, so no changes.
				$onkeyup = "";
				$onclick = '';
				$button  = '';
				$button .= '<i class="icon-joomla hasTooltip pointer-not-allowed" title="';
				$button .= $title;
				$button .= '" onclick="' . $onclick . '"></i><br>';

				$input  = '';
				$input .= '<textarea name="' . $this->name . '" id="';
				$input .= $this->id . '"' . $readonly . ' onfocus="this.select()" class="width-100 pointer-not-allowed '. $direction;
				$input .= $class;
				$input .= '" onkeyup="' . $onkeyup . '">' . $textvalue;
				$input .= '</textarea>';


				$field_data->field_input     = $input;
				$field_data->field_button    = $button;
				$field_data->field_button2   = $button2;
				$field_data->field_commented = $commented;
				return $field_data;
			}

			$input  = '';
			$input .= '<textarea name="' . $this->name . '" id="';
			$input .= $this->id . '"' . $readonly . ' onfocus="this.select()" class="width-100 '. $direction . ' ';
			$input .= $status;
			$input .= '" onkeyup="' . $onkeyup . '">' . $textvalue;
			$input .= '</textarea>';

			$field_data->field_input     = $input;
			$field_data->field_button    = $button;
			$field_data->field_button2   = $button2;
			$field_data->field_commented = $commented;
			return $field_data;
		}
	}
}
