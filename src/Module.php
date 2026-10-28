<?php

namespace larikmc\metrika;

use Yii;
use yii\base\Module as BaseModule;
use yii\filters\AccessControl;

/**
 * Universal Yandex Metrika Management Module for Yii2.
 *
 * Provides a dedicated, standalone admin dashboard to inspect, understand,
 * and verify Yandex Metrika configuration settings, speed impact, and tracking goals.
 *
 * @author LarikMC <https://64web.ru>
 * @license MIT
 */
class Module extends BaseModule
{
    /**
     * @var string Controller namespace for this module.
     */
    public $controllerNamespace = 'larikmc\metrika\controllers';

    /**
     * @var string Default route.
     */
    public $defaultRoute = 'default/index';

    /**
     * @var string ID of the MetrikaLoader application component in Yii::$app.
     */
    public string $componentId = 'metrika';

    /**
     * @var int|string|null Optional fallback counter ID if no application component is configured.
     */
    public $counterId = null;

    /**
     * @var array Allowed roles for AccessControl. Defaults to ['@'] (authenticated users).
     * Set to empty array [] or ['?','@'] to disable RBAC check.
     */
    public array $accessRoles = ['@'];

    /**
     * @var string|null Custom layout name, or null to inherit the application's layout.
     */
    public $layout = null;

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        if (!empty($this->accessRoles)) {
            $behaviors['access'] = [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => $this->accessRoles,
                    ],
                ],
            ];
        }

        return $behaviors;
    }

    /**
     * Resolves the active MetrikaLoader component or constructs a fallback instance.
     *
     * @return MetrikaLoader
     */
    public function getLoader(): MetrikaLoader
    {
        if (Yii::$app !== null && Yii::$app->has($this->componentId)) {
            $comp = Yii::$app->get($this->componentId);
            if ($comp instanceof MetrikaLoader) {
                return $comp;
            }
        }

        $config = [];
        if ($this->counterId !== null) {
            $config['counterId'] = $this->counterId;
        }

        return new MetrikaLoader($config);
    }
}
