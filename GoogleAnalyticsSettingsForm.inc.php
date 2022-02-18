<?php

/**
 * @file plugins/generic/googleAnalytics/GoogleAnalyticsSettingsForm.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GoogleAnalyticsSettingsForm
 * @ingroup plugins_generic_googleAnalytics
 *
 * @brief Form for journal managers to modify Google Analytics plugin settings
 */

use APP\template\TemplateManager;
use PKP\form\Form;

class GoogleAnalyticsSettingsForm extends Form
{
    /** @var int */
    public $_journalId;

    /** @var object */
    public $_plugin;

    /**
     * Constructor
     *
     * @param GoogleAnalyticsPlugin $plugin
     * @param int $journalId
     */
    public function __construct($plugin, $journalId)
    {
        $this->_journalId = $journalId;
        $this->_plugin = $plugin;

        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'googleAnalyticsSiteId', 'required', 'plugins.generic.googleAnalytics.manager.settings.googleAnalyticsSiteIdRequired'));

        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }

    /**
     * Initialize form data.
     */
    public function initData()
    {
        $this->_data = [
            'googleAnalyticsSiteId' => $this->_plugin->getSetting($this->_journalId, 'googleAnalyticsSiteId'),
        ];
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        $this->readUserVars(['googleAnalyticsSiteId']);
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->_plugin->getName());
        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $this->_plugin->updateSetting($this->_journalId, 'googleAnalyticsSiteId', trim($this->getData('googleAnalyticsSiteId'), "\"\';"), 'string');
        parent::execute(...$functionArgs);
    }
}
