<?php

namespace codemonauts\its\elements\conditions;

use codemonauts\its\IssueTrackingSystem;
use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use yii\db\QueryInterface;

class StateConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('its', 'State');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['state'];
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        $options = [];
        $issueTypes = IssueTrackingSystem::$plugin->getIssues()->getAllIssueTypes();

        foreach ($issueTypes as $issueType) {
            foreach ($issueType->statuses as $status) {
                $options[$status[1]] = $status[0];
            }
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var ElementQueryInterface $query */
        $query->state($this->paramValue());
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->state);
    }
}