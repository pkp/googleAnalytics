<?php

/**
 * @file plugins/generic/googleAnalytics/GoogleAnalyticsPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GoogleAnalyticsPlugin
 * @ingroup plugins_generic_googleAnalytics
 *
 * @brief Google Analytics plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class GoogleAnalyticsPlugin extends GenericPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
		if ($success && $this->getEnabled()) {
			// Insert Google Analytics page tag to footer
			HookRegistry::register('Templates::Common::Footer::PageFooter', array($this, 'insertFooter'));
		}
		return $success;
	}

	/**
	 * Get the plugin display name.
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.googleAnalytics.displayName');
	}

	/**
	 * Get the plugin description.
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.googleAnalytics.description');
	}

	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $verb) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		return array_merge(
			$this->getEnabled()?array(
				new LinkAction(
					'settings',
					new AjaxModal(
						$router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')),
						$this->getDisplayName()
					),
					__('manager.plugins.settings'),
					null
				),
			):array(),
			parent::getActions($request, $verb)
		);
	}

 	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':
				$context = $request->getContext();

				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON,  LOCALE_COMPONENT_PKP_MANAGER);
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));

				$this->import('GoogleAnalyticsSettingsForm');
				$form = new GoogleAnalyticsSettingsForm($this, $context->getId());

				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						return new JSONMessage(true);
					}
				} else {
					$form->initData();
				}
				return new JSONMessage(true, $form->fetch($request));
		}
		return parent::manage($args, $request);
	}

	/**
	 * Override the builtin to get the correct template path.
	 * @return string
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}

	/**
	 * Insert Google Analytics page tag to footer
	 * @param $hookName string
	 * @param $params array
	 */
	function insertFooter($hookName, $params) {
		$request = $this->getRequest();
		$context = $request->getContext();
		if (!$context) return false;

		$googleAnalyticsSiteId = $this->getSetting($context->getId(), 'googleAnalyticsSiteId');
		if (empty($googleAnalyticsSiteId)) return false;

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('googleAnalyticsSiteId', $googleAnalyticsSiteId);
		$output =& $params[2];
		$output .= $templateMgr->fetch($this->getTemplatePath() . 'pageTag.tpl');

		return false;
	}
}

?>
