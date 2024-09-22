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

/**
 * Form Field Ini class.
 *
 * @package     Extensions.Components
 * @subpackage  Localise
 *
 * @since       1.0
 */
class IniField extends FormField
{
	/**
	 * The field type.
	 *
	 * @var    string
	 */
	protected $type = 'Ini';

	/**
	 * Base path for editor files
	 */
	protected $basePath = 'media/vendor/codemirror/';

	/**
	 * Method to get the field input.
	 *
	 * @return  string    The field input.
	 */
	protected function getInput()
	{
		HTMLHelper::_('behavior.core');
		HTMLHelper::_('jquery.framework');
		$basePath = 'media/com_localise/js/';
		// Load Codemirror
		HTMLHelper::_('script', $basePath . 'codemirror.min.js', array('version' => 'auto'));
		HTMLHelper::_('script', $basePath . 'codemirror-ce.min.js', array('version' => 'auto'));
		HTMLHelper::_('script', $basePath . 'addons.min.js', array('version' => 'auto'));
		HTMLHelper::_('stylesheet', $basePath . 'codemirror.css', array('version' => 'auto'));
		HTMLHelper::_('script', $basePath . 'joomla-editor-codemirror.min.js', array('version' => 'auto', 'relative' => true));

		// Load Joomla language ini parser
		HTMLHelper::_('script', 'com_localise/parseini.js', array('version' => 'auto', 'relative' => true));
		HTMLHelper::_('stylesheet', 'com_localise/localise.css', array('version' => 'auto', 'relative' => true));

		$rows   = (string) $this->element['rows'];
		$cols   = (string) $this->element['cols'];
		$class  = (string) $this->class ? ' class="' . (string) $this->class . '"' : ' class="text_area"';

		$options = new \stdClass;

		$options->mode = 'text/parseini';
		$options->tabMode = 'default';
		$options->smartIndent = true;
		$options->lineNumbers = true;
		$options->foldGutter = true;
		$options->lineWrapping = true;
		$options->autoCloseBrackets = true;
		$options->showTrailingSpace = true;
		$options->styleActiveLine = true;
		$options->gutters = array('CodeMirror-linenumbers', 'CodeMirror-foldgutter', 'breakpoints');

		$html = array();
		$html[] = '<textarea' . $class . ' name="' . $this->name . '" id="' . $this->id . '" cols="' . $cols . '" rows="' . $rows . '">'
				. htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '</textarea>';

		Factory::getDocument()->addScriptDeclaration("
			(function($){
				$(document).ready(function() {
					var editor = CodeMirror.fromTextArea(document.getElementById('" . $this->id . "'), " . json_encode($options) . ");
					editor.setOption('extraKeys', {
						'F10': function(cm) {
							cm.setOption('fullScreen', !cm.getOption('fullScreen'));
						},
						'Esc': function(cm) {
							if (cm.getOption('fullScreen')) cm.setOption('fullScreen', false);
						}
					});
					editor.on('gutterClick', function(cm, n) {
						var info = cm.lineInfo(n)
						cm.setGutterMarker(n, 'breakpoints', info.gutterMarkers ? null : makeMarker())
					})
					function makeMarker() {
						var marker = document.createElement('div')
						marker.style.color = '#822';
						marker.innerHTML = '●'
						return marker
					}
					Joomla.editors.instances['" . $this->id . "'] = editor;
				});
			})(jQuery);
		");

		return implode("\n", $html);
	}

	/**
	 * Get the save javascript code.
	 *
	 * @return  string
	 */
	public function save()
	{
		return "document.getElementById('" . $this->id . "').value = Joomla.editors.instances['" . $this->id . "'].getValue();\n";
	}
}
