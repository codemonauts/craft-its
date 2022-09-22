<?php

namespace codemonauts\its\controllers;

use codemonauts\its\elements\Issue;
use codemonauts\its\IssueTrackingSystem;
use codemonauts\its\models\IssueType;
use Craft;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class IssuetypeController extends Controller
{
    public function beforeAction($action): bool
    {
        $this->requireAdmin();

        return parent::beforeAction($action);
    }

    public function actionIndex(): Response
    {
        $issueTypes = IssueTrackingSystem::$plugin->getIssues()->getAllIssueTypes();

        return $this->renderTemplate('its/settings/issuetypes/index', [
            'plugin' => IssueTrackingSystem::$plugin,
            'issueTypes' => $issueTypes,
        ]);
    }

    public function actionEdit(?int $id = null, ?IssueType $issueType = null): Response
    {
        if ($id !== null) {
            if ($issueType === null) {
                $issueType = IssueTrackingSystem::$plugin->getIssues()->getIssueTypeById($id);

                if (!$issueType) {
                    throw new NotFoundHttpException('Issue type not found');
                }
            }

            $title = trim($issueType->name) ?: Craft::t('its', 'Edit Issue Type');
        } else {
            if ($issueType === null) {
                $issueType = new IssueType();
            }

            $title = Craft::t('its', 'Create a new issue type');
        }

        return $this->renderTemplate('its/settings/issuetypes/_edit', [
            'plugin' => IssueTrackingSystem::$plugin,
            'issueType' => $issueType,
            'title' => $title,
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $issueService = IssueTrackingSystem::$plugin->getIssues();

        $issueTypeId = $this->request->getBodyParam('issueTypeId');

        if ($issueTypeId) {
            $issueType = $issueService->getIssueTypeById($issueTypeId);
            if (!$issueType) {
                throw new BadRequestHttpException("Invalid issue type ID: $issueTypeId");
            }
        } else {
            $issueType = new IssueType();
        }

        $issueType->name = $this->request->getBodyParam('name', $issueType->name);
        $issueType->handle = $this->request->getBodyParam('handle', $issueType->handle);

        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Issue::class;
        $issueType->setFieldLayout($fieldLayout);

        if (!$issueService->saveIssueType($issueType)) {
            $this->setFailFlash(Craft::t('its', 'Couldn’t save issue type.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'issueType' => $issueType,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('its', 'Issue type saved.'));

        return $this->redirectToPostedUrl($issueType);
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $issueTypeId = $this->request->getRequiredBodyParam('id');

        $success = IssueTrackingSystem::$plugin->getIssues()->deleteIssueTypeById($issueTypeId);

        return $success ? $this->asSuccess() : $this->asFailure();
    }
}
