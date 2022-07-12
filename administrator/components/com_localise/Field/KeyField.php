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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\Registry\Registry;

use Joomla\Component\Localise\Administrator\Helper\LocaliseHelper;
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
		$field_data                         = new \JObject;
		$field_data->field_key              = (string) $this->element['name'];
		$field_data->field_details          = '';
		$field_data->field_label            = '';
		$field_data->field_desc             = '';
		$field_data->field_checkbox         = '';
		$field_data->textchanges_checkbox   = '';
		$field_data->falsepositive_checkbox = '';
		$field_data->is_textchange          = (int) $this->element['istextchange'];
		$field_data->is_issued              = (int) $this->element['isissued'];
		$field_data->targetlang             = (string) $this->element['targetlang'];
		$field_data->engb_string            = (string) $this->element['engb_string'];
		$field_data->ttms_string            = (string) $this->element['ttms_string'];
		$field_data->issue_details          = (string) $this->element['issue_details'];

		$client        = (string) $this->element['client'];
		$reflang       = (string) $this->element['reflang'];
		$targetlang    = (string) $this->element['targetlang'];
		$filename      = (string) $this->element['filename'];
		$key           = (string) $this->element['name'];
		$is_rtl        = (int)    $this->element['reflang_is_rtl'];
		$is_rtl_target = (int)    $this->element['targetlang_is_rtl'];

		$direction  = "ltr";
		$direction2 = "ltr";

		if ($is_rtl == '1')
		{
			$direction = "rtl";
		}

		if ($is_rtl_target == '1')
		{
			$direction2 = "rtl";
		}

		// Set the class for the label.
		$class         = !empty($this->descText) ? "key-label hasTooltip $direction" : "key-label $direction";

		$istranslation   = (int) $this->element['istranslation'];
		$status          = (string) $this->element['status'];
		$istextchange    = (int) $field_data->is_textchange;
		$isissued        = (int) $field_data->is_issued;
		$is_comment      = false;
		$is_new          = (string) $this->element['isextraindev'];
		$is_plural       = (string) $this->element['isplural'];
		$is_root         = (string) $this->element['isroot'];
		$is_personalised = (string) $this->element['ispersonalised'];
		$is_duplicated   = (string) $this->element['isduplicated'];

		if ($is_new == 1)
		{
			$field_data->field_details .= '<span class="badge bg-success">' . Text::_('COM_LOCALISE_TEXT_TRANSLATION_NEW_AT_TAB') . '</span>';
		}

		// Setting badge priority for regular plural cases
		if ($is_root == 1)
		{
			$field_data->field_details .= '<span class="badge bg-primary">' . Text::_('COM_LOCALISE_TEXT_TRANSLATION_ROOT_AT_TAB') . '</span>';
		}
		else if ($is_plural == 1)
		{
			$field_data->field_details .= '<span class="badge bg-info">' . Text::_('COM_LOCALISE_TEXT_TRANSLATION_PLURAL_AT_TAB') . '</span>';

			if ($is_duplicated == 1)
			{
				$field_data->field_details .= '<span class="badge bg-warning">' . Text::_('COM_LOCALISE_TEXT_TRANSLATION_DUPLICATED_AT_TAB') . '</span>';
			}
		}

		if ($isissued == '1' && $istranslation == '1')
		{
			$db_data                    = new \JObject;
			$db_data->client            = $client;
			$db_data->reflang           = $reflang;
			$db_data->targetlang        = $targetlang;
			$db_data->filename          = $filename;
			$db_data->key               = $key;
			$db_data->reflang_string    = base64_encode($this->element['description']);
			$db_data->targetlang_string = base64_encode($this->value);

			$stored_case = self::searchFalsePositive($db_data);		
	
			if (isset($stored_case->is_false_positive))
			{
				if ($stored_case->is_false_positive == '1')
				{
					$falsepositive_checked = ' checked="checked" ';
				}
				else
				{
					$falsepositive_checked = '';
				}
			}
			else
			{
				$falsepositive_checked = '';
			}

			$field_data->field_details .= '<span class="badge bg-warning">' . Text::_('COM_LOCALISE_TEXT_TRANSLATION_ISSUED_AT_TAB') . '</span>';
			$field_data->engb_string    = '<div class="engb-string ' . $direction . '">' . $this->element['engb_string'] . '</div>';
			$field_data->ttms_string    = '<div class="ttms-string ' . $direction2 . '">' . $this->element['ttms_string'] . '</div>';
			$field_data->issue_details  = '<div class="details-string ' . $direction . '">' . $this->element['issue_details'] . '</div>';

			$label_desc  = '';
			$label_desc .= '<div><strong>';
			$label_desc .= $this->element['name'];
			$label_desc .= '</strong><br><label class="key-label normal-text ' . $direction . '">';
			$label_desc .= htmlspecialchars($this->element['description'], ENT_COMPAT, 'UTF-8');
			$label_desc .= '</label></div>';

			$falsepositive_key         = (string) $this->element['label'];
			$falsepositive_checkbox_id = "falsepositive_checkbox_id_" . str_replace(array("_", ":"), "", $this->element['name']);

			$falsepositive_onclick     = "javascript:";
			$falsepositive_onclick    .= "var checked_values = document.getElementsByName('jform[falsepositive]');
									var form           = $('#localise-translation-form');

									// Set to the hidden form field 'falsepositive' the value of the selected checkboxes.
									form.find('input[name=falsepositive]').val(checked_values);
									";

			$title = Text::_('COM_LOCALISE_FALSEPOSITIVE');
			$tip   = $title;

			$falsepositive_checkbox  = '';
			$falsepositive_checkbox .= '<div><strong>' . $title . '</strong><input style=""';
			$falsepositive_checkbox .= ' title="' . $tip . '"';
			$falsepositive_checkbox .= ' id="' . $falsepositive_checkbox_id . '"';
			$falsepositive_checkbox .= ' type="checkbox" ';
			$falsepositive_checkbox .= ' name="jform[falsepositive][]"';
			$falsepositive_checkbox .= ' value="' . $this->element['name'] . '"';
			$falsepositive_checkbox .= ' onclick="';
			$falsepositive_checkbox .= $falsepositive_onclick;
			$falsepositive_checkbox .= '" class="' . $class . '"';
			$falsepositive_checkbox .= 	$falsepositive_checked;
			$falsepositive_checkbox .= '></input></div>';

			$field_data->field_desc             = $label_desc;
			$field_data->falsepositive_checkbox = '<div class="float-end">' . $falsepositive_checkbox . '</div>';
		}

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

				$label_desc  = '';
				$label_desc .= '<div><strong>';
				$label_desc .= $this->element['name'];
				$label_desc .= '</strong><br><label class="key-label normal-text ' . $direction . '">';
				$label_desc .= htmlspecialchars($this->element['sourcetext'], ENT_COMPAT, 'UTF-8');
				$label_desc .= '</label></div>';

				$field_data->field_desc = $label_desc;
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
			$field_data->textchanges_checkbox = '<div class="float-end">' . $textchanges_checkbox . '</div>';

			return $field_data;
		}
		else if ($status == 'extra' && $istranslation)
		{
			$class                = !empty($this->descText) ? "key-label hasTooltip $direction" : "key-label $direction";
			$tip                  = Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_' . strtoupper($status));
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

			$field_data->field_details .= '<span class="badge bg-info">' . Text::_('COM_LOCALISE_TEXT_TRANSLATION_' . strtoupper($status)) . '</span>';
			$field_data->field_label    = $label;
			$field_data->field_checkbox = '<div class="float-end">' . $notinref_checkbox . '</div>';

			return $field_data;
		}
		else if ($status == 'deleted' && $istranslation)
		{
			$class                = !empty($this->descText) ? "key-label hasTooltip $direction" : "key-label $direction";
			$tip                  = Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_' . strtoupper($status));
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

			$field_data->field_details .= '<span class="badge bg-danger">' . Text::_('COM_LOCALISE_TEXT_TRANSLATION_' . strtoupper($status)) . '</span>';
			$field_data->field_label    = $label;
			$field_data->field_checkbox = '<div class="float-end">' . $notinref_checkbox . '</div>';

			return $field_data;
		}
		else if ($status == 'renamed' && $istranslation)
		{
			$class                = !empty($this->descText) ? "key-label hasTooltip $direction" : "key-label $direction";
			$tip                  = Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_' . strtoupper($status));
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

			$field_data->field_details .= '<span class="badge bg-warning">' . Text::_('COM_LOCALISE_TEXT_TRANSLATION_' . strtoupper($status)) . '</span>';
			$field_data->field_label    = $label;
			$field_data->field_checkbox = '<div class="float-end">' . $notinref_checkbox . '</div>';

			return $field_data;
		}
		else if ($status == 'personalised' && $istranslation)
		{
			$class                = !empty($this->descText) ? "key-label hasTooltip $direction" : "key-label $direction";
			$tip                  = Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_' . strtoupper($status));
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

			$field_data->field_details .= '<span class="badge bg-info">' . Text::_('COM_LOCALISE_TEXT_TRANSLATION_' . strtoupper($status)) . '</span>';

			if ($is_duplicated == 1)
			{
				$field_data->field_details .= '<span class="badge bg-warning">' . Text::_('COM_LOCALISE_TEXT_TRANSLATION_DUPLICATED_AT_TAB') . '</span>';
			}

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

			if ($status == 'extra' || $status == 'deleted' || $status == 'renamed' || $status == 'personalised')
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
				$tip       = '<div> <span class="badge bg-warning text-dark grammar">'
					. Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_GRAMMAR_CASE')
					. '</span></div>';

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

	/**
	 * Search false positives for issued strings by selected data
	 *
	 * @param   object $db_data  The required data to search a false positive case.
	 *
	 * @return object
	 *
	 */
	protected static function searchFalsePositive(&$db_data)
	{
		if (!is_object($db_data))
		{
			return false;
		}

		$client            = $db_data->client;
		$reflang           = $db_data->reflang;
		$targetlang        = $db_data->targetlang;
		$filename          = $db_data->filename;
		$key               = $db_data->key;
		$reflang_string    = $db_data->reflang_string;
		$targetlang_string = $db_data->targetlang_string;

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
		$query->where($db->quoteName('key')." = :key");
		$query->where($db->quoteName('reflang_string')." = :reflang_string");
		$query->where($db->quoteName('targetlang_string')." = :targetlang_string");
		$query->bind(':client', $client);
		$query->bind(':reflang', $reflang);
		$query->bind(':targetlang', $targetlang);
		$query->bind(':filename', $filename);
		$query->bind(':key', $key);
		$query->bind(':reflang_string', $reflang_string);
		$query->bind(':targetlang_string', $targetlang_string);

		$db->setQuery($query);

		$result = $db->loadObject();

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
