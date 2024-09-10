<?php
/**
 * @package     Com_Localise
 * @subpackage  views
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Joomla\Component\Localise\Administrator\View\Packagefiles;

defined('_JEXEC') or die;

use Joomla\Component\Localise\Administrator\Helper\LocaliseHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Packages View class for the Localise component
 *
 * @package     Extensions.Components
 * @subpackage  Localise
 *
 * @since       1.0
 */
class HtmlView extends BaseHtmlView
{
	protected $items;

	protected $pagination;

	protected $form;

	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		// Get the data
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->form          = $this->get('Form');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);

			return false;
		}

		// Set the toolbar
		$this->addToolbar();
		$this->sidebar = HTMLHelper::_('sidebar.render');

		// Prepare the document
		$this->prepareDocument();

		// Display the view
		parent::display($tpl);
	}

	/**
	 * Prepare Document
	 *
	 * @return  void
	 */
	protected function prepareDocument()
	{
		$document = Factory::getDocument();
		$document->setTitle(Text::sprintf('COM_LOCALISE_TITLE', Text::_('COM_LOCALISE_TITLE_PACKAGES')));
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function addToolbar()
	{
		$canDo = ContentHelper::getActions('com_localise', 'component');

		ToolBarHelper::title(Text::sprintf('COM_LOCALISE_HEADER_MANAGER', Text::_('COM_LOCALISE_HEADER_PACKAGES')), 'install');

		if ($canDo->get('localise.create'))
		{
			ToolbarHelper::addNew('package.add');
		}

		if ($canDo->get('localise.create'))
		{
			ToolbarHelper::addNew('packagefile.add', 'COM_LOCALISE_NEW_FILE_PACKAGE');
		}

		if ($canDo->get('localise.edit'))
		{
			ToolbarHelper::editList('package.edit');
		}

		if ($canDo->get('localise.create') || $canDo->get('localise.edit'))
		{
			ToolbarHelper::divider();
		}

		if ($canDo->get('localise.delete'))
		{
			ToolbarHelper::deleteList('COM_LOCALISE_MSG_PACKAGES_VALID_DELETE', 'packages.delete');
			ToolBarHelper::divider();
		}

		ToolBarHelper::custom('package.download', 'out.png', 'out.png', 'JTOOLBAR_EXPORT', true);
		ToolBarHelper::divider();

		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::preferences('com_localise');
			ToolbarHelper::divider();
		}

		// ToolBarHelper::help('screen.packages', true);
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 *
	 * @since 3.0
	 */
	protected function getSortFields()
	{
		return array(
			'title' => Text::_('COM_LOCALISE_HEADING_PACKAGES_TITLE'),
		);
	}
}
