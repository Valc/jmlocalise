<?php
/**
 * @package     Com_Localise
 * @subpackage  controller
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Joomla\Component\Localise\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Object\CMSObject;

/**
 * Package Controller class for the Localise component
 *
 * @package     Extensions.Components
 * @subpackage  Localise
 * @since       1.0
 */
class PackageFileController extends FormController
{
	/**
	 * The URL view list variable.
	 *
	 * @var    string
	 * @since  12.2
	 */
	protected $view_list = 'packages';

	/**
	 * Method to check if you can add a new record.
	 *
	 * Extended classes can override this if necessary.
	 *
	 * @param   array  $data  An array of input data.
	 *
	 * @return  boolean
	 */
	protected function allowAdd($data = array())
	{
		// @todo: $data parameter is unused
		return Factory::getUser()->authorise('localise.create', $this->option);
	}

	/**
	 * Method to check if you can add a new record.
	 *
	 * Extended classes can override this if necessary.
	 *
	 * @param   array   $data  An array of input data.
	 * @param   string  $key   The name of the key for the primary key.
	 *
	 * @return  boolean
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		return Factory::getUser()->authorise('localise.edit', $this->option . '.' . $data[$key]);
	}

	/**
	 * Todo: description missing
	 *
	 * @return void
	 */
	public function download()
	{
		// Initialise variables.
		$app   = Factory::getApplication();
		$input = $app->input;
		$model = $this->getModel();

		$data = $input->get('jform', array(), 'array');
		$model->download($data);
	}

	/**
	 * Method to update the translations field list by AJAX call.
	 *
	 * @return object
	 */
	public function updatetranslationslist()
	{
		try
		{
			// Case invalid token, ajax call die here.
			$this->checkToken() or die();

			// Initialise variables.
			$app   = Factory::getApplication();
			$input = $app->input;
			$reply = new CMSObject;
			$data  = $input->get('data', null, 'RAW');
			$data  = json_decode($data);

			$model       = $this->getModel();
			$html_output = $model->updateTranslationsList($data);

			if ($html_output->translations)
			{
				// If required send a notice as system message do it before "echo new JsonResponse", if not, comment next line.
		 		$app->enqueueMessage(Text::_('COM_LOCALISE_TASK_UPDATE_TRANSLATIONS_LIST_SUCCESS'), 'notice');

				// Adding a success message type "flash" to display after ajax call.
				$reply->success_message = Text::_('COM_LOCALISE_TASK_UPDATE_TRANSLATIONS_LIST_SUCCESS_FLASH');
				$reply->html            = true;
				$reply->translations    = $html_output->translations;
			}
			else
			{
				// $html_output is returning 'false'
				// If required send an error as system message do it before "echo new JsonResponse", if not, comment next line.
		 		$app->enqueueMessage(Text::_('COM_LOCALISE_TASK_UPDATE_TRANSLATIONS_LIST_ERROR'), 'error');

				// Adding an error message type "flash" to display after ajax call.
				$reply->error_message = Text::_('COM_LOCALISE_TASK_UPDATE_TRANSLATIONS_LIST_ERROR_FLASH');
				$reply->html          = false;
			}

			echo new JsonResponse($reply, 'Done!');
		}
		catch(Exception $e)
		{
			$app->enqueueMessage(Text::sprintf('COM_LOCALISE_TASK_UPDATE_TRANSLATIONS_LIST_ERROR', $e->getMessage()), 'error');

			$reply->error_message = Text::_('COM_LOCALISE_TASK_UPDATE_TRANSLATIONS_LIST_ERROR_FLASH');
			$reply->error         = $e;

			echo new JsonResponse($reply);
		}

		$app->close();
	}
}
