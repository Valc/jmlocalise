<?php
/**
 * @package     Com_Localise
 * @subpackage  views
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Version;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

use Joomla\Component\Localise\Administrator\Helper\LocaliseHelper;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('stylesheet', 'com_localise/localise.css', ['version' => 'auto', 'relative' => true]);
HTMLHelper::_('jquery.framework');

$parts = explode('-', $this->state->get('translation.reference'));
$src   = $parts[0];
$parts = explode('-', $this->state->get('translation.tag'));
$dest  = $parts[0];

// No use to filter if target language is also reference language
if ($this->state->get('translation.reference') != $this->state->get('translation.tag'))
{
	$istranslation = 1;
}
else
{
	$istranslation = 0;
}

$params            = ComponentHelper::getParams('com_localise');
$ref_tag           = $params->get('reference', 'en-GB');
$allow_develop     = $params->get('gh_allow_develop', 0);
$saved_ref         = $params->get('customisedref', 0);
$source_ref        = $saved_ref;
$istranslation     = $this->item->istranslation;
$filestate         = $this->item->filestate;
$installed_version = new Version;
$installed_version = $installed_version->getShortVersion();

if ($saved_ref == 0)
{
	$source_ref = $installed_version;
}

if ($saved_ref != 0 && $allow_develop == 1 && $ref_tag == 'en-GB' && $istranslation == 0)
{
	Factory::getApplication()->enqueueMessage(
		Text::sprintf('COM_LOCALISE_NOTICE_EDIT_REFERENCE_HAS_LIMITED_USE', $source_ref),
		'notice');
}

$app      = Factory::getApplication();
$input    = $app->input;
$posted   = $input->post->get('jform', array(), 'array');
$tabstate = $app->getUserState ('com_localise.translation.edit.tabstate');

if (empty($tabstate))
{
	// If empty select here the default tab by name.
	$tabstate = 'default';
}

$has_translatedkeys   = !empty($this->item->translatedkeys) ? 1 : 0;
$has_untranslatedkeys = !empty($this->item->untranslatedkeys) ? 1 : 0;
$has_unchangedkeys    = !empty($this->item->unchangedkeys) ? 1 : 0;
$has_textchangedkeys  = !empty($this->item->textchangedkeys) ? 1 : 0;
$has_extrakeys        = !empty($this->item->extrakeys) ? 1 : 0;
$has_deletedkeys      = 0;
$has_renamedkeys      = 0;
$has_pluralkeys       = 0;
$has_issuedkeys       = 0;

if ($istranslation == 1 && $ref_tag == 'en-GB')
{
	$has_deletedkeys      = (!empty($this->item->deletedkeys) || !empty($this->item->storeddeletedkeys)) ? 1 : 0;
	$has_renamedkeys      = (!empty($this->item->renamedkeys) || !empty($this->item->storedrenamedkeys)) ? 1 : 0;
	$has_pluralkeys       = !empty($this->item->pluralkeys) ? 1 : 0;
	$has_issuedkeys       = !empty($this->item->issuedkeys) ? 1 : 0;
	$has_unchecked        = ($this->item->unchecked > 0) ? 1 : 0;

	if ($has_unchecked == 1)
	{
		Factory::getApplication()->enqueueMessage(
			Text::sprintf('COM_LOCALISE_NOTICE_EDIT_UNCHECKED_PARSING_ISSUES', $this->item->unchecked),
			'warning');
	}
}

if (isset($posted['select']['keystatus'])
	&& !empty($posted['select']['keystatus'])
	&& $posted['select']['keystatus'] != 'allkeys'
	)
{
	$filter = $posted['select']['keystatus'];

	if ($filter == 'deletedkeys')
	{
		$deleted       = array ($this->item->deletedkeys);
		$storeddeleted = array ($this->item->storeddeletedkeys);

		if (!empty($deleted) && !empty($storeddeleted))
		{
			$keystofilter = array_merge($deleted, $storeddeleted);
		}
		else if (!empty($deleted))
		{
			$keystofilter = $deleted;
		}
		else if (!empty($storeddeleted))
		{
			$keystofilter = $storeddeleted;
		}
	}
	else if ($filter == 'renamedkeys')
	{
		$renamed       = array ($this->item->renamedkeys);
		$storedrenamed = array ($this->item->storedrenamedkeys);

		if (!empty($renamed) && !empty($storedrenamed))
		{
			$keystofilter = array_merge($renamed, $storedrenamed);
		}
		else if (!empty($renamed))
		{
			$keystofilter = $renamed;
		}
		else if (!empty($storedrenamed))
		{
			$keystofilter = $storedrenamed;
		}
	}
	else
	{
		$keystofilter = array ($this->item->$filter);
	}

	$tabstate   = 'strings';

	$app->setUserState ('com_localise.translation.edit.tabstate', 'strings');
}
elseif (empty($posted['select']['keystatus']))
{
	$filter       = 'allkeys';
	$keystofilter = array();
	//$tabstate   = 'default';
}
else
{
	$filter       = 'allkeys';
	$keystofilter = array();
	//$tabstate   = 'default';
}

$fieldSets = $this->form->getFieldsets();
$sections  = $this->form->getFieldsets('strings');
$ftpSets   = $this->formftp->getFieldsets();

if ($istranslation && $filestate == 'inlanguage')
{
	Factory::getDocument()->addScriptDeclaration("
		function returnAll()
		{
			$('.return').trigger('click');
		}

		(function($){
			$(document).ready(function() {
				var has_translatedkeys   = " . $has_translatedkeys . ";
				var has_untranslatedkeys = " . $has_untranslatedkeys . ";
				var has_unchangedkeys    = " . $has_unchangedkeys . ";
				var has_textchangedkeys  = " . $has_textchangedkeys . ";
				var has_extrakeys        = " . $has_extrakeys . ";
				var has_deletedkeys      = " . $has_deletedkeys . ";
				var has_renamedkeys      = " . $has_renamedkeys . ";
				var has_pluralkeys       = " . $has_pluralkeys . ";
				var has_issuedkeys       = " . $has_issuedkeys . ";

				if (has_translatedkeys == '0')
				{
					var x = document.getElementById('jform_select_keystatus').options[2].disabled = true;
				}

				if (has_untranslatedkeys == '0')
				{
					var x = document.getElementById('jform_select_keystatus').options[3].disabled = true;
				}

				if (has_unchangedkeys == '0')
				{
					var x = document.getElementById('jform_select_keystatus').options[4].disabled = true;
				}

				if (has_textchangedkeys == '0')
				{
					var x = document.getElementById('jform_select_keystatus').options[5].disabled = true;
				}

				if (has_pluralkeys == '0')
				{
					var x = document.getElementById('jform_select_keystatus').options[6].disabled = true;
				}

				if (has_renamedkeys == '0')
				{
					var x = document.getElementById('jform_select_keystatus').options[7].disabled = true;
				}

				if (has_deletedkeys == '0')
				{
					var x = document.getElementById('jform_select_keystatus').options[8].disabled = true;
				}

				if (has_extrakeys == '0')
				{
					var x = document.getElementById('jform_select_keystatus').options[9].disabled = true;
				}

				if (has_issuedkeys == '0')
				{
					var x = document.getElementById('jform_select_keystatus').options[10].disabled = true;
				}
			});
		})(jQuery);
	");
}
else
{
	Factory::getDocument()->addScriptDeclaration("
		function returnAll()
		{
			$('.return').trigger('click');
		}
	");
}

Factory::getDocument()->addScriptDeclaration("
	(function($){
		$(document).ready(function() {
			$('#myTab').click(function(){

				// Getting the form to use.
				var form = $('#localise-translation-form');

				// Searching the actual tab
				var actual = form.find('joomla-tab-element[active]').attr('id');

				// Save the actual tab value to the hidden form field 'tabstate'
				form.find('input[name=tabstate]').val(actual);
			});
		});
	})(jQuery);
");
?>
<form action="" method="post" name="adminForm" id="localise-translation-form" class="form-validate">
	<div class="row">
		<!-- Begin Localise Translation -->
		<div class="col-md-12 form-horizontal">
				<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => $tabstate)); ?>
					<?php if ($this->ftp) : ?>
						<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'ftp', Text::_($ftpSets['ftp']->label, true)); ?>
							<?php if (!empty($ftpSets['ftp']->description)):?>
								<p class="tip"><?php echo Text::_($ftpSets['ftp']->description); ?></p>
							<?php endif;?>
							<?php if ($this->ftp instanceof Exception): ?>
								<p class="error"><?php echo Text::_($this->ftp->message); ?></p>
							<?php endif; ?>
							<?php foreach($this->formftp->getFieldset('ftp',false) as $field) : ?>
								<?php echo $field->renderField(); ?>
							<?php endforeach; ?>
						<?php echo HTMLHelper::_('uitab.endTab'); ?>
					<?php endif; ?>
					<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'default', Text::_($fieldSets['default']->label, true)); ?>
						<?php if (!empty($fieldSets['default']->description)) : ?>
							<p class="alert alert-info"><?php echo Text::_($fieldSets['default']->description); ?></p>
						<?php endif;?>
						<?php foreach($this->form->getFieldset('default') as $field) : ?>
							<?php echo $field->renderField(); ?>
						<?php endforeach; ?>
					<?php echo HTMLHelper::_('uitab.endTab'); ?>
					<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'strings', Text::_('COM_LOCALISE_FIELDSET_TRANSLATION_STRINGS')); ?>
						<div class="alert alert-info">
							<span class="fas fa-info-circle info-line" aria-hidden="true"></span><span class="sr-only"><?php echo Text::_('INFO'); ?></span>
							<?php if ($istranslation) : ?>
								<?php echo Text::_('COM_LOCALISE_TRANSLATION_NOTICE'); ?>
							<?php else : ?>
								<?php echo Text::_('COM_LOCALISE_TRANSLATION_NOTICE_ENGB'); ?>
							<?php endif; ?>
						</div>
						<?php echo HTMLHelper::_('bootstrap.startAccordion', 'slide-legend', array('active' => '')); ?>
						<?php echo HTMLHelper::_('bootstrap.addSlide', 'slide-legend', Text::_($fieldSets['legend']->label), 'legend'); ?>
							<div>
								<p class="tip"><?php echo Text::_('COM_LOCALISE_LABEL_TRANSLATION_KEY'); ?></p>
								<ul class="adminformlist">
									<?php foreach($this->form->getFieldset('legend') as $field) : ?>
										<li>
											<?php echo $field->input; ?>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php echo HTMLHelper::_('bootstrap.endSlide'); ?>
						<?php echo HTMLHelper::_('bootstrap.endAccordion'); ?>
						<div class="key">
							<div id="translationbar">
								<?php if ($istranslation && $filestate == 'inlanguage') : ?>
									<div class="pull-left">
										<?php foreach($this->form->getFieldset('select') as $field): ?>
											<?php if ($field->type != "Spacer") : ?>
												<?php
													$field->value = $filter;
													echo Text::_('JSEARCH_FILTER_LABEL');
													echo $field->input;
												?>
											<?php else : ?>
												<?php echo $field->label; ?>
											<?php endif; ?>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
								<?php if ($filestate != 'notinref') : ?>
								<a href="javascript:void(0);" class="btn btn-small" onclick="returnAll();">
									<i class="icon-reset"></i> <?php echo Text::_('COM_LOCALISE_BUTTON_RESET_ALL');?>
								</a>
								<?php endif; ?>
							</div>
							<?php
								if (count($sections) > 1 && $filter == 'allkeys') :
									echo '<div class="clearfix"></div>';
									echo HTMLHelper::_('bootstrap.startAccordion', 'localise-translation-sliders');
									$i = 0;
									foreach ($sections as $name => $fieldSet) :
										echo HTMLHelper::_('bootstrap.addSlide', 'localise-translation-sliders', Text::_($fieldSet->label), 'collapse' . $i++);
										if ($fieldSet->label == "COM_LOCALISE_TEXT_TRANSLATION_EXTRA") : ?>
											<div class="alert alert-info">
												<span class="fas fa-info-circle info-line" aria-hidden="true"></span><span class="sr-only">
												<?php echo Text::_('INFO'); ?></span>
												<?php if ($istranslation) : ?>
													<?php echo Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_EXTRA_KEYS_IN_TRANSLATION'); ?>
												<?php else : ?>
													<?php echo Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_KEYS_TO_DELETE'); ?>
												<?php endif; ?>
											</div>
										<?php elseif ($fieldSet->label == "COM_LOCALISE_TEXT_TRANSLATION_PERSONALISED") : ?>
											<div class="alert alert-info">
												<span class="fas fa-info-circle info-line" aria-hidden="true"></span><span class="sr-only">
												<?php echo Text::_('INFO'); ?></span>
												<?php if ($istranslation) : ?>
													<?php echo Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_PERSONALISED_KEYS_IN_TRANSLATION'); ?>
												<?php endif; ?>
											</div>
										<?php elseif ($fieldSet->label == "COM_LOCALISE_TEXT_TRANSLATION_RENAMED") : ?>
											<div class="alert alert-info">
												<span class="fas fa-info-circle info-line" aria-hidden="true"></span><span class="sr-only">
												<?php echo Text::_('INFO'); ?></span>
												<?php if ($istranslation) : ?>
													<?php echo Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_RENAMED_KEYS_IN_TRANSLATION'); ?>
												<?php endif; ?>
											</div>
										<?php elseif ($fieldSet->label == "COM_LOCALISE_TEXT_TRANSLATION_DELETED") : ?>
											<div class="alert alert-info">
												<span class="fas fa-info-circle info-line" aria-hidden="true"></span><span class="sr-only">
												<?php echo Text::_('INFO'); ?></span>
												<?php if ($istranslation) : ?>
													<?php echo Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_DELETED_KEYS_IN_TRANSLATION'); ?>
												<?php endif; ?>
											</div>
										<?php endif; ?>
										<div class="table-responsive"><table class="table table-hover"><tbody>
											<?php foreach ($this->form->getFieldset($name) as $field) :
												echo LocaliseHelper::getKeyHtmlOutput($field, $filter, $keystofilter);
												endforeach;
											?>
										</tbody></table></div>
									<?php
									echo HTMLHelper::_('bootstrap.endSlide');
									endforeach;
									echo HTMLHelper::_('bootstrap.endAccordion');
									?>
								<?php elseif (count($sections) > 1 && $filter != 'allkeys') :
									echo '<div class="clearfix"></div>';
									$display_section_comment = true;
									foreach ($sections as $name => $fieldSet) : ?>
										<?php if ($istranslation && $display_section_comment == true) : ?>
											<?php $display_section_comment = false; ?>
											<?php echo LocaliseHelper::getSectionHtmlOutput($name, $filter); ?>
										<?php endif; ?>
										<div class="table-responsive"><table class="table table-hover"><tbody>
											<?php foreach ($this->form->getFieldset($name) as $field) :
												echo LocaliseHelper::getKeyHtmlOutput($field, $filter, $keystofilter);
												endforeach;
											?>
										</tbody></table></div>
									<?php
									endforeach;
									?>
								<?php else : ?>
									<?php if ($istranslation && $filestate == 'notinref') : ?>
										<div class="alert alert-info">
											<span class="fas fa-info-circle info-line" aria-hidden="true"></span><span class="sr-only">
											<?php echo Text::_('INFO'); ?></span>
											<?php echo Text::_('COM_LOCALISE_TOOLTIP_TRANSLATION_FILESTATE_NOTINREF'); ?>
										</div>
									<?php endif; ?>
									<div class="table-responsive"><table class="table table-hover"><tbody>
										<?php $sections = array_keys($sections); ?>
										<?php foreach ($this->form->getFieldset($sections[0]) as $field) :
											echo LocaliseHelper::getKeyHtmlOutput($field, $filter, $keystofilter);
											endforeach;
										?>
									</tbody></table></div>
								<?php endif; ?>
						</div>

					<?php echo HTMLHelper::_('uitab.endTab'); ?>
					<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_($fieldSets['permissions']->label, true)); ?>
						<?php if (!empty($fieldSets['permissions']->description)):?>
							<p class="tip"><?php echo Text::_($fieldSets['permissions']->description); ?></p>
						<?php endif;?>
						<?php foreach($this->form->getFieldset('permissions') as $field) : ?>
							<div class="control-group form-vertical">
								<div class="controls">
									<?php echo $field->input; ?>
								</div>
							</div>
						<?php endforeach; ?>
					<?php echo HTMLHelper::_('uitab.endTab'); ?>
				<?php echo HTMLHelper::_('uitab.endTabSet'); ?>

		</div>
		<!-- End Localise Translation -->
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="falsepositive" value="" />
		<input type="hidden" name="notinref" value="" />
		<input type="hidden" name="tabstate" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>

	</div>
</form>
