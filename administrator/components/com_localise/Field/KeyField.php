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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

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
	 * Method to get the field label.
	 *
	 * @return  string    The field label.
	 */

	/**
	 * Method to get the field label markup.
	 *
	 * @return  string  The field label markup.
	 *
	 * @since  1.6
	 */
	protected function getLabel()
	{
		// Set the class for the label.
		$class         = !empty($this->descText) ? 'key-label hasTooltip fltrt' : 'key-label fltrt';

		$istranslation = (int) $this->element['istranslation'];
		$status        = (string) $this->element['status'];
		$istextchange  = (int) $this->element['istextchange'];

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
			$textchanges_checkbox .= '<div><strong>' . $title . '</strong><input style="max-width:5%; min-width:5%;" id="';
			$textchanges_checkbox .= $textchange_visible_id;
			$textchanges_checkbox .= '" type="checkbox" ';
			$textchanges_checkbox .= ' name="jform[vtext_changes][]" value="';
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
			$textchanges_checkbox .= '" ><br></input>';

			$return  = '';
			$return .= '<div><label id="';
			$return .= $this->id;
			$return .= '-lbl" for="';
			$return .= $this->id;
			$return .= '">';
			$return .= $this->element['label'];
			$return .= $textchanges_checkbox;
			$return .= '</label></div>';

			return $return;
		}
		else if ($status == 'extra' && $istranslation)
		{
			$class                = '';
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
			$notinref_checkbox .= '<div><strong>' . $title . '</strong><input style="max-width:5%; min-width:5%;"';
			$notinref_checkbox .= ' title="' . $tip . '"';
			$notinref_checkbox .= ' id="' . $notinref_checkbox_id . '"';
			$notinref_checkbox .= ' type="checkbox" ';
			$notinref_checkbox .= ' name="jform[notinref][]"';
			$notinref_checkbox .= ' value="' . $this->element['name'] . '"';
			$notinref_checkbox .= ' onclick="';
			$notinref_checkbox .= $notinref_onclick;
			$notinref_checkbox .= '" class="' . $class . '"';
			$notinref_checkbox .= '></input></div>';

			$return  = '';
			$return .= '<div><label id="';
			$return .= $this->id;
			$return .= '-lbl" for="';
			$return .= $this->id;
			$return .= '">';
			$return .= $this->element['label'];
			$return .= $notinref_checkbox;
			$return .= '<br></label></div>';

			return $return;
		}
		else if ($status == 'extra' && !$istranslation)
		{
			// Set the class for the label when it is an extra key in the en-GB language.
			$class = !empty($this->descText) ? 'key-label fltrt hasTooltip' : 'key-label fltrt';

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

			return $label;
		}
		else
		{
			// Set the class for the label for any other case.
			$class = !empty($this->descText) ? 'key-label fltrt hasTooltip' : 'key-label fltrt';

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

			return $label;
		}
	}

	/**
	 * Method to get the field input.
	 *
	 * @return  string    The field input.
	 */
	protected function getInput()
	{
		// Set the class for the input for any other case.
		$class         = '';
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
				$input  = '';
				$input .= '<textarea name="' . $textarea_name;
				$input .= '" id="' . $textarea_id . '" class="width-45 ' . $status . ' ">';
				$input .= htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '</textarea>';

				$notinref_key         = (string) $this->element['label'];
				$notinref_checkbox_id = "notinref_checkbox_id_" . str_replace(array("_", ":"), "", $this->element['name']);

				$notinref_onclick = "javascript:";
				$notinref_onclick = "var checked_values = document.getElementsByName('jform[notinref]');
									var form           = $('#localise-translation-form');

									// Set to the hidden form field 'notinref' the value of the selected checkboxes.
									form.find('input[name=notinref]').val(checked_values);
									";

				$class   = '';
				//$button  = '<br>';
				$button .= '<i class="icon-16-notinreference hasTooltip pointer" title="';
				$button .= Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_EXTRA_KEYS_IN_TRANSLATION_ICON');
				$button .= '" onclick="' . $onclick . '"></i>';

				$button2 = '';

				$input  = '';
				$input .= '<textarea name="' . $textarea_name . '" id="' . $textarea_id . '"';
				$input .= ' class="width-45 ' . $status . '">';
				$input .= htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '</textarea><br>';

				return $button . $button2 . $commented . $input;
			}
			else
			{
				$onclick  = "";
				$onclick .= "javascript:document.getElementById('" . $id . "').value='";
				$onclick .= addslashes(htmlspecialchars($this->element['description'], ENT_COMPAT, 'UTF-8'));
				$onclick .= "';";
				$onclick .= "document.getElementById('" . $id . "').setAttribute('class','width-45 untranslated');";

				$onclick2 = "";

				$button   = '';
				$button  .= '<i class="icon-reset hasTooltip return pointer" title="';
				$button  .= Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_INSERT');
				$button  .= '" onclick="' . $onclick . '"></i>';

				$onkeyup = "javascript:";

				if ($istextchange == 1)
				{
					$onkeyup .= "if (this.getAttribute('value')=='')
							{
								this.setAttribute('class','width-45 untranslated');
							}
							else if (this.getAttribute('value')=='"
							. addslashes(htmlspecialchars($this->element['description'], ENT_COMPAT, 'UTF-8'))
							. "')
							{
								this.setAttribute('class','width-45 untranslated');
							}
							else if (this.getAttribute('value')=='"
							. addslashes(htmlspecialchars($this->element['frozen_task'], ENT_COMPAT, 'UTF-8'))
							. "')
							{
								this.setAttribute('class','width-45 untranslated');
							}
							else
							{
								this.setAttribute('class','width-45 translated');
							}";
				}
				else
				{
					$onkeyup .= "if (this.getAttribute('value')=='')
							{
								this.setAttribute('class','width-45 untranslated');
							}
							else if (this.getAttribute('value')=='"
							. addslashes(htmlspecialchars($this->element['description'], ENT_COMPAT, 'UTF-8'))
							. "')
							{
								this.setAttribute('class','width-45 untranslated');
							}
							else
							{
								this.setAttribute('class','width-45 translated');
							}";
				}

				$onfocus = "javascript:this.select();";

				$input  = '';
				$input .= '<textarea name="' . $textarea_name . '" id="' . $textarea_id . '" onfocus="' . $onfocus;
				$input .= '" class="width-45 ' . $status . '" onkeyup="';
				$input .= $onkeyup . '">' . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '</textarea>';
			}

			return $button . $button2 . $commented . $input;
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
			$onclick .= "document.getElementById('" . $id . "').setAttribute('class','width-45 untranslated');";

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
			$onkeyup .= "if (this.getAttribute('value')=='') {this.setAttribute('class','width-45 untranslated');}
						else {if (this.getAttribute('value')=='"
						. addslashes(htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8'))
						. "') this.setAttribute('class','width-45 "
						. $status
						. "');"
						. "else this.setAttribute('class','width-45 translated');}";

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
				$button .= '" onclick="' . $onclick . '"></i>';

				$input  = '';
				$input .= '<textarea name="' . $this->name . '" id="';
				$input .= $this->id . '"' . $readonly . ' onfocus="this.select()" class="width-45 pointer-not-allowed ';

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

				return $button . $button2 . $commented . $input;
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
				$button .= '" onclick="' . $onclick . '"></i>';

				$input  = '';
				$input .= '<textarea name="' . $this->name . '" id="';
				$input .= $this->id . '"' . $readonly . ' onfocus="this.select()" class="width-45 pointer-not-allowed ';
				$input .= $class;
				$input .= '" onkeyup="' . $onkeyup . '">' . $textvalue;
				$input .= '</textarea>';

				return $button . $button2 . $tip . $commented . $input;
			}

			$input  = '';
			$input .= '<textarea name="' . $this->name . '" id="';
			$input .= $this->id . '"' . $readonly . ' onfocus="this.select()" class="width-45 ';
			$input .= $status;
			$input .= '" onkeyup="' . $onkeyup . '">' . $textvalue;
			$input .= '</textarea>';

			return $button . $button2 . $commented . $input;
		}
	}
}
