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
use Joomla\CMS\MVC\Controller\FormController;

/**
 * Translation Controller class for the Localise component
 *
 * @package     Extensions.Components
 * @subpackage  Localise
 * @since       1.0
 */
class TranslationController extends FormController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The name of the model.
	 * @param   string  $prefix  The prefix for the PHP class name.
	 * @param   array   $config  The array of possible config values. Optional.
	 *
	 * @return  object  The model.
	 */
	public function getModel($name = 'Translation', $prefix = 'Administrator', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, array('ignore_request' => false));
	}

	/**
	 * Method to check if you can edit a record.
	 *
	 * @param   array   $data  An array of input data.
	 * @param   string  $key   The name of the key for the primary key.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		return Factory::getUser()->authorise('localise.edit', 'com_localise.' . $data[$key]);
	}

	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param   integer  $recordId  The primary key id for the item.
	 * @param   string   $urlVar    The name of the URL variable for the id.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   1.6
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		// Get the infos
		$app      = Factory::getApplication();
		$input    = $app->input;
		$client   = $input->get('client', '');
		$tag      = $input->get('tag', '');
		$filename = $input->get('filename', '');
		$storage  = $input->get('storage', '');
		$task     = $input->get('task', '');
		$tabstate = $input->get('tabstate', '');

		if(!empty($task) && $task == 'apply')
		{
			$app->setUserState ('com_localise.translation.edit.tabstate', $tabstate);
		}
		else
		{
			$app->setUserState ('com_localise.translation.edit.tabstate', '');
		}

		// Get the append string
		$append  = parent::getRedirectToItemAppend($recordId, $urlVar);
		$append .= '&client=' . $client . '&tag=' . $tag . '&filename=' . $filename . '&storage=' . $storage;

		return $append;
	}
}
