<?php

namespace codemonauts\its;

use codemonauts\its\elements\Issue;
use codemonauts\its\models\Settings;
use codemonauts\its\services\Issues;
use Craft;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Elements;
use craft\services\ProjectConfig;
use craft\web\Controller;
use craft\web\UrlManager;
use yii\base\Event;
use yii\web\Response;

/**
 * Class IssueTrackingSystem
 */
class IssueTrackingSystem extends Plugin
{
    /**
     * @var \codemonauts\its\IssueTrackingSystem|null
     */
    public static ?IssueTrackingSystem $plugin;

    /**
     * @var \codemonauts\its\models\Settings|null
     */
    public static ?Settings $settings;

    /**
     * @inheritDoc
     */
    public bool $hasCpSettings = true;

    /**
     * @inheritDoc
     */
    public bool $hasCpSection = true;

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        self::$settings = $this->getSettings();

        $this->setComponents([
            'issues' => Issues::class,
        ]);

        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = Issue::class;
        });

        // Register CP routes
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            $event->rules['settings/plugins/its/issuetypes'] = 'its/issuetype/index';
            $event->rules['settings/plugins/its/issuetype/new'] = 'its/issuetype/edit';
            $event->rules['settings/plugins/its/issuetype/delete'] = 'its/issuetype/delete';
            $event->rules['settings/plugins/its/issuetype/<id:\d+>'] = 'its/issuetype/edit';
        });
    }

    /**
     * @inheritDoc
     */
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    /**
     * @inheritDoc
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('its/settings/_settings', [
                'settings' => $this->getSettings()
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getSettingsResponse(): Response
    {
        $settingsHtml = Craft::$app->getView()->namespaceInputs(function() {
            return (string)$this->settingsHtml();
        }, 'settings');

        return Craft::$app->controller->renderTemplate('its/settings/index', [
            'plugin' => $this,
            'settingsHtml' => $settingsHtml,
        ]);
    }

    /**
     * Returns the issues component.
     *
     * @return Issues
     * @throws \yii\base\InvalidConfigException
     */
    public function getIssues(): Issues
    {
        return $this->get('issues');
    }
}
