<?php

/**
 * @file plugins/generic/googleAnalytics/GoogleAnalyticsPlugin.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GoogleAnalyticsPlugin
 * @ingroup plugins_generic_googleAnalytics
 *
 * @brief Google Analytics plugin class
 */

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;

use PKP\plugins\GenericPlugin;
use PKP\plugins\HookRegistry;

class GoogleAnalyticsPlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if (Application::isUnderMaintenance()) {
            return true;
        }
        if ($success && $this->getEnabled($mainContextId)) {
            // Insert Google Analytics page tag to footer
            HookRegistry::register('TemplateManager::display', [$this, 'registerScript']);
        }
        return $success;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.googleAnalytics.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.generic.googleAnalytics.description');
    }

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $verb)
    {
        $router = $request->getRouter();
        return array_merge(
            $this->getEnabled() ? [
                new LinkAction(
                    'settings',
                    new AjaxModal(
                        $router->url($request, null, null, 'manage', null, ['verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic']),
                        $this->getDisplayName()
                    ),
                    __('manager.plugins.settings'),
                    null
                ),
            ] : [],
            parent::getActions($request, $verb)
        );
    }

    /**
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request)
    {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                $context = $request->getContext();
                $templateMgr = TemplateManager::getManager($request);
                $templateMgr->registerPlugin('function', 'plugin_url', [$this, 'smartyPluginUrl']);

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
     * Register the Google Analytics script tag
     *
     * @param string $hookName
     * @param array $params
     */
    public function registerScript($hookName, $params)
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context) {
            return false;
        }
        $router = $request->getRouter();
        if (!is_a($router, 'PKPPageRouter')) {
            return false;
        }

        $googleAnalyticsSiteId = $this->getSetting($context->getId(), 'googleAnalyticsSiteId');
        if (empty($googleAnalyticsSiteId)) {
            return false;
        }

        $googleAnalyticsCode = "
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

ga('create', '${googleAnalyticsSiteId}', 'auto');
ga('send', 'pageview');
";

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->addJavaScript(
            'googleanalytics',
            $googleAnalyticsCode,
            [
                'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
                'inline' => true,
            ]
        );

        return false;
    }
}
