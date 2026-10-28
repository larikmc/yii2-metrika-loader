<?php

namespace larikmc\metrika;

use Yii;
use yii\base\Widget;

/**
 * Universal Yandex Metrika Widget for Yii2 templates.
 *
 * Usage in layouts:
 * ```php
 * <?= \larikmc\metrika\MetrikaWidget::widget([
 *     'counterId' => 113667541,
 *     'mode' => 'deferred',
 *     'webvisor' => true,
 *     'ecommerce' => 'dataLayer',
 * ]) ?>
 * ```
 * Or when configured as an application component 'metrika':
 * ```php
 * <?= \larikmc\metrika\MetrikaWidget::widget() ?>
 * ```
 *
 * @author LarikMC <https://64web.ru>
 * @license MIT
 */
class MetrikaWidget extends Widget
{
    /**
     * @var string Component ID in Yii::$app if registered as a component.
     */
    public string $componentId = 'metrika';

    /**
     * @var int|string|null Counter ID. If null, taken from component.
     */
    public $counterId = null;

    /**
     * @var string|null Loading mode: 'deferred' or 'immediate'.
     */
    public ?string $mode = null;

    /**
     * @var bool|null Enabled flag.
     */
    public ?bool $enabled = null;

    /**
     * @var int|null Max delay before forced tag.js load (ms).
     */
    public ?int $maxDelay = null;

    /**
     * @var int|null Idle timeout for requestIdleCallback (ms).
     */
    public ?int $idleTimeout = null;

    /**
     * @var bool|null Load on first user interaction.
     */
    public ?bool $loadOnInteraction = null;

    /**
     * @var array|null Interaction event names.
     */
    public ?array $interactionEvents = null;

    /**
     * @var bool|null Webvisor option.
     */
    public ?bool $webvisor = null;

    /**
     * @var bool|null Clickmap option.
     */
    public ?bool $clickmap = null;

    /**
     * @var bool|null Track links option.
     */
    public ?bool $trackLinks = null;

    /**
     * @var bool|int|null Accurate track bounce option.
     */
    public $accurateTrackBounce = null;

    /**
     * @var string|bool|null Ecommerce data layer.
     */
    public $ecommerce = null;

    /**
     * @var bool|null Trigger event flag.
     */
    public ?bool $triggerEvent = null;

    /**
     * @var array Additional params.
     */
    public array $params = [];

    /**
     * @var array Allowed hosts.
     */
    public array $allowedHosts = [];

    /**
     * @var string|callable|null CSP nonce.
     */
    public $nonce = null;

    /**
     * @var bool|null Render noscript tag.
     */
    public ?bool $renderNoscript = null;

    /**
     * @var bool|null Require cookie/privacy consent.
     */
    public ?bool $requireConsent = null;

    /**
     * @var string|null Consent storage key.
     */
    public ?string $consentStorageKey = null;

    /**
     * @var string|null Consent event name.
     */
    public ?string $consentEvent = null;

    /**
     * @var string|null Reject event name.
     */
    public ?string $rejectEvent = null;

    /**
     * @var bool|null Inline script rendering.
     */
    public ?bool $inline = null;

    /**
     * @var MetrikaLoader|null
     */
    private ?MetrikaLoader $_loader = null;

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        $component = null;
        if (Yii::$app !== null && Yii::$app->has($this->componentId)) {
            $component = Yii::$app->get($this->componentId);
        }

        $config = [];
        if ($component instanceof MetrikaLoader) {
            $config = [
                'counterId' => $component->counterId,
                'mode' => $component->mode,
                'enabled' => $component->enabled,
                'maxDelay' => $component->maxDelay,
                'idleTimeout' => $component->idleTimeout,
                'loadOnInteraction' => $component->loadOnInteraction,
                'interactionEvents' => $component->interactionEvents,
                'tagUrl' => $component->tagUrl,
                'webvisor' => $component->webvisor,
                'clickmap' => $component->clickmap,
                'trackLinks' => $component->trackLinks,
                'accurateTrackBounce' => $component->accurateTrackBounce,
                'ecommerce' => $component->ecommerce,
                'triggerEvent' => $component->triggerEvent,
                'params' => $component->params,
                'allowedHosts' => $component->allowedHosts,
                'nonce' => $component->nonce,
                'renderNoscript' => $component->renderNoscript,
                'requireConsent' => $component->requireConsent,
                'consentStorageKey' => $component->consentStorageKey,
                'consentEvent' => $component->consentEvent,
                'rejectEvent' => $component->rejectEvent,
                'inline' => $component->inline,
            ];
        }

        // Apply widget overrides
        $properties = [
            'counterId', 'mode', 'enabled', 'maxDelay', 'idleTimeout',
            'loadOnInteraction', 'interactionEvents', 'webvisor', 'clickmap',
            'trackLinks', 'accurateTrackBounce', 'ecommerce', 'triggerEvent',
            'allowedHosts', 'nonce', 'renderNoscript', 'requireConsent',
            'consentStorageKey', 'consentEvent', 'rejectEvent', 'inline',
        ];

        foreach ($properties as $prop) {
            if ($this->$prop !== null) {
                $config[$prop] = $this->$prop;
            }
        }

        if (!empty($this->params)) {
            $config['params'] = array_merge($config['params'] ?? [], $this->params);
        }

        $config['class'] = MetrikaLoader::class;

        $this->_loader = Yii::createObject($config);
    }

    /**
     * {@inheritdoc}
     */
    public function run(): string
    {
        if ($this->_loader === null || !$this->_loader->isEnabled()) {
            return '';
        }

        return $this->_loader->render($this->getView());
    }

    /**
     * Returns the underlying MetrikaLoader instance.
     *
     * @return MetrikaLoader|null
     */
    public function getLoader(): ?MetrikaLoader
    {
        return $this->_loader;
    }
}
