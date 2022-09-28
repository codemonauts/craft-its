<?php

namespace codemonauts\its;

use codemonauts\its\elements\Issue;
use codemonauts\its\fieldlayoutelements\SubjectField;
use codemonauts\its\models\Settings;
use codemonauts\its\services\Issues;
use Craft;
use craft\base\Plugin;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\services\Elements;
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

        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = Issue::class;
        });

        // Register CP routes
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
            $event->rules['settings/plugins/its/issuetypes'] = 'its/issuetype/index';
            $event->rules['settings/plugins/its/issuetype/new'] = 'its/issuetype/edit';
            $event->rules['settings/plugins/its/issuetype/delete'] = 'its/issuetype/delete';
            $event->rules['settings/plugins/its/issuetype/<id:\d+>'] = 'its/issuetype/edit';

            $event->rules['its/issue/new'] = 'its/issue/create';
            $event->rules['its/issue/<elementId:\d+>'] = 'elements/edit';
            $event->rules['its/issue/take/<issueId:\d+>'] = 'its/issue/take';
        });

        // Register field layout
        Event::on(FieldLayout::class, FieldLayout::EVENT_DEFINE_NATIVE_FIELDS, function (DefineFieldLayoutFieldsEvent $event) {
            /**
             * @var FieldLayout $fieldLayout
             */
            $fieldLayout = $event->sender;

            if ($fieldLayout->type === Issue::class) {
                $event->fields[] = SubjectField::class;
            }
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
                'settings' => $this->getSettings(),
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getSettingsResponse(): Response
    {
        $settingsHtml = Craft::$app->getView()->namespaceInputs(function () {
            return (string)$this->settingsHtml();
        }, 'settings');

        return Craft::$app->controller->renderTemplate('its/settings/index', [
            'plugin' => $this,
            'settingsHtml' => $settingsHtml,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getCpNavItem(): ?array
    {
        $navItem = parent::getCpNavItem();
        $subNavs = [];

        $subNavs['issues'] = [
            'url' => $this->handle . '/issues',
            'label' => Craft::t('its', 'Issues'),
        ];

        $navItem['subnav'] = $subNavs;

        return $navItem;
    }

    /**
     * @inheritDoc
     */
    public function afterInstall(): void
    {
        parent::afterInstall();

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        Craft::$app->getResponse()->redirect(
            UrlHelper::cpUrl('settings/plugins/its')
        )->send();
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
