<?php
/**
 * @package     Com_Localise
 * @subpackage  model
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Localise\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\Component\Localise\Administrator\Helper\LocaliseHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Access\Access as JAccess;
use Joomla\CMS\Access\Rules as JAccessRules;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Client\ClientHelper;
use Joomla\CMS\Filesystem\Stream;
use Joomla\CMS\Object\CMSObject;

/**
 * Translation Model class for the Localise component
 *
 * @since  1.0
 */
class TranslationModel extends AdminModel
{
	protected $item;

	protected $contents;

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$input = Factory::getApplication()->input;

		// Get the infos
		$client   = $input->getCmd('client', '');
		$tag      = $input->getCmd('tag', '');
		$filename = $input->getCmd('filename', '');
		$storage  = $input->getCmd('storage', '');

		$this->setState('translation.client', !empty($client) ? $client : 'site');
		$this->setState('translation.tag', $tag);
		$this->setState('translation.filename', $filename);
		$this->setState('translation.storage', $storage);

		// Get the id
		$id = $input->getInt('id', '0');
		$this->setState('translation.id', $id);

		// Get the layout
		$layout = $input->getCmd('layout', 'edit');
		$this->setState('translation.layout', $layout);

		// Get the parameters
		$params = ComponentHelper::getParams('com_localise');

		// Get the reference tag
		$ref = $params->get('reference', 'en-GB');
		$this->setState('translation.reference', $ref);

		// Get the paths
		$path = LocaliseHelper::getTranslationPath($client, $tag, $filename, $storage);

		if ($filename == 'lib_joomla')
		{
			$refpath = LocaliseHelper::findTranslationPath('administrator', $ref, $filename);

			if (!File::exists($path))
			{
				$path2 = LocaliseHelper::getTranslationPath($client == 'administrator' ? 'site' : 'administrator', $tag, $filename, $storage);

				if (File::exists($path2))
				{
					$path = $path2;
				}
			}
		}
		else
		{
			$refpath = LocaliseHelper::findTranslationPath($client, $ref, $filename);
		}

