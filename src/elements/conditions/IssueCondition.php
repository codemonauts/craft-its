<?php

namespace codemonauts\its\elements\conditions;

use Craft;
use craft\base\ElementInterface;
use craft\elements\conditions\DateCreatedConditionRule;
use craft\elements\conditions\DateUpdatedConditionRule;
use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\IdConditionRule;
use craft\elements\conditions\RelatedToConditionRule;
use craft\elements\conditions\TitleConditionRule;
use craft\errors\InvalidTypeException;
use craft\fields\conditions\FieldConditionRuleInterface;

class IssueCondition extends ElementCondition
{
    /**
     * @inheritdoc
     */
    protected function conditionRuleTypes(): array
    {
        $types = [
            SubjectConditionRule::class,
            DateCreatedConditionRule::class,
            DateUpdatedConditionRule::class,
            IdConditionRule::class,
            RelatedToConditionRule::class,
            StateConditionRule::class,
        ];

        if ($this->elementType !== null) {
            /** @var string|ElementInterface $elementType */
            /** @phpstan-var class-string<ElementInterface>|ElementInterface $elementType */
            $elementType = $this->elementType;

            if ($elementType::hasContent()) {
                if ($elementType::hasTitles()) {
                    $types[] = TitleConditionRule::class;
                }

                // If we have a source key, we can fetch just the field layouts that are available to it
                if ($this->sourceKey) {
                    $fieldLayouts = Craft::$app->getElementSources()->getFieldLayoutsForSource($elementType, $this->sourceKey);
                } else {
                    $fieldLayouts = Craft::$app->getFields()->getLayoutsByType($elementType);
                }

                foreach ($fieldLayouts as $fieldLayout) {
                    foreach ($fieldLayout->getCustomFields() as $field) {
                        if (($type = $field->getElementConditionRuleType()) !== null) {
                            if (is_string($type)) {
                                $type = ['class' => $type];
                            }
                            if (!is_subclass_of($type['class'], FieldConditionRuleInterface::class)) {
                                throw new InvalidTypeException($type['class'], FieldConditionRuleInterface::class);
                            }
                            $type['fieldUid'] = $field->uid;
                            $types[] = $type;
                        }
                    }
                }
            }
        }

        return $types;
    }
}
