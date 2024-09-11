<?php
/**
 * @package     Com_Localise
 * @subpackage  views
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Joomla\Component\Localise\Administrator\View\Languages;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Languages View class for the Localise component
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

	protected $state;

	protected $form;

	public $filterForm;

	public $activeFilters;

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
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state      = $this->get('State');
		$this->form       = $this->get('Form');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		unset($this->activeFilters['client']);
		unset($this->activeFilters['tag']);

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);

			return false;
		}

		// Set the toolbar
		$this->addToolbar();
		$this->sidebar = HTMLHelper::_('sidebar.render');	

		// Display the view
		parent::display($tpl);
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

		ToolbarHelper::title(Text::sprintf('COM_LOCALISE_HEADER_MANAGER', Text::_('COM_LOCALISE_HEADER_LANGUAGES')), 'comments-2 langmanager');

		if ($canDo->get('localise.create'))
		{
			ToolbarHelper::addNew('language.add');
			ToolbarHelper::custom('languages.purge', 'purge', 'purge', 'COM_LOCALISE_PURGE', false, false);
			ToolbarHelper::custom('languages.reformat', 'refresh', 'reformat', 'COM_LOCALISE_REFORMAT', false, false);
			ToolbarHelper::divider();
		}

		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::preferences('com_localise');
			ToolbarHelper::divider();
		}

		// ToolBarHelper::help('screen.languages', true);
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
			'name'   => Text::_('COM_LOCALISE_HEADING_LANGUAGES_NAME'),
			'tag'    => Text::_('COM_LOCALISE_HEADING_LANGUAGES_TAG'),
			'client' => Text::_('COM_LOCALISE_HEADING_LANGUAGES_CLIENT'),
		);
	}
}
