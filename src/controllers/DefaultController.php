<?php

namespace larikmc\metrika\controllers;

use larikmc\metrika\MetrikaLoader;
use larikmc\metrika\Module;
use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Default controller for Metrika Admin Dashboard.
 *
 * Handles viewing and saving Yandex Metrika configuration settings.
 *
 * @property Module $module
 *
 * @author LarikMC <https://64web.ru>
 * @license MIT
 */
class DefaultController extends Controller
{
    /**
     * Dashboard page showing all active settings, speed impact, and developer cheatsheet.
     * Also processes POST requests to save settings directly from the UI.
     *
     * @return Response|string
     */
    public function actionIndex()
    {
        $loader = $this->module->getLoader();
        $componentId = $this->module->componentId;
        $hasComponent = Yii::$app !== null && Yii::$app->has($componentId);

        if ($this->request->isPost) {
            $postData = $this->request->post('MetrikaSetting', []);
            try {
                $loader->saveSettings($postData);
                Yii::$app->session->setFlash('metrika_success', 'Настройки Яндекс.Метрики успешно сохранены!');
                Yii::$app->session->setFlash('success', 'Настройки Яндекс.Метрики успешно сохранены!');
            } catch (\Throwable $e) {
                Yii::$app->session->setFlash('metrika_error', 'Ошибка при сохранении настроек: ' . $e->getMessage());
                Yii::$app->session->setFlash('error', 'Ошибка при сохранении настроек: ' . $e->getMessage());
            }

            return $this->redirect(['index']);
        }

        return $this->render('index', [
            'loader' => $loader,
            'componentId' => $componentId,
            'hasComponent' => $hasComponent,
        ]);
    }
}