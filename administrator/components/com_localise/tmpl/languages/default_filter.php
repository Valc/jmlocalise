<?php
/**
 * @package     Com_Localise
 * @subpackage  views
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Object\CMSObject;

$params = (isset($this->state->params)) ? $this->state->params : new CMSObject;

?>
<div class="row">
<!-- Begin Content -->
<div class="col-md-12">
<div id="j-main-container" class="j-main-container">
	<div class="js-tools clearfix">
	<div id="filter-bar hidden-phone" class="btn-toolbar">
		<!--<div class="filter-select hidden-phone"> -->
		<div class="js-stools-field-filter">
			<?php foreach($this->form->getFieldset('select') as $field): ?>
				<?php echo $field->input; ?>
			<?php endforeach; ?>
		</div>
		<div class="js-stools-field-filter">
			<?php foreach($this->form->getFieldset('search') as $field): ?>
				<?php echo $field->input; ?>
			<?php endforeach; ?>
		</div>

		<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this, 'options' => array('filterButton' => false))); ?>
	</div>
