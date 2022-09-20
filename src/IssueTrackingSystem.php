<?php

namespace codemonauts\its;

use codemonauts\its\elements\Issue;
use codemonauts\its\models\Settings;
use Craft;
use craft\base\Plugin;
use craft\events\ConfigEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Json;
use craft\services\Elements;
use craft\services\ProjectConfig;
use yii\base\Event;

/**
 * Class IssueTrackingSystem
 *
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

        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = Issue::class;
        });

        Craft::$app->projectConfig
            ->onAdd(ProjectConfig::PATH_PLUGINS . '.' . $this->handle . '.issueFieldLayout', [$this, 'handleChangedFieldLayout'])
            ->onUpdate(ProjectConfig::PATH_PLUGINS . '.' . $this->handle . '.issueFieldLayout', [$this, 'handleChangedFieldLayout'])
            ->onRemove(ProjectConfig::PATH_PLUGINS . '.' . $this->handle . '.issueFieldLayout', [$this, 'handleRemovedFieldLayout']);
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
        return Craft::$app->getView()->renderTemplate('its/settings', [
                'settings' => $this->getSettings()
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function beforeSaveSettings(): bool
    {
        $settings = Craft::$app->getRequest()->getBodyParam('settings');
        $config = Json::decode($settings['fieldLayout']);

        $fieldLayout = Craft::$app->fields->createLayout($config);
        $fieldLayout->type = Issue::class;

        Craft::$app->fields->saveLayout($fieldLayout);

        IssueTrackingSystem::$settings->fieldLayoutConfig =

        return true;
    }

    public function handleChangedFieldLayout(ConfigEvent $event)
    {
        // Get the UID that was matched in the config path
        $uid = $event->tokenMatches[0];

        // Does this product type exist?
        $id = (new Query())
            ->select(['id'])
            ->from('{{%producttypes}}')
            ->where(['uid' => $uid])
            ->scalar();

        $isNew = empty($id);

        // Insert or update its row
        if ($isNew) {
            Craft::$app->db->createCommand()
                ->insert('{{%producttypes}}', [
                    'name' => $event->newValue['name'],
                    // ...
                ])
                ->execute();
        } else {
            Craft::$app->db->createCommand()
                ->update('{{%producttypes}}', [
                    'name' => $event->newValue['name'],
                    // ...
                ], ['id' => $id])
                ->execute();
        }

        // Fire an 'afterSaveProductType' event?
        if ($this->hasEventHandlers('afterSaveProductType')) {
            $productType = $this->getProductTypeByUid($uid);
            $this->trigger('afterSaveProductType', new ProducTypeEvent([
                'productType' => $productType,
                'isNew' => $isNew,
            ]));
        }
    }
}
