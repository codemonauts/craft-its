<?php

namespace codemonauts\its\controllers;

use codemonauts\its\elements\Issue;
use codemonauts\its\IssueTrackingSystem;
use Craft;
use craft\base\Element;
use craft\elements\Entry;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use yii\web\Response;

class IssueController extends Controller
{
    public function actionCreate(): ?Response
    {
        $user = Craft::$app->getUser()->getIdentity();

        // Create & populate the draft
        $element = Craft::createObject(Issue::class);
        $element->enabled = true;

        // Issue Type
        $issueTypes = IssueTrackingSystem::$plugin->getIssues()->getAllIssueTypes();
        $element->setTypeId($issueTypes[0]->id);

        // Custom fields
        foreach ($element->getFieldLayout()->getCustomFields() as $field) {
            if (($value = $this->request->getQueryParam($field->handle)) !== null) {
                $element->setFieldValue($field->handle, $value);
            }
        }

        // Save it
        $element->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($element, $user->getId(), null, null, false)) {
            return $this->asModelFailure($element, Craft::t('app', 'Couldn’t create {type}.', [
                'type' => Entry::lowerDisplayName(),
            ]), 'entry');
        }

        $editUrl = $element->getCpEditUrl();

        $response = $this->asModelSuccess($element, Craft::t('app', '{type} created.', [
            'type' => Entry::displayName(),
        ]), 'entry', array_filter([
            'cpEditUrl' => $this->request->isCpRequest ? $editUrl : null,
        ]));

        if (!$this->request->getAcceptsJson()) {
            $response->redirect(UrlHelper::urlWithParams($editUrl, [
                'fresh' => 1,
            ]));
        }

        return $response;
    }
}