<?php

/**
 * @file plugins/importexport/abcdref/AbcdRefExportPlugin.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AbcdRefExportPlugin
 * @ingroup plugins_importexport_abcdref
 *
 * @brief AbcdRef/MEDLINE XML metadata export plugin
 */

import('classes.plugins.DOIPubIdExportPlugin');

// The status of the Crossref DOI.
// any, notDeposited, and markedRegistered are reserved
define('ABCDREF_STATUS_FAILED', 'failed');

define('ABCDREF_API_DEPOSIT_OK', 200);

define('ABCDREF_API_URL', 'https://api.crossref.org/v2/deposits');
//TESTING
define('ABCDREF_API_URL_DEV', 'https://test.crossref.org/v2/deposits');

define('ABCDREF_API_STAUTS_URL', 'https://api.crossref.org/servlet/submissionDownload');
//TESTING
define('ABCDREF_API_STAUTS_URL_DEV', 'https://test.crossref.org/servlet/submissionDownload');

// The name of the setting used to save the registered DOI and the URL with the deposit status.
define('ABCDREF_DEPOSIT_STATUS', 'depositStatus');


class AbcdRefExportPlugin extends DOIPubIdExportPlugin {

	/**
	 * @copydoc Plugin::getName()
	 */
	function getName() {
		return 'AbcdRefExportPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.importexport.abcdref.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.importexport.abcdref.description');
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getSubmissionFilter()
	 */
	function getSubmissionFilter() {
		return 'article=>abcdref-xml';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getStatusNames()
	 */
	function getStatusNames() {
		return array_merge(parent::getStatusNames(), array(
			EXPORT_STATUS_REGISTERED => __('plugins.importexport.abcdref.status.registered'),
			ABCDREF_STATUS_FAILED => __('plugins.importexport.abcdref.status.failed'),
			EXPORT_STATUS_MARKEDREGISTERED => __('plugins.importexport.abcdref.status.markedRegistered'),
		));
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getStatusActions()
	 */
	function getStatusActions($pubObject) {
		$request = Application::get()->getRequest();
		$dispatcher = $request->getDispatcher();
		return array(
			ABCDREF_STATUS_FAILED =>
				new LinkAction(
					'failureMessage',
					new AjaxModal(
						$dispatcher->url(
							$request, ROUTE_COMPONENT, null,
							'grid.settings.plugins.settingsPluginGridHandler',
							'manage', null, array('plugin' => 'AbcdRefExportPlugin', 'category' => 'importexport', 'verb' => 'statusMessage',
							'batchId' => $pubObject->getData($this->getDepositBatchIdSettingName()), 'articleId' => $pubObject->getId())
						),
						__('plugins.importexport.abcdref.status.failed'),
						'failureMessage'
					),
					__('plugins.importexport.abcdref.status.failed')
				)
		);
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getStatusMessage()
	 */
	function getStatusMessage($request) {
		// if the failure occured on request and the message was saved
		// return that message
		$articleId = $request->getUserVar('articleId');
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
		$article = $submissionDao->getByid($articleId);
		$failedMsg = $article->getData($this->getFailedMsgSettingName());
		if (!empty($failedMsg)) {
			return $failedMsg;
		}
		// else check the failure message with Abcdref, using the API
		$context = $request->getContext();

		import('lib.pkp.classes.helpers.PKPCurlHelper');
		$curlCh = PKPCurlHelper::getCurlObject();
		
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_POST, true);
		curl_setopt($curlCh, CURLOPT_HEADER, 0);

		// Use a different endpoint for testing and production.
		$endpoint = ($this->isTestMode($context) ? ABCDREF_API_STAUTS_URL_DEV : ABCDREF_API_STAUTS_URL);
		curl_setopt($curlCh, CURLOPT_URL, $endpoint);
		// Set the form post fields
		$username = $this->getSetting($context->getId(), 'username');
		$password = $this->getSetting($context->getId(), 'password');
		$batchId = $request->getUserVar('batchId');
		$data = array('doi_batch_id' => $batchId, 'type' => 'result', 'usr' => $username, 'pwd' => $password);
		curl_setopt($curlCh, CURLOPT_POSTFIELDS, $data);

		$response = curl_exec($curlCh);

		if ($response === false) {
			$result = __('plugins.importexport.common.register.error.mdsError', array('param' => 'No response from server.'));
		} else {
			$result = $response;
		}
		return $result;
	}


	/**
	 * @copydoc PubObjectsExportPlugin::getExportActionNames()
	 */
	function getExportActionNames() {
		return array(
			EXPORT_ACTION_DEPOSIT => __('plugins.importexport.abcdref.action.register'),
			EXPORT_ACTION_EXPORT => __('plugins.importexport.abcdref.action.export'),
			EXPORT_ACTION_MARKREGISTERED => __('plugins.importexport.abcdref.action.markRegistered'),
		);
	}

	/**
	 * Get a list of additional setting names that should be stored with the objects.
	 * @return array
	 */
	protected function _getObjectAdditionalSettings() {
		return array_merge(parent::_getObjectAdditionalSettings(), array(
			$this->getDepositBatchIdSettingName(),
			$this->getFailedMsgSettingName(),
		));
	}

	/**
	 * @copydoc ImportExportPlugin::getPluginSettingsPrefix()
	 */
	function getPluginSettingsPrefix() {
		return 'abcdref';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getSettingsFormClassName()
	 */
	function getSettingsFormClassName() {
		return 'AbcdRefSettingsForm';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getExportDeploymentClassName()
	 */
	function getExportDeploymentClassName() {
		return 'AbcdrefExportDeployment';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::executeExportAction()
	 */
	function executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart, $noValidation = null) {
		$context = $request->getContext();
		$path = array('plugin', $this->getName());

		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();
		$resultErrors = array();

		if ($request->getUserVar(EXPORT_ACTION_DEPOSIT)) {
			assert($filter != null);
			// Errors occured will be accessible via the status link
			// thus do not display all errors notifications (for every article),
			// just one general.
			// Warnings occured when the registration was successfull will however be
			// displayed for each article.
			$errorsOccured = false;
			// The new Abcdref deposit API expects one request per object.
			// On contrary the export supports bulk/batch object export, thus
			// also the filter expects an array of objects.
			// Thus the foreach loop, but every object will be in an one item array for
			// the export and filter to work.
			foreach ($objects as $object) {
				// Get the XML
				$exportXml = $this->exportXML(array($object), $filter, $context, $noValidation);
				// Write the XML to a file.
				// export file name example: abcdref-20160723-160036-articles-1-1.xml
				$objectsFileNamePart = $objectsFileNamePart . '-' . $object->getId();
				$exportFileName = $this->getExportFileName($this->getExportPath(), $objectsFileNamePart, $context, '.xml');
				$fileManager->writeFile($exportFileName, $exportXml);
				// Deposit the XML file.
				$result = $this->depositXML($object, $context, $exportFileName);
				if (!$result) {
					$errorsOccured = true;
				}
				if (is_array($result)) {
					$resultErrors[] = $result;
				}
				// Remove all temporary files.
				$fileManager->deleteByPath($exportFileName);
			}
			// send notifications
			if (empty($resultErrors)) {
				if ($errorsOccured) {
					$this->_sendNotification(
						$request->getUser(),
						'plugins.importexport.abcdref.register.error.mdsError',
						NOTIFICATION_TYPE_ERROR
					);
				} else {
					$this->_sendNotification(
						$request->getUser(),
						$this->getDepositSuccessNotificationMessageKey(),
						NOTIFICATION_TYPE_SUCCESS
					);
				}
			} else {
				foreach($resultErrors as $errors) {
					foreach ($errors as $error) {
						assert(is_array($error) && count($error) >= 1);
						$this->_sendNotification(
							$request->getUser(),
							$error[0],
							NOTIFICATION_TYPE_ERROR,
							(isset($error[1]) ? $error[1] : null)
						);
					}
				}
			}
			// redirect back to the right tab
			$request->redirect(null, null, null, $path, null, $tab);
		} else {
			parent::executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart, $noValidation);
		}
	}

	/**
	 * @see PubObjectsExportPlugin::depositXML()
	 *
	 * @param $objects Submission
	 * @param $context Context
	 * @param $filename string Export XML filename
	 */
	function depositXML($objects, $context, $filename) {
		$status = null;

		import('lib.pkp.classes.helpers.PKPCurlHelper');
		$curlCh = PKPCurlHelper::getCurlObject();

		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_POST, true);
		curl_setopt($curlCh, CURLOPT_HEADER, 0);

		// Use a different endpoint for testing and production.
		$endpoint = ($this->isTestMode($context) ? ABCDREF_API_URL_DEV : ABCDREF_API_URL);
		curl_setopt($curlCh, CURLOPT_URL, $endpoint);
		// Set the form post fields
		$username = $this->getSetting($context->getId(), 'username');
		$password = $this->getSetting($context->getId(), 'password');
		assert(is_readable($filename));
		if (function_exists('curl_file_create')) {
			curl_setopt($curlCh, CURLOPT_SAFE_UPLOAD, true);
			$cfile = new CURLFile($filename);
		} else {
			$cfile = "@$filename";
		}
		$data = array('operation' => 'doMDUpload', 'usr' => $username, 'pwd' => $password, 'mdFile' => $cfile);
		curl_setopt($curlCh, CURLOPT_POSTFIELDS, $data);
		$response = curl_exec($curlCh);

		$msg = null;
		if ($response === false) {
			$result = array(array('plugins.importexport.common.register.error.mdsError', 'No response from server.'));
		} elseif (curl_getinfo($curlCh, CURLINFO_HTTP_CODE) != ABCDREF_API_DEPOSIT_OK) {
			// These are the failures that occur immediatelly on request
			// and can not be accessed later, so we save the falure message in the DB
			$xmlDoc = new DOMDocument();
			$xmlDoc->loadXML($response);
			// Get batch ID
			$batchIdNode = $xmlDoc->getElementsByTagName('batch_id')->item(0);
			// Get re message
			$msg = $response;
			$status = ABCDREF_STATUS_FAILED;
			$result = false;
		} else {
			// Get DOMDocument from the response XML string
			$xmlDoc = new DOMDocument();
			$xmlDoc->loadXML($response);
			$batchIdNode = $xmlDoc->getElementsByTagName('batch_id')->item(0);

			// Get the DOI deposit status
			// If the deposit failed
			$failureCountNode = $xmlDoc->getElementsByTagName('failure_count')->item(0);
			$failureCount = (int) $failureCountNode->nodeValue;
			if ($failureCount > 0) {
				$status = ABCDREF_STATUS_FAILED;
				$result = false;
			} else {
				// Deposit was received
				$status = EXPORT_STATUS_REGISTERED;
				$result = true;

				// If there were some warnings, display them
				$warningCountNode = $xmlDoc->getElementsByTagName('warning_count')->item(0);
				$warningCount = (int) $warningCountNode->nodeValue;
				if ($warningCount > 0) {
					$result = array(array('plugins.importexport.abcdref.register.success.warning', htmlspecialchars($response)));
				}
				// A possibility for other plugins (e.g. reference linking) to work with the response
				HookRegistry::call('abcdrefexportplugin::deposited', array($this, $response, $objects));
			}
		}
		// Update the status
		if ($status) {
			$this->updateDepositStatus($context, $objects, $status, $batchIdNode->nodeValue, $msg);
			$this->updateObject($objects);
		}

		curl_close($curlCh);
		return $result;
	}

	/**
	 * Check the AbcdRef APIs, if deposits and registration have been successful
	 * @param $context Context
	 * @param $object The object getting deposited
	 * @param $status ABCDREF_STATUS_...
	 * @param $batchId string
	 * @param $failedMsg string (opitonal)
	 */
	function updateDepositStatus($context, $object, $status, $batchId, $failedMsg = null) {
		assert(is_a($object, 'Submission') or is_a($object, 'Issue'));
		// remove the old failure message, if exists
		$object->setData($this->getFailedMsgSettingName(), null);
		$object->setData($this->getDepositStatusSettingName(), $status);
		$object->setData($this->getDepositBatchIdSettingName(), $batchId);
		if ($failedMsg) {
			$object->setData($this->getFailedMsgSettingName(), $failedMsg);
		}
		if ($status == EXPORT_STATUS_REGISTERED) {
			// Save the DOI -- the object will be updated
			$this->saveRegisteredDoi($context, $object);
		}
	}

	/**
	 * @copydoc DOIPubIdExportPlugin::markRegistered()
	 */
	function markRegistered($context, $objects) {
		foreach ($objects as $object) {
			// remove the old failure message, if exists
			$object->setData($this->getFailedMsgSettingName(), null);
			$object->setData($this->getDepositStatusSettingName(), EXPORT_STATUS_MARKEDREGISTERED);
			$this->saveRegisteredDoi($context, $object);
		}
	}

	/**
	 * Get request failed message setting name.
	 * @return string
	 */
	function getFailedMsgSettingName() {
		return $this->getPluginSettingsPrefix().'::failedMsg';
	}

	/**
	 * Get deposit batch ID setting name.
	 * @return string
	 */
	function getDepositBatchIdSettingName() {
		return $this->getPluginSettingsPrefix().'::batchId';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getDepositSuccessNotificationMessageKey()
	 */
	function getDepositSuccessNotificationMessageKey() {
		return 'plugins.importexport.common.register.success';
	}

	/**
	 * @copydoc PKPImportExportPlugin::executeCLI()
	 */
	function executeCLICommand($scriptName, $command, $context, $outputFile, $objects, $filter, $objectsFileNamePart) {
		switch ($command) {
			case 'export':
				PluginRegistry::loadCategory('generic', true, $context->getId());
				$exportXml = $this->exportXML($objects, $filter, $context);
				if ($outputFile) file_put_contents($outputFile, $exportXml);
				break;
			case 'register':
				PluginRegistry::loadCategory('generic', true, $context->getId());
				import('lib.pkp.classes.file.FileManager');
				$fileManager = new FileManager();
				$resultErrors = array();
				// Errors occured will be accessible via the status link
				// thus do not display all errors notifications (for every article),
				// just one general.
				// Warnings occured when the registration was successfull will however be
				// displayed for each article.
				$errorsOccured = false;
				// The new Abcdref deposit API expects one request per object.
				// On contrary the export supports bulk/batch object export, thus
				// also the filter expects an array of objects.
				// Thus the foreach loop, but every object will be in an one item array for
				// the export and filter to work.
				foreach ($objects as $object) {
					// Get the XML
					$exportXml = $this->exportXML(array($object), $filter, $context);
					// Write the XML to a file.
					// export file name example: abcdref-20160723-160036-articles-1-1.xml
					$objectsFileNamePartId = $objectsFileNamePart . '-' . $object->getId();
					$exportFileName = $this->getExportFileName($this->getExportPath(), $objectsFileNamePartId, $context, '.xml');
					$fileManager->writeFile($exportFileName, $exportXml);
					// Deposit the XML file.
					$result = $this->depositXML($object, $context, $exportFileName);
					if (!$result) {
						$errorsOccured = true;
					}
					if (is_array($result)) {
						$resultErrors[] = $result;
					}
					// Remove all temporary files.
					$fileManager->deleteByPath($exportFileName);
				}
				// display deposit result status messages
				if (empty($resultErrors)) {
					if ($errorsOccured) {
						echo __('plugins.importexport.abcdref.register.error.mdsError') . "\n";
					} else {
						echo __('plugins.importexport.common.register.success') . "\n";
					}
				} else {
					echo __('plugins.importexport.common.cliError') . "\n";
					foreach($resultErrors as $errors) {
						foreach ($errors as $error) {
							assert(is_array($error) && count($error) >= 1);
							$errorMessage = __($error[0], array('param' => (isset($error[1]) ? $error[1] : null)));
							echo "*** $errorMessage\n";
						}
					}
					echo "\n";
					$this->usage($scriptName);
				}
				break;
		}
	}
}

