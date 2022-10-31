<?php

namespace codemonauts\its\fieldlayoutelements;

use codemonauts\its\services\Issues;
use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;

class StatusField extends BaseNativeField
{
    /**
     * @inheritdoc
     */
    public bool $mandatory = false;

    /**
     * @inheritdoc
     */
    public string $attribute = 'state';

    /**
     * @inheritdoc
     */
    public bool $translatable = false;

    /**
     * @inheritdoc
     */
    public bool $required = false;

    /**
     * @var bool Whether the input should get a `disabled` attribute.
     */
    public bool $disabled = false;

    /**
     * @inheritdoc
     */
    public function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('its', 'Status');
    }

    /**
     * @inheritdoc
     */
    public function defaultInstructions(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('its', 'The status of the issue.');
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        /**
         * @var \codemonauts\its\models\IssueType $issueType
         */
        $issueType = $element->getType();
        $options = [];

        foreach ($issueType->statuses as $status) {
            $options[] = [
                'label' => $status[0],
                'value' => $status[1],
                'data' => ['data' => ['status' => 'its-status-' . $issueType->handle . '-' . $status[1]]],
            ];
        }

        Issues::registerStatusCss();

        return Craft::$app->getView()->renderTemplate('_includes/forms/selectize', [
            'id' => $this->id(),
            'disabled' => $static || $this->disabled,
            'required' => !$static && $this->required,
            'options' => $options,
            'value' => $element->state,
            'name' => $this->attribute(),
        ]);
    }
}
