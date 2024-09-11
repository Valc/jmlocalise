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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Object\CMSObject;

/**
 * Package Controller class for the Localise component
 *
 * @package     Extensions.Components
 * @subpackage  Localise
 * @since       1.0
 */
class PackageController extends FormController
{
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
	 * Method for uploading a file.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function uploadFile()
	{
		$app    = Factory::getApplication();
		$model  = $this->getModel();
		$upload = $app->input->files->get('files');

		if ($return = $model->uploadFile($upload))
		{
			$app->enqueueMessage(Text::sprintf('COM_LOCALISE_FILE_UPLOAD_SUCCESS', $upload['name']));
		}
		else
		{
			$app->enqueueMessage(Text::_('COM_LOCALISE_ERROR_FILE_UPLOAD'), 'error');
		}

		$url = 'index.php?option=com_localise&view=packages';
		$this->setRedirect(Route::_($url, false));
	}

	/**
	 * Method for uploading a css or a php file in the language xx-XX folder.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function uploadOtherFile()
	{
		$app      = Factory::getApplication();
		$id       = $app->getUserState('com_localise.edit.package.id');
		$model    = $this->getModel();
		$upload   = $app->input->files->get('files');
		$location = $app->input->get('location');

		if ($location == "admin")
		{
			$location = LOCALISEPATH_ADMINISTRATOR;
		}
		elseif ($location == "site")
		{
			$location = LOCALISEPATH_SITE;
		}

		if ($return = $model->uploadOtherFile($upload, $location))
		{
			$app->enqueueMessage(Text::sprintf('COM_LOCALISE_OTHER_FILE_UPLOAD_SUCCESS', $upload['name']));
		}
		else
		{
			$app->enqueueMessage(Text::_('COM_LOCALISE_ERROR_OTHER_FILE_UPLOAD'), 'error');
		}

		$url = 'index.php?option=com_localise&task=package.edit&cid[]=' . $id;

		$this->setRedirect(Route::_($url, false));
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

			$model = $this->getModel();
			$html  = $model->updateTranslationsList($data);

			if ($html)
			{
				// If required send a notice as system message do it before "echo new JsonResponse", if not, comment next line.
		 		$app->enqueueMessage(Text::_('COM_LOCALISE_TASK_UPDATE_TRANSLATIONS_LIST_SUCCESS'), 'notice');

				// Adding a success message type "flash" to display after ajax call.
				$reply->success_message = Text::_('COM_LOCALISE_TASK_UPDATE_TRANSLATIONS_LIST_SUCCESS_FLASH');
				$reply->html            = $html;
			}
			else
			{
				// $html is returning 'false'
				// If required send an error as system message do it before "echo new JsonResponse", if not, comment next line.
		 		$app->enqueueMessage(Text::_('COM_LOCALISE_TASK_UPDATE_TRANSLATIONS_LIST_ERROR'), 'error');

				// Adding an error message type "flash" to display after ajax call.
				$reply->error_message = Text::_('COM_LOCALISE_TASK_UPDATE_TRANSLATIONS_LIST_ERROR_FLASH');
				$reply->html          = '';
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
