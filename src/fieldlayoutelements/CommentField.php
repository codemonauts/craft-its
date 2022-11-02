<?php

namespace codemonauts\its\fieldlayoutelements;

use codemonauts\its\IssueTrackingSystem;
use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextField;

class CommentField extends TextField
{
    /**
     * @inheritdoc
     */
    public bool $mandatory = false;

    /**
     * @inheritdoc
     */
    public string $attribute = 'comment';

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
    public bool $required = false;

    /**
     * @inheritdoc
     */
    public bool $autofocus = true;

    /**
     * @inheritdoc
     */
    public function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('its', 'Comment');
    }

    /**
     * @inheritdoc
     */
    public function defaultInstructions(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('its', 'Add a comment to the issue.');
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::$app->getView()->renderTemplate('its/fields/comment.twig', [
            'type' => $this->type,
            'autocomplete' => $this->autocomplete,
            'class' => $this->class,
            'id' => $this->id(),
            'describedBy' => $this->describedBy($element, $static),
            'size' => $this->size,
            'name' => $this->name ?? $this->attribute(),
            'value' => $this->value($element),
            'maxlength' => $this->maxlength,
            'autofocus' => $this->autofocus,
            'autocorrect' => $this->autocorrect,
            'autocapitalize' => $this->autocapitalize,
            'disabled' => $static || $this->disabled,
            'readonly' => $this->readonly,
            'required' => !$static && $this->required,
            'title' => $this->title,
            'placeholder' => $this->placeholder,
            'step' => $this->step,
            'min' => $this->min,
            'max' => $this->max,

            'history' => IssueTrackingSystem::$plugin->getHistory()->getHistoryOfIssue($element->id),
        ]);
    }
}
