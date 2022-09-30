<?php

namespace codemonauts\its\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\elements\User;
use craft\fieldlayoutelements\BaseNativeField;

class ReporterField extends BaseNativeField
{
    /**
     * @inheritdoc
     */
    public bool $mandatory = false;

    /**
     * @inheritdoc
     */
    public string $attribute = 'reporter';

    /**
     * @inheritdoc
     */
    public bool $translatable = false;

    /**
     * @inheritdoc
     */
    public bool $required = true;

    /**
     * @var bool Whether the input should get a `disabled` attribute.
     */
    public bool $disabled = false;

    /**
     * @inheritdoc
     */
    public function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('its', 'Reporter');
    }

    /**
     * @inheritdoc
     */
    public function defaultInstructions(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('its', 'The reporter of the issue.');
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/elementSelect', [
            'id' => $this->id(),
            'single' => true,
            'elements' => [$element->getReporter()],
            'elementType' => User::class,
            'disabled' => $static || $this->disabled,
            'required' => !$static && $this->required,
            'name' => $this->attribute(),
        ]);
    }
}
