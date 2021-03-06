<?php
/**
 * @package     Com_Localise
 * @subpackage  views
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('jquery.framework');

$fieldSets = $this->form->getFieldsets();
$ftpSets   = $this->formftp->getFieldsets();

Text::script('COM_LOCALISE_MSG_CONFIRM_PACKAGE_SAVE');

Factory::getDocument()->addScriptDeclaration("
	Joomla.submitbutton = function(task)
	{
		if ((task == 'packagefile.apply' || task == 'packagefile.save') && document.formvalidator.isValid(document.getElementById('localise-package-form')))
		{
			if (confirm(Joomla.JText._('COM_LOCALISE_MSG_CONFIRM_PACKAGE_SAVE')))
			{
				Joomla.submitform(task, document.getElementById('localise-package-form'));
			}
		}
		else if (task == 'packagefile.cancel' || task == 'packagefile.download')
		{
			Joomla.submitform(task, document.getElementById('localise-package-form'));
		}
	}
");

Factory::getDocument()->addScriptDeclaration("
function updateTranslationsList() {
	var packagename   = jQuery('#jform_name').val();
	var languagetag   = jQuery('#jform_language').val();
	var token         = '". Session::getFormToken() ."';
	var required_data = JSON.stringify([{
										'packagename' : packagename,
										'languagetag' : languagetag
										}]);
	jQuery.post('index.php',{
		'option'     : 'com_localise',
		'controller' : 'packagefile',
		'task'       : 'packagefile.updatetranslationslist',
		'format'     : 'raw',
		'data'       : required_data,
		[token]      : '1',
		'dataType'   : 'json'
		})
	.done(function(result, textStatus, jqXHR)
	{
		const reply = JSON.parse(result);
		//console.log(reply);

		if (!reply.success && reply.message)
		{
			// Success flag is set to 'false' and main response message given
			// so we can alert it or insert it into some HTML element
			alert(result.message);
		}

		if (reply.messages)
		{
			// All the enqueued messages of the app object can simple be
			// rendered by the respective helper function of Joomla!
			// They will automatically be displayed at the messages section of the template
			Joomla.renderMessages(reply.messages);
		}

		if (reply.data)
		{
			// Here we can access all the data of our response

			if (reply.success)
			{
				if (reply.data.html)
				{
					jQuery('#jform_translations').html(reply.data.translations);
				}

				if (reply.data.success_message)
				{
					jQuery('#flash-message-success').empty().show().html(reply.data.success_message).delay(2000).fadeOut(300);
				}
				else if (reply.data.error_message)
				{
					jQuery('#flash-message-danger').empty().show().html(reply.data.error_message).delay(2000).fadeOut(300);
				}
			}
			else
			{
				if (reply.data.error_message)
				{
					jQuery('#flash-message-danger').empty().show().html(reply.data.error_message).delay(2000).fadeOut(300);
				}
			}
		}
	})
	.fail(function(jqXHR, textStatus, errorThrown)
	{
		//console.log('ajax call failed\\n' + textStatus + '\\n'+ errorThrown);

		// Reaching this point means that the Ajax request itself was not successful
		// So JsonResponse was never called

		// Here we can handle an alert message type 'System message', creating it. Sample:
		//var messages = {
		//					'message': ['Sample message one', 'Sample message two'],
		//					'error'  : ['Sample error one', 'Sample error two']
		//};

		var messages = {
						'error': ['" . Text::_('COM_LOCALISE_TASK_THROWN_ERROR') . "']
		};

		Joomla.renderMessages(messages);

		// Here we can handle an alert message type 'flash'.
		jQuery('#flash-message-danger').empty().show().html('" . Text::_('COM_LOCALISE_TASK_THROWN_ERROR_FLASH') . "').delay(2000).fadeOut(300);
	});
}

jQuery(document).ready(function() {
	jQuery('#jform_language').change(function(){
		updateTranslationsList();
	})
});
");
?>
<?php
	echo '<div class="text-center"><div id="flash-message-danger" class="alert alert-danger flash-message" style="display: none;"></div></div>';
	echo '<div class="text-center"><div id="flash-message-notice" class="alert alert-notice flash-message" style="display: none;"></div></div>';
	echo '<div class="text-center"><div id="flash-message-success" class="alert alert-success flash-message" style="display: none;"></div></div>';
?>
<form action="<?php echo Route::_('index.php?option=com_localise&view=packagefile&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="localise-package-form" class="form-validate">
	<div class="row-fluid">
		<!-- Begin Localise Package -->
		<div class="col-md-12">
				<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => $this->ftp ? 'ftp' : 'default')); ?>
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
					<?php echo HTMLHelper::_('uitab.endTab');; ?>
					<?php endif; ?>
					<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'default', Text::_($fieldSets['default']->label, true)); ?>
						<div class="row">
						<div class="col-lg-12 col-xl-6">
							<fieldset id="fieldset-default" class="options-form">
								<legend><?php echo Text::_('COM_LOCALISE_FIELDSET_PACKAGE_DETAIL'); ?></legend>
								<?php foreach($this->form->getFieldset('default') as $field) : ?>
									<?php echo $field->renderField(); ?>
								<?php endforeach; ?>
							</fieldset>
						</div>
						<div class="col-lg-12 col-xl-6">
							<fieldset id="fieldset-translations" class="options-form">
								<legend><?php echo Text::_($fieldSets['translations']->label); ?></legend>
								<?php if (!empty($fieldSets['translations']->description)):?>
										<p><?php echo Text::_($fieldSets['translations']->description); ?></p>
								<?php endif;?>
								<?php foreach($this->form->getFieldset('translations') as $field) : ?>
									<?php echo $field->renderField(); ?>
								<?php endforeach; ?>
							</fieldset>
						</div>
						</div>
					<?php echo HTMLHelper::_('uitab.endTab'); ?>
					<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_($fieldSets['permissions']->label, true)); ?>
						<fieldset id="fieldset-rules" class="options-form">
							<legend><?php echo Text::_($fieldSets['permissions']->label, true); ?></legend>
							<div>
								<?php echo $this->form->getInput('rules'); ?>
							</div>
						</fieldset>
					<?php echo HTMLHelper::_('uitab.endTab'); ?>
					<input type="hidden" name="task" value="" />
					<?php echo HTMLHelper::_('form.token'); ?>
				<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
		</div>
		<!-- End Localise Package -->
	</div>
</form>