		$this->setState('translation.path', $path);
		$this->setState('translation.refpath', $refpath);
	}

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   type    $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable  A database object
	 */
	public function getTable($type = 'LocaliseTable', $prefix = '\\Joomla\\Component\\Localise\\Administrator\\Table\\', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Get contents
	 *
	 * @return string
	 */
	public function getContents()
	{
		if (!isset($this->contents))
		{
			$path = $this->getState('translation.path');

			if (File::exists($path))
			{
				$this->contents = file_get_contents($path);
			}
			else
			{
				$this->contents = '';
			}
		}

		return $this->contents;
	}

	/**
	 * Get a translation
	 *
	 * @param   integer  $pk  The id of the primary key (Note unused by the function).
	 *
	 * @return  CMSObject|null  Object on success, null on failure.
	 */
	public function getItem($pk = null)
	{
		if (!isset($this->item))
		{
			$conf    = Factory::getConfig();
			$caching = $conf->get('caching') >= 1;

			if ($caching)
			{
				$keycache   = $this->getState('translation.client') . '.' . $this->getState('translation.tag') . '.' .
					$this->getState('translation.filename') . '.' . 'translation';
				$cache      = Factory::getCache('com_localise', '');
				$this->item = $cache->get($keycache);

				if ($this->item && $this->item->reference != $this->getState('translation.reference'))
				{
					$this->item = null;
				}
			}
			else
			{
				$this->item = null;
			}

			if (!$this->item)
			{
				$path = File::exists($this->getState('translation.path'))
					? $this->getState('translation.path')
					: $this->getState('translation.refpath');

				$params        = ComponentHelper::getParams('com_localise');
				$allow_develop = $params->get('gh_allow_develop', 0);
				$gh_client     = $this->getState('translation.client');
				$tag           = $this->getState('translation.tag');
				$reftag        = $this->getState('translation.reference');
				$refpath       = $this->getState('translation.refpath');
				$istranslation = 0;
				$filestate     = '';

				$stored_deleted_keys = array();
				$stored_renamed_keys = array();

				if (!empty($tag) && $tag != $reftag)
				{
					$istranslation = 1;
				}

				if ($istranslation == 1)
				{
                    if (!is_string($this->getState('translation.refpath')))
                    {
                        $filestate = 'notinref';
                    }
					else if (File::exists($this->getState('translation.refpath')) && File::exists($this->getState('translation.path')))
					{
						$filestate = 'inlanguage';
					}
					else if (!File::exists($this->getState('translation.refpath')) || File::exists($this->getState('translation.path')))
					{
						$filestate = 'notinref';
					}
				}

				$this->setState('translation.filestate', $filestate);
				$this->setState('translation.translatedkeys', array());
				$this->setState('translation.untranslatedkeys', array());
				$this->setState('translation.unchangedkeys', array());
				$this->setState('translation.textchangedkeys', array());
				$this->setState('translation.revisedchanges', array());
				$this->setState('translation.extrakeys', array());
				$this->setState('translation.deletedkeys', array());
				$this->setState('translation.renamedkeys', array());
				$this->setState('translation.storeddeletedkeys', array());
				$this->setState('translation.storedrenamedkeys', array());
				$this->setState('translation.pluralkeys', array());
				$this->setState('translation.rootkeys', array());
				$this->setState('translation.regularkeys', array());
				$this->setState('translation.personalisedkeys', array());
				$this->setState('translation.duplicatedkeys', array());
				$this->setState('translation.issuedkeys', array());
				$this->setState('translation.issueddata', array());
				$this->setState('translation.developdata', array());

				$translatedkeys    = $this->getState('translation.translatedkeys');
				$untranslatedkeys  = $this->getState('translation.untranslatedkeys');
				$unchangedkeys     = $this->getState('translation.unchangedkeys');
				$extrakeys         = $this->getState('translation.extrakeys');
				$deletedkeys       = $this->getState('translation.deletedkeys');
				$renamedkeys       = $this->getState('translation.renamedkeys');
				$storeddeletedkeys = $this->getState('translation.storeddeletedkeys');
				$storedrenamedkeys = $this->getState('translation.storedrenamedkeys');
				$pluralkeys        = $this->getState('translation.pluralkeys');
				$rootkeys          = $this->getState('translation.rootkeys');
				$regularkeys       = $this->getState('translation.regularkeys');
				$personalisedkeys  = $this->getState('translation.personalisedkeys');
				$duplicatedkeys    = $this->getState('translation.duplicatedkeys');
				$issuedkeys        = $this->getState('translation.issuedkeys');
				$issueddata        = $this->getState('translation.issueddata');
				$textchangedkeys   = $this->getState('translation.textchangedkeys');
				$revisedchanges    = $this->getState('translation.revisedchanges');
				$developdata       = $this->getState('translation.developdata');

				$this->item = new CMSObject(
									array
										(
										'reference'           => $this->getState('translation.reference'),
										'bom'                 => 'UTF-8',
										'svn'                 => '',
										'version'             => '',
										'description'         => '',
										'creationdate'        => '',
										'author'              => '',
										'maincopyright'       => '',
										'additionalcopyright' => array(),
										'license'             => '',
										'exists'              => File::exists($this->getState('translation.path')),
										'filestate'           => $filestate,
										'reflang'             => $reftag,
										'targetlang'          => $tag,
										'istranslation'       => $istranslation,
										'developdata'         => (array) $developdata,
										'translatedkeys'      => (array) $translatedkeys,
										'untranslatedkeys'    => (array) $untranslatedkeys,
										'unchangedkeys'       => (array) $unchangedkeys,
										'textchangedkeys'     => (array) $textchangedkeys,
										'revisedchanges'      => (array) $revisedchanges,
										'extrakeys'           => (array) $extrakeys,
										'deletedkeys'         => (array) $deletedkeys,
										'renamedkeys'         => (array) $renamedkeys,
										'storeddeletedkeys'   => (array) $storeddeletedkeys,
										'storedrenamedkeys'   => (array) $storedrenamedkeys,
										'pluralkeys'          => (array) $pluralkeys,
										'rootkeys'            => (array) $rootkeys,
										'regularkeys'         => (array) $regularkeys,
										'personalisedkeys'    => (array) $personalisedkeys,
										'duplicatedkeys'      => (array) $duplicatedkeys,
										'issuedkeys'          => (array) $issuedkeys,
										'issueddata'          => (array) $issueddata,
										'unrevised'           => 0,
										'unchecked'           => 0,
										'translatednews'      => 0,
										'unchangednews'       => 0,
										'translated'          => 0,
										'untranslated'        => 0,
										'unchanged'           => 0,
										'extra'               => 0,
										'deleted'             => 0,
										'renamed'             => 0,
										'storeddeleted'       => 0,
										'storedrenamed'       => 0,
										'plural'              => 0,
										'issued'              => 0,
										'total'               => 0,
										'linespath'           => 0,
										'linesrefpath'        => 0,
										'linesdevpath'        => 0,
										'linescustompath'     => 0,
										'complete'            => false,
										'source'              => '',
										'error'               => array()
										)
				);

				if (File::exists($path))
				{
					$devpath    = LocaliseHelper::searchDevpath($gh_client, $refpath);
					$custompath = LocaliseHelper::searchCustompath($gh_client, $refpath);
                    $fname      = basename($path);

					if ($istranslation == 0 && $reftag == 'en-GB')
					{
						if (!empty($devpath))
						{
							if (!empty($custompath))
							{
								$this->item->source = LocaliseHelper::combineReferences($custompath, $devpath);
							}
							else
							{
								$this->item->source = LocaliseHelper::combineReferences($path, $devpath);
							}
						}
						else
						{
							$this->item->source = file_get_contents($path);
						}
					}
					else
					{
						$this->item->source = file_get_contents($path);
					}

					$stream = new Stream;
					$stream->open($path);

					$is_emptyFile = empty(file_get_contents($path));

					if ($is_emptyFile)
					{
						// Setting it as an error
						$this->item->error[] = 0;

						// Sending the error message
						$emptyFile = str_replace(JPATH_ROOT, '', $path);
						Factory::getApplication()->enqueueMessage(Text::sprintf('COM_LOCALISE_ERROR_FILE_EMPTY', $emptyFile), 'error');

						// The "checked_out" value must be set before return the item or also will trigger log warnings.
						// Next code line are a copy of the code to handle it that we can found also at the end of this function.
						if ($this->getState('translation.id'))
						{
							$table = $this->getTable();
							$table->load($this->getState('translation.id'));
							$user = Factory::getUser($table->checked_out);
							$this->item->setProperties($table->getProperties());

							if ($this->item->checked_out == Factory::getUser()->id)
							{
								$this->item->checked_out = 0;
							}

							$this->item->editor = Text::sprintf('COM_LOCALISE_TEXT_TRANSLATION_EDITOR', $user->name, $user->username);
						}

						return $this->item;
					}

					$begin  = $stream->read(4);
					$bom    = strtolower(bin2hex($begin));

					if ($bom == '0000feff')
					{
						$this->item->bom = 'UTF-32 BE';
					}
					else
					{
						if ($bom == 'feff0000')
						{
							$this->item->bom = 'UTF-32 LE';
						}
						else
						{
							if (substr($bom, 0, 4) == 'feff')
							{
								$this->item->bom = 'UTF-16 BE';
							}
							else
							{
								if (substr($bom, 0, 4) == 'fffe')
								{
									$this->item->bom = 'UTF-16 LE';
								}
							}
						}
					}

					$stream->seek(0);
					$continue    = true;
					$lineNumber  = 0;
					$has_headers = false;
					$is_string   = false;

					$isTranslationsView = Factory::getApplication()->input->get('view') == 'translations';

					while (!$stream->eof())
					{
						$line = $stream->gets();
						$lineNumber++;

						// Adding some control to detect if the file have headers.
						if ($lineNumber == '1' && $line[0] != ';')
						{
							$headerline = str_replace('\"', '"_QQ_"', $line);

							if (preg_match('/^(|(\[[^\]]*\])|([A-Z][A-Z0-9_:\*\-\.]*\s*=(\s*(("[^"]*")|(_QQ_)))+))\s*(;.*)?$/', $headerline))
							{
								// The fist line is a string and no headders are prensent.
								$is_string = true;
							}
						}
						elseif ($lineNumber == '1' && $line[0] == ';')
						{
							// The file start with a standar header.
							$has_headers = true;
						}

                        if (!is_string($line))
                        {
                            Factory::getApplication()->enqueueMessage(Text::sprintf('COM_LOCALISE_FILE_LINE_IS_NOT_A_STRING',
                                $fname),
                                'warning');
                            continue;
                        }
						if ($line[0] == '#')
						{
							$this->item->error[] = $lineNumber;
						}
						elseif ($line[0] == ';')
						{
							if (preg_match('/^(;).*(\$Id.*\$)/', $line, $matches))
							{
								$this->item->svn = $matches[2];
							}
							elseif (preg_match('/(;)\s*@?(\pL+):?.*/', $line, $matches))
							{
								switch (strtolower($matches[2]))
								{
									case 'note':
										preg_match('/(;)\s*@?(\pL+):?\s+(.*)/', $line, $matches2);
										$this->item->complete = $this->item->complete || strtolower($matches2[3]) == 'complete';
										break;
									case 'version':
										preg_match('/(;)\s*@?(\pL+):?\s+(.*)/', $line, $matches2);
										$this->item->version = $matches2[3];
										break;
									case 'desc':
									case 'description':
										preg_match('/(;)\s*@?(\pL+):?\s+(.*)/', $line, $matches2);
										$this->item->description = $matches2[3];
										break;
									case 'date':
										preg_match('/(;)\s*@?(\pL+):?\s+(.*)/', $line, $matches2);
										$this->item->creationdate = $matches2[3];
										break;
									case 'author':
										if ($params->get('author') && !$isTranslationsView)
										{
											$this->item->author = $params->get('author');
										}
										else
										{
											preg_match('/(;)\s*@?(\pL+):?\s+(.*)/', $line, $matches2);
											$this->item->author = $matches2[3];
										}
										break;
									case 'copyright':
										preg_match('/(;)\s*@?(\pL+):?\s+(.*)/', $line, $matches2);

										if (empty($this->item->maincopyright))
										{
											if ($params->get('copyright') && !$isTranslationsView)
											{
												$this->item->maincopyright = $params->get('copyright');
											}
											else
											{
												$this->item->maincopyright = $matches2[3];
											}
										}

										if (empty($this->item->additionalcopyright))
										{
											if ($params->get('additionalcopyright') && !$isTranslationsView)
											{
												$this->item->additionalcopyright[] = $params->get('additionalcopyright');
											}
											else
											{
												$this->item->additionalcopyright[] = $matches2[3];
											}
										}
										break;
									case 'license':
										if ($params->get('license') && !$isTranslationsView)
										{
											$this->item->license = $params->get('license');
										}
										else
										{
											preg_match('/(;)\s*@?(\pL+):?\s+(.*)/', $line, $matches2);
											$this->item->license = $matches2[3];
										}
										break;
									case 'package':
										preg_match('/(;)\s*@?(\pL+):?\s+(.*)/', $line, $matches2);
										$this->item->package = $matches2[3];
										break;
									case 'subpackage':
										preg_match('/(;)\s*@?(\pL+):?\s+(.*)/', $line, $matches2);
										$this->item->subpackage = $matches2[3];
										break;
									case 'link':
										break;
									default:
										if (empty($this->item->author))
										{
											if ($params->get('author') && !$isTranslationsView)
											{
												$this->item->author = $params->get('author');
											}
											else
											{
												preg_match('/(;)\s*(.*)/', $line, $matches2);
												$this->item->author = $matches2[2];
											}
										}
										break;
								}
							}
						}
						else
						{
							break;
						}
					}

					if (empty($this->item->author) && $params->get('author') && !$isTranslationsView)
					{
						$this->item->author = $params->get('author');
					}

					if (empty($this->item->license) && $params->get('license') && !$isTranslationsView)
					{
						$this->item->license = $params->get('license');
					}

					if (empty($this->item->maincopyright) && $params->get('copyright') && !$isTranslationsView)
					{
						$this->item->maincopyright = $params->get('copyright');
					}

					if (empty($this->item->additionalcopyright) && $params->get('additionalcopyright') && !$isTranslationsView)
					{
						$this->item->additionalcopyright[] = $params->get('additionalcopyright');
					}

					// Starting from the begin again if no headers are present.
					if ($has_headers == false && $is_string == true)
					{
						$stream->seek(0);
						$lineNumber = 0;
					}

					while (!$stream->eof())
					{
						$line = $stream->gets();
						$lineNumber++;
						$line = str_replace('\"', '"_QQ_"', $line);

						if (!preg_match('/^(|(\[[^\]]*\])|([A-Z][A-Z0-9_:\*\-\.]*\s*=(\s*(("[^"]*")|(_QQ_)))+))\s*(;.*)?$/', $line))
						{
							$this->item->error[] = $lineNumber;
						}
					}

					if ($tag != $reftag)
					{
						if (File::exists($custompath))
						{
							$this->item->linescustompath = count(file($custompath));
						}
					}

					$stream->close();
				}

				$this->item->additionalcopyright = implode("\n", $this->item->additionalcopyright);

				if ($this->getState('translation.layout') != 'raw' && empty($this->item->error))
				{
					$sections = LocaliseHelper::parseSections($this->getState('translation.path'));

						if (!empty($custompath))
						{
							$refsections = LocaliseHelper::parseSections($custompath);
						}
						else
						{
							$refsections = LocaliseHelper::parseSections($this->getState('translation.refpath'));
						}

					$develop_client_path = JPATH_ROOT
								. '/media/com_localise/develop/github/joomla-cms/en-GB/'
								. $gh_client;
					$develop_client_path = Folder::makeSafe($develop_client_path);
					$ref_file            = basename($this->getState('translation.refpath'));
					$develop_file_path   = "$develop_client_path/$ref_file";
					$new_keys            = array();

					if (File::exists($develop_file_path) && $allow_develop == 1 && $reftag == 'en-GB')
					{
						$info                  = array();
						$info['client']        = $gh_client;
						$info['reftag']        = 'en-GB';
						$info['tag']           = 'en-GB';
						$info['filename']      = $ref_file;
						$info['istranslation'] = $istranslation;

						$develop_sections = LocaliseHelper::parseSections($develop_file_path);
						$developdata      = LocaliseHelper::getDevelopchanges($info, $refsections, $develop_sections);
						$developdata['develop_file_path'] = '';

						// Getting the deteted and renamed en-GB keys in development.
						$enGB_deletedkeys = $developdata['deleted_keys'];
						$enGB_renamedkeys = $developdata['renamed_keys'];

						$stored_deleted_keys = LocaliseHelper::getKnownDeletedKeysList();
						$stored_renamed_keys = LocaliseHelper::getKnownRenamedKeysList($client = $info['client']);

						// Handling the extra en-GB keys ('New' ones) in development
						// and the changed strings between the seleted en-GB release and branch.
						if ($developdata['new_keys']['amount'] > 0  || $developdata['text_changes']['amount'] > 0)
						{
							if ($developdata['new_keys']['amount'] > 0)
							{
								$new_keys = $developdata['new_keys']['keys'];
							}

							if ($developdata['text_changes']['amount'] > 0)
							{
								$textchangedkeys = $developdata['text_changes']['keys'];
								$this->item->textchangedkeys = $textchangedkeys;
								$this->setState('translation.textchangedkeys', $textchangedkeys);

								$changesdata['client'] = $gh_client;
								$changesdata['reftag'] = $reftag;

									if ($istranslation == 0)
									{
										$changesdata['tag'] = $reftag;
									}
									else
									{
										$changesdata['tag'] = $tag;
									}

								$changesdata['filename'] = $ref_file;

								foreach ($textchangedkeys as $key_changed)
								{
									$target_text = $developdata['text_changes']['ref_in_dev'][$key_changed];
									$source_text = $developdata['text_changes']['ref'][$key_changed];

									$changesdata['revised']       = '0';
									$changesdata['key']           = $key_changed;
									$changesdata['target_text']   = $target_text;
									$changesdata['source_text']   = $source_text;
									$changesdata['istranslation'] = $istranslation;
									$changesdata['catch_grammar'] = '1';

									$isgrammar = LocaliseHelper::searchRevisedvalue($changesdata);

									if ($istranslation && $isgrammar)
									{
										continue;
									}

									$changesdata['catch_grammar'] = '0';
									$change_status                = LocaliseHelper::searchRevisedvalue($changesdata);
									$revisedchanges[$key_changed] = $change_status;

									if ($change_status == 1)
									{
										$developdata['text_changes']['revised']++;
									}
									else
									{
										$developdata['text_changes']['unrevised']++;
									}
								}

								$this->item->revisedchanges = $revisedchanges;
								$this->setState('translation.revisedchanges', $revisedchanges);
							}

							// When develop changes are present, replace the reference keys
							$refsections = $develop_sections;

							// And store the path for future calls
							$developdata['develop_file_path'] = $develop_file_path;
						}
					}

					$client                 = $this->getState('translation.client');
					$stored_false_positives = false;

					if ($reftag == 'en-GB' && $istranslation == 1)
					{
						$db_data = new CMSObject;
						$db_data->client     = $client;
						$db_data->reflang    = $reftag;
						$db_data->targetlang = $tag;
						$db_data->filename   = $ref_file;

						$stored_false_positives = LocaliseHelper::getFalsePositives($db_data);

						// Getting the plural suffixes for the selected language to translate.
						$language_instance = Language::getInstance($tag);
						$suffixes_data     = LocaliseHelper::getPluralSuffixes($language_instance, $counter = 100);
					}

					if (!empty($refsections['keys']))
					{
						$ref_keys_only = array_keys($refsections['keys']);
						$orphankeys    = array();

						foreach ($refsections['keys'] as $key => $string)
						{
							$this->item->total++;

							if ($reftag == 'en-GB' && $istranslation == 1)
							{
								$key_case = LocaliseHelper::isEngbPlural($key, $ref_keys_only, $orphankeys);

								if (isset($key_case->is_plural) && $key_case->is_plural == true)
								{
									if (!in_array($key_case->root_key, $rootkeys))
									{
										$rootkeys[]   = $key_case->root_key;
										$pluralkeys[] = $key_case->root_key;
										$this->item->plural++;
									}

									if (!in_array($key, $pluralkeys))
									{
										$pluralkeys[] = $key;
										$this->item->plural++;
									}

									if (!in_array($key, $regularkeys))
									{
										$regularkeys[] = $key;
									}
								}
								else if (isset($key_case->is_orphan) && $key_case->is_orphan == true)
								{
										$message = "[$client][$ref_file]Orphan plural case detected for the key: " . $key;
												Factory::getApplication()->enqueueMessage(
													Text::_($message),
													'warning');
								}
							}

							if (!empty($sections['keys']) && array_key_exists($key, $sections['keys']) && $sections['keys'][$key] != '')
							{
								if ($sections['keys'][$key] != $string && $istranslation == 1)
								{
									if (array_key_exists($key, $revisedchanges) && $revisedchanges[$key] == 0)
									{
										$this->item->unrevised++;
										$translatedkeys[] = $key;
									}
									elseif (in_array($key, $new_keys))
									{
										$this->item->translatednews++;
										$translatedkeys[] = $key;

										// Parse issued strings when its a translation with the en-GB language as reference
										if ($reftag == 'en-GB')
										{
											// This is not an changed string to revise or it is a changed string checked as revised.
											if (!isset($revisedchanges[$key]) || (array_key_exists($key, $revisedchanges) && $revisedchanges[$key] == 1))
											{
												$parsed_string = LocaliseHelper::parseStringIssues($string, $sections['keys'][$key]);

												if ($parsed_string->is_issued == true)
												{
													$this->item->issued++;
													$issuedkeys[] = $key;

													if (!in_array($key, $issueddata))
													{
														$issueddata[$key] = $parsed_string;

														$issues_data = new CMSObject;
														$issues_data->client            = $client;
														$issues_data->reflang           = $reftag;
														$issues_data->targetlang        = $tag;
														$issues_data->filename          = $ref_file;
														$issues_data->key               = $key;
														$issues_data->is_false_positive = '0';
														$issues_data->reflang_string    = base64_encode($string);
														$issues_data->targetlang_string = base64_encode($sections['keys'][$key]);

														if (isset($stored_false_positives[$key]))
														{
															$stored_reflang_string    = $stored_false_positives[$key]->reflang_string;
															$stored_targetlang_string = $stored_false_positives[$key]->targetlang_string;

															$current_reflang_string    = $issues_data->reflang_string;
															$current_targetlang_string = $issues_data->targetlang_string;

															if (($stored_reflang_string != $current_reflang_string)
																|| ($stored_targetlang_string != $current_targetlang_string))
															{
																$issues_data->id = $stored_false_positives[$key]->id;

																$update = LocaliseHelper::updateFalsePositive($issues_data);

																$this->item->unchecked++;
															}
															else if ($stored_false_positives[$key]->is_false_positive == '0')
															{
																$this->item->unchecked++;
															}
														}
														else
														{
															$issues_data->id = null;

															$save = LocaliseHelper::saveFalsePositive($issues_data);

															$this->item->unchecked++;
														}
													}
												}
											}
										}
									}
									else
									{
										$this->item->translated++;
										$translatedkeys[] = $key;

										// Parse issued strings when its a translation with the en-GB language as reference
										if ($reftag == 'en-GB')
										{
											// This is not an changed string to revise or it is a changed string checked as revised with issues.
											if (!isset($revisedchanges[$key]) || (array_key_exists($key, $revisedchanges) && $revisedchanges[$key] == 1))
											{
												$parsed_string = LocaliseHelper::parseStringIssues($string, $sections['keys'][$key]);

												if ($parsed_string->is_issued == true)
												{
													$this->item->issued++;
													$issuedkeys[] = $key;

													if (!in_array($key, $issueddata))
													{
														$issueddata[$key] = $parsed_string;

														$issues_data = new CMSObject;
														$issues_data->client            = $client;
														$issues_data->reflang           = $reftag;
														$issues_data->targetlang        = $tag;
														$issues_data->filename          = $ref_file;
														$issues_data->key               = $key;
														$issues_data->is_false_positive = '0';
														$issues_data->reflang_string    = base64_encode($string);
														$issues_data->targetlang_string = base64_encode($sections['keys'][$key]);

														if (isset($stored_false_positives[$key]))
														{
															$stored_reflang_string    = $stored_false_positives[$key]->reflang_string;
															$stored_targetlang_string = $stored_false_positives[$key]->targetlang_string;

															$current_reflang_string    = $issues_data->reflang_string;
															$current_targetlang_string = $issues_data->targetlang_string;

															if (($stored_reflang_string != $current_reflang_string)
																|| ($stored_targetlang_string != $current_targetlang_string))
															{
																$issues_data->id = $stored_false_positives[$key]->id;

																$update = LocaliseHelper::updateFalsePositive($issues_data);

																$this->item->unchecked++;
															}
															else if ($stored_false_positives[$key]->is_false_positive == '0')
															{
																$this->item->unchecked++;
															}
														}
														else
														{
															$issues_data->id = null;

															$save = LocaliseHelper::saveFalsePositive($issues_data);

															$this->item->unchecked++;
														}
													}
												}
											}
										}
									}
								}
								elseif ($istranslation == 0)
								{
									if (array_key_exists($key, $revisedchanges) && $revisedchanges[$key] == 0)
									{
										$this->item->unrevised++;
									}
									elseif (in_array($key, $new_keys))
									{
										$untranslatedkeys[] = $key;
									}

									$this->item->translated++;
								}
								else
								{
									if (in_array($key, $new_keys))
									{
										$this->item->unchangednews++;
									}
									else
									{
										$this->item->unchanged++;
									}

									$unchangedkeys[] = $key;
								}
							}
							elseif (!array_key_exists($key, $sections['keys']))
							{
								$this->item->untranslated++;
								$untranslatedkeys[] = $key;
							}
						}
					}

					$this->item->translatedkeys   = $translatedkeys;
					$this->item->untranslatedkeys = $untranslatedkeys;
					$this->item->unchangedkeys    = $unchangedkeys;

					$this->setState('translation.translatedkeys', $translatedkeys);
					$this->setState('translation.untranslatedkeys', $untranslatedkeys);
					$this->setState('translation.unchangedkeys', $unchangedkeys);

					// From here all those are 'extra' keys only present at the selected translation (also called 'Not in en-GB ref' keys)
					// With this code is posible detect some cases that explain why its are extra, such as:
					// "Deleted" since in the next Joomla release it will not be there.
					// "Renamed" since in the next Joomla release the key has been renamed, but the string to translate remains the same.
					// "Personalised" due are plural cases than the language to translate needs to use.
					// "Extra" since it could not be detected that it is a deleted en-GB key
					// or it is a 'real' extra key than never has been present at en-GB.
					if (!empty($sections['keys']) && $istranslation == 1 && $reftag == 'en-GB')
					{
						// Getting the plural suffixes for the selected language to translate.
						$plural_suffixes   = $suffixes_data->plural_suffixes;
						$cases_0           = $suffixes_data->cases_0;
						$cases_1           = $suffixes_data->cases_1;
						$cases_other       = $suffixes_data->cases_other;
						$cases_more        = $suffixes_data->cases_more;

						foreach ($sections['keys'] as $key => $string)
						{
							if (empty($refsections['keys']) || !array_key_exists($key, $refsections['keys']))
							{
								$key_case = LocaliseHelper::isPlural($plural_suffixes, $key, $rootkeys);

								if (!empty($enGB_renamedkeys) && $enGB_renamedkeys['amount'] > 0 && in_array($key, $enGB_renamedkeys['keys']))
								{
									$renamedkeys[] = $key;
									$this->item->renamed++;
								}
								elseif (in_array($key, $stored_renamed_keys))
								{
									$storedrenamedkeys[] = $key;
									$this->item->storedrenamed++;
								}
								elseif (!empty($enGB_deletedkeys) && $enGB_deletedkeys['amount'] > 0 && in_array($key, $enGB_deletedkeys['keys']))
								{
									$deletedkeys[] = $key;
									$this->item->deleted++;
								}
								elseif (in_array($key, $stored_deleted_keys))
								{
									$storeddeletedkeys[] = $key;
									$this->item->storeddeleted++;
								}
								elseif ($key_case && $key_case->is_plural == true)
								{
									$personalisedkeys[] = $key;
									$pluralkeys[]       = $key;
									$this->item->plural++;
									LocaliseHelper::getDuplicatedPlurals($ref_keys_only, $suffixes_data, $key_case, $duplicatedkeys);
								}
								else
								{
									$extrakeys[] = $key;
									$this->item->extra++;
								}
							}
						}
					}
					else if (!empty($sections['keys']) && $istranslation == 1 && $reftag != 'en-GB')
					{
						foreach ($sections['keys'] as $key => $string)
						{
							if (empty($refsections['keys']) || !array_key_exists($key, $refsections['keys']))
							{
								$extrakeys[] = $key;
								$this->item->extra++;
							}
						}
					}
					else if (!empty($sections['keys']) && $istranslation == 0)
					{
						foreach ($sections['keys'] as $key => $string)
						{
							if (empty($refsections['keys']) || !array_key_exists($key, $refsections['keys']))
							{
								$extrakeys[] = $key;
								$this->item->extra++;
							}
						}
					}

					$this->item->developdata       = $developdata;
					$this->item->extrakeys         = $extrakeys;
					$this->item->deletedkeys       = $deletedkeys;
					$this->item->renamedkeys       = $renamedkeys;
					$this->item->storeddeletedkeys = $storeddeletedkeys;
					$this->item->storedrenamedkeys = $storedrenamedkeys;
					$this->item->pluralkeys        = $pluralkeys;
					$this->item->rootkeys          = $rootkeys;
					$this->item->regularkeys       = $regularkeys;
					$this->item->personalisedkeys  = $personalisedkeys;
					$this->item->duplicatedkeys    = $duplicatedkeys;
					$this->item->issuedkeys        = $issuedkeys;
					$this->item->issueddata        = $issueddata;

					$this->setState('translation.developdata', $developdata);
					$this->setState('translation.extrakeys', $extrakeys);
					$this->setState('translation.deletedkeys', $deletedkeys);
					$this->setState('translation.renamedkeys', $renamedkeys);
					$this->setState('translation.storeddeletedkeys', $storeddeletedkeys);
					$this->setState('translation.storedenamedkeys', $storedrenamedkeys);
					$this->setState('translation.pluralkeys', $pluralkeys);
					$this->setState('translation.rootkeys', $rootkeys);
					$this->setState('translation.regularkeys', $regularkeys);
					$this->setState('translation.personalisedkeys', $personalisedkeys);
					$this->setState('translation.duplicatedkeys', $duplicatedkeys);
					$this->setState('translation.issuedkeys', $issuedkeys);
					$this->setState('translation.issueddata', $issueddata);

					$done = $this->item->translated + $this->item->translatednews + $this->item->unchangednews;

					$this->item->completed = $this->item->total
						? intval(100 * $done / $this->item->total)
						: 100;

					$this->item->complete = $this->item->complete == 1 && $this->item->untranslated == 0 && $this->item->unrevised == 0
						? 1
						: ($this->item->completed == 100
							? 1
							: 0);
				}

				if ($this->getState('translation.id'))
				{
					$table = $this->getTable();
					$table->load($this->getState('translation.id'));
					$user = Factory::getUser($table->checked_out);
					$this->item->setProperties($table->getProperties());

					if ($this->item->checked_out == Factory::getUser()->id)
					{
						$this->item->checked_out = 0;
					}

					$this->item->editor = Text::sprintf('COM_LOCALISE_TEXT_TRANSLATION_EDITOR', $user->name, $user->username);
				}

				if ($caching)
				{
					$cache->store($this->item, $keycache);
				}

				// Count the number of lines in the ini file to check max_input_vars
				if ($tag != $reftag)
				{
					if (File::exists($path))
					{
						$this->item->linespath = count(file($path));
					}

					if (File::exists($refpath))
					{
						$this->item->linesrefpath = count(file($refpath));
					}

					if ($this->getState('translation.layout') != 'raw')
					{
						if (isset($develop_file_path) && File::exists($develop_file_path))
						{
							$this->item->linesdevpath = count(file($develop_file_path));
						}
					}
				}
				else
				{
					if (File::exists($path))
					{
						$this->item->linespath = count(file($path));
					}
				}
			}
		}

		return $this->item;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_localise.translation', 'translation', array('control'   => 'jform', 'load_data' => $loadData));

		$params = ComponentHelper::getParams('com_localise');

		// Set fields readonly if localise global params exist
		if ($params->get('author'))
		{
			$form->setFieldAttribute('author', 'readonly', 'true');
		}

		if ($params->get('copyright'))
		{
			$form->setFieldAttribute('maincopyright', 'readonly', 'true');
		}

		if ($params->get('additionalcopyright'))
		{
			$form->setFieldAttribute('additionalcopyright', 'readonly', 'true');
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  array  The default data is an empty array.
	 */
	protected function loadFormData()
	{
		return $this->getItem();
	}

	/**
	 * Method to get the ftp form.
	 *
	 * @return  mixed  A JForm object on success, false on failure or not ftp
	 */
	public function getFormFtp()
	{
		// Get the form.
		$form = $this->loadForm('com_localise.ftp', 'ftp');

		if (empty($form))
		{
			return false;
		}

		// Check for an error.
		if ($form instanceof Exception)
		{
			$this->setError($form->getMessage());

			return false;
		}

		return $form;
	}

	/**
	 * Method to allow derived classes to preprocess the form.
	 *
	 * @param   JForm   $form   A form object.
	 * @param   mixed   $item   The data expected for the form.
	 * @param   string  $group  The name of the plugin group to import (defaults to "content").
	 *
	 * @throws  Exception if there is an error in the form event.
	 * @return  JForm
	 */
	protected function preprocessForm(Form $form, $item, $group = 'content')
	{
		// Initialize variables.
		$filename = $this->getState('translation.filename');
		$client   = $this->getState('translation.client');
		$tag      = $this->getState('translation.tag');
		$origin   = LocaliseHelper::getOrigin($filename, $client);
		$app      = Factory::getApplication();
		$false    = false;

		$have_develop           = 0;
		$developdata            = array();
		$revisedchanges         = array();
		$deletedkeys            = array();
		$renamedkeys            = array();
		$storeddeletedkeys      = array();
		$storedrenamedkeys      = array();
		$pluralkeys             = array();
		$rootkeys               = array();
		$regularkeys            = array();
		$personalisedkeys       = array();
		$duplicatedkeys         = array();
		$issuedkeys             = array();
		$issueddata             = array();
		$extrakeys              = array();
		$replacements           = array();
		$replacements_keys      = array();
		$stored_deleted_keys    = array();
		$stored_renamed_keys    = array();
		$istranslation          = '';
		$new_in_dev_amount      = 0;
		$deleted_in_dev_amount  = 0;
		$renamed_in_dev_amount  = 0;
		$text_changes_amount    = 0;
		$deleted                = 0;
		$renamed                = 0;
		$storeddeleted          = 0;
		$storedrenamed          = 0;
		$plural                 = 0;
		$extra                  = 0;
		$reflang                = '';
		$targetlang             = '';
		$reflang_rtl            = 0;
		$targetlang_rtl         = 0;
		$replaced_cases_amount  = 0;

		// Compute all known languages
		static $languages = array();
		jimport('joomla.language.language');

		if (!array_key_exists($client, $languages))
		{
			$languages[$client] = LanguageHelper::getKnownLanguages(constant('LOCALISEPATH_' . strtoupper($client)));
		}

		if (is_object($item))
		{
			$form->setFieldAttribute('legend', 'unchanged', $item->unchanged, 'legend');
			$form->setFieldAttribute('legend', 'translated', $item->translated, 'legend');
			$form->setFieldAttribute('legend', 'untranslated', $item->total - $item->translated - $item->unchanged, 'legend');
			$form->setFieldAttribute('legend', 'extra', $item->extra, 'legend');

			$developdata         = $item->developdata;
			$revisedchanges      = $item->revisedchanges;
			$deletedkeys         = $item->deletedkeys;
			$renamedkeys         = $item->renamedkeys;
			$storeddeletedkeys   = $item->storeddeletedkeys;
			$storedrenamedkeys   = $item->storedrenamedkeys;
			$pluralkeys          = $item->pluralkeys;
			$rootkeys            = $item->rootkeys;
			$regularkeys         = $item->regularkeys;
			$personalisedkeys    = $item->personalisedkeys;
			$duplicatedkeys      = $item->duplicatedkeys;
			$issuedkeys          = $item->issuedkeys;
			$issueddata          = $item->issueddata;
			$extrakeys           = $item->extrakeys;
			$reflang             = $item->reflang;
			$targetlang          = $item->targetlang;
			$istranslation       = $item->istranslation;
			$reflang_metadata    = LanguageHelper::getMetadata($reflang);
			$targetlang_metadata = LanguageHelper::getMetadata($targetlang);
			$reflang_rtl         = (int) $reflang_metadata['rtl'];
			$targetlang_rtl      = (int) $targetlang_metadata['rtl'];

			$stored_deleted_keys = LocaliseHelper::getKnownDeletedKeysList();

			if ($stored_deleted_keys == false)
			{
				$stored_deleted_keys = array();
			}

			$stored_renamed_keys = LocaliseHelper::getKnownRenamedKeysList($client, $reflang);

			if ($stored_renamed_keys == false)
			{
				$stored_renamed_keys = array();
			}
		}

		if ($this->getState('translation.layout') != 'raw')
		{
			$this->setState('translation.devpath', '');

			if (!empty($developdata))
			{
				$new_in_dev_amount     = $developdata['new_keys']['amount'];
				$text_changes_amount   = $developdata['text_changes']['amount'];
				$deleted_in_dev_amount = $developdata['deleted_keys']['amount'];
				$renamed_in_dev_amount = $developdata['renamed_keys']['amount'];
				$replacements          = $developdata['renamed_keys']['replacements'];

				if ($renamed_in_dev_amount > 0)
				{
					$replacements_keys = array_keys($replacements);
				}

				$refpath               = $this->getState('translation.refpath');

				$custompath            = LocaliseHelper::searchCustompath($client, $refpath);

				if ($istranslation == '0')
				{
					if (!empty($custompath))
					{
						$refpath     = $custompath;
						$path        = $refpath;
						$refsections = LocaliseHelper::parseSections($refpath);
						$sections    = $refsections;
					}
					else
					{
						$refpath     = $this->getState('translation.refpath');
						$path        = $refpath;
						$refsections = LocaliseHelper::parseSections($refpath);
						$sections    = $refsections;
					}
				}
				else
				{
					if (!empty($custompath))
					{
						$refpath     = $custompath;
						$path        = $this->getState('translation.path');
						$refsections = LocaliseHelper::parseSections($refpath);
						$sections    = LocaliseHelper::parseSections($path);
					}
					else
					{
						$refpath     = $this->getState('translation.refpath');
						$path        = $this->getState('translation.path');
						$refsections = LocaliseHelper::parseSections($refpath);
						$sections    = LocaliseHelper::parseSections($path);
					}
				}

				if ($new_in_dev_amount > 0  || $text_changes_amount > 0)
				{
					$have_develop      = 1;
					$develop_file_path = $developdata['develop_file_path'];
					$develop_sections  = LocaliseHelper::parseSections($develop_file_path);
					$oldref            = $refsections;
					$refsections       = $develop_sections;
					$refpath           = $develop_file_path;

					$this->setState('translation.devpath', $develop_file_path);
				}
			}
			else
			{
				$path        = $this->getState('translation.path');
				$refpath     = $this->getState('translation.refpath');
				$sections    = LocaliseHelper::parseSections($path);
				$refsections = LocaliseHelper::parseSections($refpath);
			}


			$ref_keys_only  = array();
			$lang_keys_only = array();

			$stored_renamed_data   = LocaliseHelper::getStoredRenamedKeys($client, $reflang);
			$stored_renamed_amount = 0;

			if ($stored_renamed_data == false)
			{
				$stored_renamed_data = array();
			}

			if (!empty($refsections['keys']))
			{
				$ref_keys_only = array_keys($refsections['keys']);
			}

			if (!empty($sections['keys']))
			{
				$lang_keys_only = array_keys($sections['keys']);
			}

			$extra_keys_in_translation = array_diff($lang_keys_only, $ref_keys_only);

			$addform = new \SimpleXMLElement('<form />');

			$group = $addform->addChild('fields');
			$group->addAttribute('name', 'strings');

			$fieldset = $group->addChild('fieldset');
			$fieldset->addAttribute('name', 'JDEFAULT');
			$fieldset->addAttribute('label', 'JDEFAULT');

			if (File::exists($refpath))
			{
				$stream = new Stream;
				$stream->open($refpath);
				$stream->seek(0);

				// $header is used to avoid display at regular edition mode the file headers.
				// It starts as true due normaly all the core files to translate have the en-GB credits presents at the begin of the file to translate.
				$header     = true;
				$lineNumber = 0;
                $fname      = basename($refpath);

				while (!$stream->eof())
				{
					$line = $stream->gets();
					$commented = '';
					$lineNumber++;

					// Due the 3pd non core files to translate maybe have no en-GB headers to catch, this vars will help to detect it.
					$is_string  = false;
					$has_headers = false;

					// Adding some control to detect if the file have headers.
					if ($lineNumber == '1' && $line[0] != ';')
					{
						$headerline = str_replace('\"', '"_QQ_"', $line);

						if (preg_match('/^(|(\[[^\]]*\])|([A-Z][A-Z0-9_:\*\-\.]*\s*=(\s*(("[^"]*")|(_QQ_)))+))\s*(;.*)?$/', $headerline))
						{
							// The first line is a string and no headers are prensent.
							$is_string = true;
							$header    = false;
						}
					}
					elseif ($lineNumber == '1' && $line[0] == ';')
					{
						// The file start with a standar header.
						$has_headers = true;
					}

					// Blank lines
					if (preg_match('/^\s*$/', $line))
					{
						// The first black line, if present, means it is the headers end and that one and next ones will be handled as 'type spacer'.
						$header = false;
						$field  = $fieldset->addChild('field');
						$field->addAttribute('label', '');
						$field->addAttribute('type', 'spacer');
						$field->addAttribute('class', 'text');

						continue;
					}
					// Section lines
					elseif (preg_match('/^\[([^\]]*)\]\s*$/', $line, $matches))
					{
						$header = false;
						$form->load($addform, false);
						$section = $matches[1];
						$addform = new \SimpleXMLElement('<form />');
						$group   = $addform->addChild('fields');
						$group->addAttribute('name', 'strings');
						$fieldset = $group->addChild('fieldset');
						$fieldset->addAttribute('name', $section);
						$fieldset->addAttribute('label', $section);

						continue;
					}
					// Comment lines
					elseif (!$header && preg_match('/^;(.*)$/', $line, $matches))
					{
						// When $header is false means than this one is a comment line present within the en-GB reference to display.
						$key   = $matches[1];
						$field = $fieldset->addChild('field');
						$field->addAttribute('label', htmlspecialchars($line));
						$field->addAttribute('translateLabel', 'false');
						$field->addAttribute('type', 'spacer');
						$field->addAttribute('class', 'fs-5 edition-comment normal-text');

						continue;
					}
					// Key lines
					elseif (preg_match('/^([A-Z][A-Z0-9_:\*\-\.]*)\s*=/', $line, $matches))
					{
						$header     = false;
						$key        = $matches[1];
						$field      = $fieldset->addChild('field');

						preg_match('/("\s;\s.*)$/', $line, $contextcomment);

						if (!empty($contextcomment[1]))
						{
							$commented = ltrim($contextcomment[1], '"');
						}

						$field->addAttribute('commented', $commented);
						$field->addAttribute('translateLabel', 'false');

						if ($have_develop == '1' && $istranslation == '0' && array_key_exists($key, $oldref['keys']))
						{
							$string = $oldref['keys'][$key];
							$translated = isset($sections['keys'][$key]);
							$modified   = $translated && $sections['keys'][$key] != $oldref['keys'][$key];
						}
						else
						{
							// The string in en-GB reference
							$string = $refsections['keys'][$key];

							// The current translation string
							$translation_string = isset($sections['keys'][$key]) ? $sections['keys'][$key] : $string;

							// Searching for renamed keys in translation
							if (in_array($key, $replacements_keys))
							{
								$old_key = $replacements[$key];

								// The old translation string, if present at the translation as 'Renamed key' to delete.
								$old_translation_string = isset($sections['keys'][$old_key]) ? $sections['keys'][$old_key] : '';

								// Check if the en-GB string is equal to the translated string to catch untranslated cases only.
								if (!empty($old_translation_string) && $translation_string == $string)
								{
									// Check if the old string in translation was translated
									if ($old_translation_string != $string)
									{
										$sections['keys'][$key] = $old_translation_string;
										$replaced_cases_amount++;

										// TODO Check if the old translated string is an issued string case to skip the replacement.
									}
								}
							}
							else if (isset($stored_renamed_data[$key]))
							{
								$old_key = $stored_renamed_data[$key]->key;

								// The old translation string, if present at the translation as 'Renamed key' to delete.
								$old_translation_string = isset($sections['keys'][$old_key]) ? $sections['keys'][$old_key] : '';

								// Check if the en-GB string is equal to the translated string to catch untranslated cases only.
								if (!empty($old_translation_string) && $translation_string == $string)
								{
									// Check if the old string in translation was translated
									if ($old_translation_string != $string)
									{
										$sections['keys'][$key] = $old_translation_string;
										$stored_renamed_amount++;
									}
								}
							}

							$translated = isset($sections['keys'][$key]);
							$modified   = $translated && $sections['keys'][$key] != $refsections['keys'][$key];
						}

						$status     = $modified
							? 'translated'
							: ($translated
								? 'unchanged'
								: 'untranslated');
						$default    = $translated
							? $sections['keys'][$key]
							: '';

						$field->addAttribute('istranslation', $istranslation);
						$field->addAttribute('istextchange', 0);
						$field->addAttribute('isextraindev', 0);

						if (isset($issueddata[$key]))
						{
							$field->addAttribute('isissued', 1);
							$field->addAttribute('engb_string', (string) $issueddata[$key]->engb_string);
							$field->addAttribute('ttms_string', (string) $issueddata[$key]->ttms_string);
							$field->addAttribute('issue_details', (string) $issueddata[$key]->issue_details);
						}
						else
						{
							$field->addAttribute('isissued', 0);
							$field->addAttribute('engb_string', '');
							$field->addAttribute('ttms_string', '');
							$field->addAttribute('issue_details', '');
						}

						if (in_array($key, $rootkeys))
						{
							$field->addAttribute('isroot', 1);
						}
						else
						{
							$field->addAttribute('isroot', 0);
						}

						if (in_array($key, $pluralkeys))
						{
							$field->addAttribute('isplural', 1);
						}
						else
						{
							$field->addAttribute('isplural', 0);
						}

						if (in_array($key, $duplicatedkeys))
						{
							$field->addAttribute('isduplicated', 1);
						}
						else
						{
							$field->addAttribute('isduplicated', 0);
						}

						if ($have_develop == '1' && in_array($key, $developdata['text_changes']['keys']))
						{
							$change     = $developdata['text_changes']['diff'][$key];
							$sourcetext = $developdata['text_changes']['ref'][$key];
							$targettext = $developdata['text_changes']['ref_in_dev'][$key];

							$label = '<p class="key-case"><strong>'
								. $key
								. '</strong></p><p class="text_changes normal-text">'
								. $change
								. '</p>';

							$field->attributes()->istextchange = 1;
							$field->addAttribute('changestatus', $revisedchanges[$key]);
							$field->addAttribute('sourcetext', $sourcetext);
							$field->addAttribute('targettext', $targettext);
						}
						elseif ($have_develop == '1' && in_array($key, $developdata['new_keys']['keys']))
						{
							$label = '<p class="key-case"><strong>'
								. $key
								. '</strong></p><p class="normal-text">'
								. htmlspecialchars($string, ENT_COMPAT, 'UTF-8')
								. '</p>';

							$field->attributes()->isextraindev = 1;
						}
						else
						{
							$label = '<p class="key-case"><strong>'
								. $key
								. '</strong></p><p class="normal-text">'
								. htmlspecialchars($string, ENT_COMPAT, 'UTF-8')
								. '</p>';

							$field->attributes()->isextraindev = 0;
						}

						$label = '<div class="word-break-width-100">'
							. $label
							. '</div>';

						$field->addAttribute('status', $status);
						$field->addAttribute('description', $string);

						if ($default)
						{
							$field->addAttribute('default', $default);
						}
						else
						{
							$field->addAttribute('default', $string);
						}

						$field->addAttribute('label', $label);
						$field->addAttribute('name', $key);
						$field->addAttribute('reflang', $reflang);
						$field->addAttribute('targetlang', $targetlang);
						$field->addAttribute('filename', basename($this->getState('translation.path')));
						$field->addAttribute('client', $client);
						$field->addAttribute('reflang_is_rtl', $reflang_rtl);
						$field->addAttribute('targetlang_is_rtl', $targetlang_rtl);
						$field->addAttribute('type', 'key');
						$field->addAttribute('filter', 'raw');

						continue;
					}
					elseif (!preg_match('/^(|(\[[^\]]*\])|([A-Z][A-Z0-9_:\*\-\.]*\s*=(\s*(("[^"]*")|(_QQ_)))+))\s*(;.*)?$/', $line))
					{
                        if (is_numeric($lineNumber))
                        {
						    $item->error[] = $lineNumber;
                        }
                        else
                        {
                            Factory::getApplication()->enqueueMessage(Text::sprintf('COM_LOCALISE_FILE_LINE_NOT_ENUMERABLE',
                                $fname),
                                'warning');
                        }
					}
				}

				$stream->close();

				$combined_renamed_keys = array();

				if (!empty($renamedkeys) && !empty($storedrenamedkeys))
				{
					$combined_renamed_keys = array_merge($renamedkeys, $storedrenamedkeys);
				}
				else if (!empty($renamedkeys))
				{
					$combined_renamed_keys = $renamedkeys;
				}
				else if (!empty($storedrenamedkeys))
				{
					$combined_renamed_keys = $storedrenamedkeys;
				}

				$combined_deleted_keys = array();

				if (!empty($deletedkeys) && !empty($storeddeletedkeys))
				{
					$combined_deleted_keys = array_merge($deletedkeys, $storeddeletedkeys);
				}
				else if (!empty($deletedkeys))
				{
					$combined_deleted_keys = $deletedkeys;
				}
				else if (!empty($storeddeletedkeys))
				{
					$combined_deleted_keys = $storeddeletedkeys;
				}

				if ($istranslation == 1)
				{
					$cases = array(
						'renamed' => $combined_renamed_keys,
						'deleted' => $combined_deleted_keys,
						'personalised' => $personalisedkeys,
						'extra' => $extrakeys
					);

					if (!empty($extra_keys_in_translation))
					{
						foreach ($cases as $case => $case_data)
						{
							if (empty($case_data))
							{
								continue;
							}

							$newstrings = false;

							foreach ($extra_keys_in_translation as $extra_key_in_translation)
							{
								if(in_array($extra_key_in_translation, $case_data))
								{
									if (!in_array($extra_key_in_translation, $ref_keys_only))
									{
										$string = $sections['keys'][$extra_key_in_translation];

										if (!$newstrings)
										{
											$newstrings = true;
											$form->load($addform, false);
											$section = 'COM_LOCALISE_TEXT_TRANSLATION_' . strtoupper($case);
											$addform = new \SimpleXMLElement('<form />');
											$group   = $addform->addChild('fields');
											$group->addAttribute('name', 'strings');
											$fieldset = $group->addChild('fieldset');
											$fieldset->addAttribute('name', $section);
											$fieldset->addAttribute('label', $section);
										}

										$field   = $fieldset->addChild('field');
										$status  = $case;
										$default = $string;

										$label = '<p class="key-case"><strong>'
										. $extra_key_in_translation
										. '</strong></p>';
										$label = '<div class="word-break-width-100">'
										. $label
										. '</div>';

										$field->addAttribute('status', $status);
										$field->addAttribute('description', $string);
										$field->addAttribute('istranslation', $istranslation);

										if ($default)
										{
											$field->addAttribute('default', $default);
										}
										else
										{
											$field->addAttribute('default', $string);
										}

										$field->addAttribute('label', $label);
										$field->addAttribute('name', $extra_key_in_translation);
										$field->addAttribute('reflang', $reflang);
										$field->addAttribute('targetlang', $targetlang);
										$field->addAttribute('reflang_is_rtl', $reflang_rtl);
										$field->addAttribute('targetlang_is_rtl', $targetlang_rtl);
										$field->addAttribute('type', 'key');
										$field->addAttribute('filter', 'raw');

										if ($case == 'personalised')
										{
											$field->addAttribute('ispersonalised', 1);

											if (in_array($extra_key_in_translation, $duplicatedkeys))
											{
												$field->addAttribute('isduplicated', 1);
											}
											else
											{
												$field->addAttribute('isduplicated', 0);
											}
										}
										else
										{
											$field->addAttribute('ispersonalised', 0);
											$field->addAttribute('isduplicated', 0);
										}

										if ($case == 'deleted')
										{
											if (!in_array($extra_key_in_translation, $deletedkeys))
											{
												$field->addAttribute('isdeletedstorage', 1);
											}
											else
											{
												$field->addAttribute('isdeletedstorage', 0);
											}
										}
										else
										{
											$field->addAttribute('isdeletedstorage', 0);
										}

										if ($case == 'renamed')
										{
											if (!in_array($extra_key_in_translation, $renamedkeys))
											{
												$field->addAttribute('isrenamedstorage', 1);
											}
											else
											{
												$field->addAttribute('isrenamedstorage', 0);
											}
										}
										else
										{
											$field->addAttribute('isrenamedstorage', 0);
										}
									}
								}
							}
						}
					}
				}
				else
				{
					$newstrings = false;

					if (!empty($sections['keys']))
					{
						foreach ($sections['keys'] as $key => $string)
						{
							if (!isset($refsections['keys'][$key]))
							{
								if (!$newstrings)
								{
									$newstrings = true;
									$form->load($addform, false);
									$section = 'COM_LOCALISE_TEXT_TRANSLATION_EXTRA';
									$addform = new \SimpleXMLElement('<form />');
									$group   = $addform->addChild('fields');
									$group->addAttribute('name', 'strings');
									$fieldset = $group->addChild('fieldset');
									$fieldset->addAttribute('name', $section);
									$fieldset->addAttribute('label', $section);
								}

								$field   = $fieldset->addChild('field');
								$status  = 'extra';
								$default = $string;

								$label = '<p class="key-case"><strong>'
									. $key
									. '</strong></p>';
								$label = '<div class="word-break-width-100">'
									. $label
									. '</div>';

								$field->addAttribute('status', $status);
								$field->addAttribute('description', $string);
								$field->addAttribute('istranslation', $istranslation);

								if ($default)
								{
									$field->addAttribute('default', $default);
								}
								else
								{
									$field->addAttribute('default', $string);
								}

								$field->addAttribute('label', $label);
								$field->addAttribute('name', $key);
								$field->addAttribute('reflang', $reflang);
								$field->addAttribute('targetlang', $targetlang);
								$field->addAttribute('reflang_is_rtl', $reflang_rtl);
								$field->addAttribute('targetlang_is_rtl', $targetlang_rtl);
								$field->addAttribute('type', 'key');
								$field->addAttribute('filter', 'raw');
							}
						}
					}
				}
			}
			else
			{
				// Extra file case.
				$newstrings = true;

				if (!empty($sections['keys']))
				{
					foreach ($sections['keys'] as $key => $string)
					{
						if (!isset($refsections['keys'][$key]))
						{
							if (!$newstrings)
							{
								$newstrings = true;
								$form->load($addform, false);
								$section = 'COM_LOCALISE_TEXT_TRANSLATION_EXTRAFILE';
								$addform = new \SimpleXMLElement('<form />');
								$group   = $addform->addChild('fields');
								$group->addAttribute('name', 'strings');
								$fieldset = $group->addChild('fieldset');
								$fieldset->addAttribute('name', $section);
								$fieldset->addAttribute('label', $section);
							}

							$field   = $fieldset->addChild('field');
							$status  = 'extrafile';
							$default = $string;

							$label = '<p class="key-case"><strong>'
								. $key
								. '</strong></p>';
							$label = '<div class="word-break-width-100">'
								. $label
								. '</div>';

							$field->addAttribute('status', $status);
							$field->addAttribute('description', $string);
							$field->addAttribute('istranslation', $istranslation);

							if ($default)
							{
								$field->addAttribute('default', $default);
							}
							else
							{
								$field->addAttribute('default', $string);
							}

							$field->addAttribute('label', $label);
							$field->addAttribute('name', $key);
							$field->addAttribute('reflang', $reflang);
							$field->addAttribute('targetlang', $targetlang);
							$field->addAttribute('reflang_is_rtl', $reflang_rtl);
							$field->addAttribute('targetlang_is_rtl', $targetlang_rtl);
							$field->addAttribute('type', 'key');
							$field->addAttribute('filter', 'raw');
						}
					}
				}
			}

			$form->load($addform, false);

			if ($replaced_cases_amount > 0)
			{
				Factory::getApplication()->enqueueMessage(Text::sprintf('COM_LOCALISE_TRANSLATION_REPLACED_CASES_AMOUNT',
					$renamed_in_dev_amount,
					$replaced_cases_amount),
					'notice');
			}

			if ($stored_renamed_amount > 0)
			{
				Factory::getApplication()->enqueueMessage(Text::sprintf('COM_LOCALISE_TRANSLATION_STORED_REPLACED_CASES_AMOUNT',
					$stored_renamed_amount),
					'notice');
			}
		}

		// Check the session for previously entered form data.
		$data = $app->getUserState('com_localise.edit.translation.data', array());

		// Bind the form data if present.
		if (!empty($data))
		{
			$form->bind($data);
		}

		if ($origin != '_thirdparty' && $origin != '_override')
		{
			$packages = LocaliseHelper::getPackages();
			$package  = $packages[$origin];

			if (!empty($package->author))
			{
				$form->setValue('author', $package->author);
				$form->setFieldAttribute('author', 'readonly', 'true');
			}

			if (!empty($package->copyright))
			{
				$form->setValue('maincopyright', $package->copyright);
				$form->setFieldAttribute('maincopyright', 'readonly', 'true');
			}

			if (!empty($package->license))
			{
				$form->setValue('license', $package->license);
				$form->setFieldAttribute('license', 'readonly', 'true');
			}
		}

		if ($form->getValue('description') == '' && array_key_exists($tag, $languages[$client]))
		{
			$form->setValue('description', $filename . ' ' . $languages[$client][$tag]['name']);
		}

		return $form;
	}

	/**
	 * Save a file
	 *
	 * @param   array  $data  Array that represents a file
	 *
	 * @return bool
	 */
	public function saveFile($data)
	{
		$client        = $this->getState('translation.client');
		$tag           = $this->getState('translation.tag');
		$reftag        = $this->getState('translation.reference');
		$path          = $this->getState('translation.path');
		$refpath       = $this->getState('translation.refpath');
		$devpath       = LocaliseHelper::searchDevpath($client, $refpath);
		$custompath    = LocaliseHelper::searchCustompath($client, $refpath);
		$exists        = File::exists($path);
		$refexists     = File::exists($refpath);
		$istranslation = $tag != $reftag;
		$notinref      = array();

		if (isset($data['notinref']))
		{
			$notinref  = (array) $data['notinref'];
		}

		if ($refexists && !empty($devpath))
		{
			if ($reftag == 'en-GB' && $tag == 'en-GB' && !empty($custompath))
			{
				$params             = ComponentHelper::getParams('com_localise');
				$customisedref      = $params->get('customisedref', '0');
				$custom_short_path  = '../media/com_localise/customisedref/github/'
							. $client
							. '/'
							. $customisedref;

				// The saved file is not using the core language folders.
				$path   = $custompath;
				$exists = File::exists($path);

				$ref_file         = basename($refpath);
				$custom_file_path = Folder::makeSafe("$custompath/$ref_file");
			}
			elseif ($reftag == 'en-GB' &&  $tag != 'en-GB')
			{
				// It is a translation with the file in develop as reference.
				$refpath = $devpath;
			}
		}

		// Set FTP credentials, if given.
		ClientHelper::setCredentialsFromRequest('ftp');
		$ftp = ClientHelper::getCredentials('ftp');

		// Try to make the file writeable.
		if ($exists && !$ftp['enabled'] && Path::isOwner($path) && !Path::setPermissions($path, '0644'))
		{
			$this->setError(Text::sprintf('COM_LOCALISE_ERROR_TRANSLATION_WRITABLE', $path));

			return false;
		}

		if (array_key_exists('source', $data))
		{
			$contents = $data['source'];
		}
		else
		{
			$data['description']  = str_replace(array("\r\n", "\n", "\r"), " ", $data['description']);
			$additionalcopyrights = trim($data['additionalcopyright']);

			if (empty($additionalcopyrights))
			{
				$additionalcopyrights = array();
			}
			else
			{
				$additionalcopyrights = explode("\n", $additionalcopyrights);
			}

			$contents2 = '';

			if (!empty($data['svn']))
			{
				$contents2 .= "; " . $data['svn'] . "\n;\n";
			}

			if (!empty($data['package']))
			{
				$contents2 .= "; @package     " . $data['package'] . "\n";
			}

			if (!empty($data['subpackage']))
			{
				$contents2 .= "; @subpackage  " . $data['subpackage'] . "\n";
			}

			if (!empty($data['description']) && $data['description'] != '[Description] [Name of language]([Country code])')
			{
				$contents2 .= "; @description " . $data['description'] . "\n";
			}

			if (!empty($data['version']))
			{
				$contents2 .= "; @version     " . $data['version'] . "\n";
			}

			if (!empty($data['creationdate']))
			{
				$contents2 .= "; @date        " . $data['creationdate'] . "\n";
			}

			if (!empty($data['author']))
			{
				$contents2 .= "; @author      " . $data['author'] . "\n";
			}

			if (!empty($data['maincopyright']))
			{
				$contents2 .= "; @copyright   " . $data['maincopyright'] . "\n";
			}

			foreach ($additionalcopyrights as $copyright)
			{
				$contents2 .= "; @copyright   " . $copyright . "\n";
			}

			if (!empty($data['license']))
			{
				$contents2 .= "; @license     " . $data['license'] . "\n";
			}

			if (array_key_exists('complete', $data) && ($data['complete'] == '1'))
			{
				$this->setState('translation.complete', 1);
				$contents2 .= "; @note        Complete\n";
			}
			else
			{
				$this->setState('translation.complete', 0);
			}

			$contents2 .= "; @note        Client " . ucfirst($client) . "\n";
			$contents2 .= "; @note        All ini files need to be saved as UTF-8\n\n";

			$contents = array();
			$stream   = new Stream;
			$stream->seek(0);

			if ($exists)
			{
				$stream->open($path);

				while (!$stream->eof())
				{
					$line = $stream->gets();

					// Comment lines
					if (preg_match('/^(;.*)$/', $line, $matches))
					{
						// $contents[] = $matches[1]."\n";
					}
					else
					{
						break;
					}
				}

				if ($refexists)
				{
					$stream->close();
					$stream->open($refpath);
					$stream->seek(0);

					while (!$stream->eof())
					{
						$line = $stream->gets();

						// Comment lines
						if (!preg_match('/^(;.*)$/', $line, $matches))
						{
							break;
						}
					}
				}
			}
			else
			{
				$stream->open($refpath);
				$stream->seek(0);

				while (!$stream->eof())
				{
					$line = $stream->gets();

					// Comment lines
					if (preg_match('/^(;.*)$/', $line, $matches))
					{
						$contents[] = $matches[1] . "\n";
					}
					else
					{
						break;
					}
				}
			}

			$strings = $data['strings'];

			$stream->seek(0);

			while (!$stream->eof())
			{
			// Mounting the language file in this way will help to avoid save files with errors at the content.

				$line = $stream->gets();

				// Blank lines
				if (preg_match('/^\s*$/', $line))
				{
					$contents[] = "\n";
				}
				// Comments lines
				elseif (preg_match('/^(;.*)$/', $line, $matches))
				{
					$contents[] = $matches[1] . "\n";
				}
				// Section lines
				elseif (preg_match('/^\[([^\]]*)\]\s*$/', $line, $matches))
				{
					$contents[] = "[" . $matches[1] . "]\n";
				}
				// Key lines
				elseif (preg_match('/^([A-Z][A-Z0-9_:\*\-\.]*)\s*=/', $line, $matches))
				{
					$key       = $matches[1];
					$commented = '';

					preg_match('/("\s;\s.*)$/', $line, $contextcomment);

					if (!empty($contextcomment[1]))
					{
						$commented = ltrim($contextcomment[1], '"');
					}

					if (isset($strings[$key]))
					{
						$contents[] = $key . '="' . str_replace('"', '\"', $strings[$key]) . "\"" . $commented . "\n";
						unset($strings[$key]);
					}
				}
				// Content with EOL
				elseif (preg_split("/\\r\\n|\\r|\\n/", $line))
				{
					$application = Factory::getApplication();
					$application->enqueueMessage(Text::sprintf('COM_LOCALISE_WRONG_LINE_CONTENT', htmlspecialchars($line)), 'warning');
				}
				// Wrong lines
				else
				{
					$application = Factory::getApplication();
					$application->enqueueMessage(Text::sprintf('COM_LOCALISE_WRONG_LINE_CONTENT', htmlspecialchars($line)), 'warning');
				}
			}

			$catched = false;
			$counted = 0;
			// Handle here the not in ref cases before add the "Not in reference" comment.
			if (!empty($strings) && !empty($notinref[0]) && $istranslation)
			{
				foreach ($strings as $key => $string)
				{
					if (in_array($key, $notinref))
					{
						$catched = true;
						$counted++;

						unset($strings[$key]);
					}
				}

				if ($catched)
				{
					Factory::getApplication()->enqueueMessage(
						Text::plural('COM_LOCALISE_NOTICE_TRANSLATION_DELETE_NOTINREF', $counted),
						'notice');

					if (!empty($strings))
					{
						Factory::getApplication()->enqueueMessage(
						Text::plural('COM_LOCALISE_NOTICE_TRANSLATION_DELETE_NOTINREF_OMITTED', count($strings)),
							'notice');
					}
					else
					{
						Factory::getApplication()->enqueueMessage(
							Text::_('COM_LOCALISE_NOTICE_TRANSLATION_DELETE_NOTINREF_ALL'),
							'notice');
					}
				}
			}
			elseif (!empty($strings) && $istranslation)
			{
				Factory::getApplication()->enqueueMessage(
					Text::plural('COM_LOCALISE_NOTICE_TRANSLATION_DELETE_NOTINREF_OMITTED', count($strings)),
					'notice');
			}

			if (!empty($strings))
			{
				$contents[] = "\n[" . Text::_('COM_LOCALISE_TEXT_TRANSLATION_NOTINREFERENCE') . "]\n\n";

				foreach ($strings as $key => $string)
				{
					$contents[] = $key . '="' . str_replace('"', '\"', $string) . "\"\n";
				}
			}

			$stream->close();
			$contents = implode($contents);
			$contents = $contents2 . $contents;
		}

		// Make sure EOL is Unix
		$contents = str_replace(array("\r\n", "\n", "\r"), "\n", $contents);

		$return = File::write($path, $contents);

		// Try to make the template file unwriteable.

		// Get the parameters
		$coparams = ComponentHelper::getParams('com_localise');

		// Get the file save permission
		$fsper = $coparams->get('filesavepermission', '0444');

		if (!$ftp['enabled'] && Path::isOwner($path) && !Path::setPermissions($path, $fsper))
		{
			$this->setError(Text::sprintf('COM_LOCALISE_ERROR_TRANSLATION_UNWRITABLE', $path));

			return false;
		}
		else
		{
			if (!$return)
			{
				$this->setError(Text::sprintf('COM_LOCALISE_ERROR_TRANSLATION_FILESAVE', $path));

				return false;
			}
			elseif ($reftag == 'en-GB' && $tag == 'en-GB' && !empty($custompath))
			{
				$params             = ComponentHelper::getParams('com_localise');
				$customisedref      = $params->get('customisedref', '0');
				$custom_short_path  = '../media/com_localise/customisedref/github/'
							. $client
							. '/'
							. $customisedref;

				Factory::getApplication()->enqueueMessage(
					Text::_('COM_LOCALISE_NOTICE_CUSTOM_EN_GB_FILE_SAVED') . $custom_short_path,
					'notice');
			}
		}

		// Remove the cache
		$conf    = Factory::getConfig();
		$caching = $conf->get('caching') >= 1;

		if ($caching)
		{
			$keycache = $this->getState('translation.client') . '.'
				. $this->getState('translation.tag') . '.'
				. $this->getState('translation.filename') . '.' . 'translation';
			$cache    = Factory::getCache('com_localise', '');
			$cache->remove($keycache);
		}
	}

	/**
	 * Saves a translation
	 *
	 * @param   array  $data  translation to be saved
	 *
	 * @return bool
	 */
	public function save($data)
	{
		// Fix DOT saving issue
		$input = Factory::getApplication()->input;

		$formData = $input->get('jform', array(), 'ARRAY');

		if (!empty($formData['strings']))
		{
			$client     = $this->getState('translation.client');
			$reflang    = $this->getState('translation.reference');
			$targetlang = $this->getState('translation.tag');
			$filename   = basename($this->getState('translation.refpath'));

			$data['strings']       = $formData['strings'];
			$data['falsepositive'] = (array) $formData['falsepositive'];

			if (!empty($formData['text_changes']))
			{
				$data['text_changes']        = $formData['text_changes'];
				$data['source_text_changes'] = $formData['source_text_changes'];
				$data['target_text_changes'] = $formData['target_text_changes'];

				$changes_data = array();
				$changes_data['client']   = $client;
				$changes_data['reftag']   = $reflang;
				$changes_data['tag']      = $targetlang;
				$changes_data['filename'] = $filename;

				foreach ($data['text_changes'] as $key => $revised)
				{
					$changes_data['revised'] = "0";

					if ($revised == '1' || $revised == 'true')
					{
						$changes_data['revised'] = "1";
					}

					$changes_data['key'] = $key;
					$changes_data['target_text'] = $data['target_text_changes'][$key];
					$changes_data['source_text'] = $data['source_text_changes'][$key];

					LocaliseHelper::updateRevisedvalue($changes_data);
				}
			}

			if (!empty($data['falsepositive']) && $reflang == 'en-GB' && $reflang != $targetlang)
			{
				$db_data = new CMSObject;
				$db_data->client     = $client;
				$db_data->reflang    = $reflang;
				$db_data->targetlang = $targetlang;
				$db_data->filename   = $filename;

				$stored_false_positives = LocaliseHelper::getFalsePositives($db_data);

				foreach ($stored_false_positives as $stored)
				{
					$key = $stored->key;
					$issues_data = new CMSObject;
					$issues_data->id         = $stored->id;
					$issues_data->client     = $client;
					$issues_data->reflang    = $reflang;
					$issues_data->targetlang = $targetlang;
					$issues_data->filename   = $filename;

					if (in_array($stored->key, $data['falsepositive']))
					{
						$issues_data->is_false_positive = '1';
					}
					else
					{
						$issues_data->is_false_positive = '0';
					}

					$issues_data->key    = $key;
					$issues_data->targetlang_string = base64_encode($data['strings'][$key]);

					$update = LocaliseHelper::updateFalsePositive($issues_data);
				}
			}
		}

		// Special case for lib_joomla
		if ($this->getState('translation.filename') == 'lib_joomla')
		{
			$tag = $this->getState('translation.tag');

			if (Folder::exists(JPATH_SITE . "/language/$tag"))
			{
				$this->setState('translation.client', 'site');
				$this->setState('translation.path', JPATH_SITE . "/language/$tag/lib_joomla.ini");
				$this->saveFile($data);
			}

			if (Folder::exists(JPATH_ADMINISTRATOR . "/language/$tag"))
			{
				$this->setState('translation.client', 'administrator');
				$this->setState('translation.path', JPATH_ADMINISTRATOR . "/language/$tag/lib_joomla.ini");
				$this->saveFile($data);
			}
		}
		else
		{
			$this->saveFile($data);
		}

		// Bind the rules.
		$table = $this->getTable();
		$table->load($data['id']);

		if (isset($data['rules']))
		{
			$rules = new JAccessRules($data['rules']);
			$table->setRules($rules);
		}

		// Check the data.
		if (!$table->check())
		{
			$this->setError($table->getError());

			return false;
		}

		// Store the data.
		if (!$table->store())
		{
			$this->setError($table->getError());

			return false;
		}

		if ($this->getState('translation.complete') == 1)
		{
			Factory::getApplication()->enqueueMessage(Text::_('COM_LOCALISE_NOTICE_TRANSLATION_COMPLETE'), 'notice');
		}
		else
		{
			Factory::getApplication()->enqueueMessage(Text::_('COM_LOCALISE_NOTICE_TRANSLATION_NOT_COMPLETE'), 'notice');
		}

		return true;
	}
}
