<?php

namespace codemonauts\its\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextField;

class SubjectField extends TextField
{
    /**
     * @inheritdoc
     */
    public bool $mandatory = true;

    /**
     * @inheritdoc
     */
    public string $attribute = 'subject';

    /**
     * @inheritdoc
     */
    public bool $translatable = false;

    /**
     * @inheritdoc
     */
    public ?int $maxlength = 255;

    /**
     * @inheritdoc
     */
    public bool $required = true;

    /**
     * @inheritdoc
     */
    public bool $autofocus = true;

    /**
     * @inheritdoc
     */
    public function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('its', 'Subject');
    }

    /**
     * @inheritdoc
     */
    public function defaultInstructions(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('its', 'The subject of the issue.');
    }
}
