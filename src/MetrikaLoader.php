<?php

namespace larikmc\metrika;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\Request;
use yii\web\View;

/**
 * Universal Yandex Metrika Loader Component for Yii2.
 *
 * Provides optimized, deferred loading of Yandex Metrika to maximize
 * Google PageSpeed Insights (PSI) and Total Blocking Time (TBT) scores
 * while guaranteeing 100% data and goal tracking fidelity.
 *
 * @author LarikMC <https://64web.ru>
 * @license MIT
 */
class MetrikaLoader extends Component
{
    public const MODE_DEFERRED = 'deferred';
    public const MODE_IMMEDIATE = 'immediate';

    /**
     * @var int|string Yandex Metrika Counter ID (e.g. 113667541).
     */
    public $counterId;

    /**
     * @var string Loading mode: 'deferred' (optimized) or 'immediate' (standard fallback).
     */
    public string $mode = self::MODE_DEFERRED;

    /**
     * @var bool Whether Metrika is enabled. Can be tied to YII_ENV_PROD or config.
     */
    public bool $enabled = true;

    /**
     * @var int Maximum delay in milliseconds before forcing tag.js download.
     */
    public int $maxDelay = 3500;

    /**
     * @var int Idle callback timeout in milliseconds for requestIdleCallback.
     */
    public int $idleTimeout = 1500;

    /**
     * @var bool Whether to trigger immediate load on the first user interaction.
     */
    public bool $loadOnInteraction = true;

    /**
     * @var array DOM event names that constitute user interaction.
     */
    public array $interactionEvents = ['pointerdown', 'touchstart', 'scroll', 'keydown', 'wheel'];

    /**
     * @var string URL of Yandex Metrika tag.js.
     */
    public string $tagUrl = 'https://mc.yandex.ru/metrika/tag.js';

    /**
     * @var bool Yandex Metrika Webvisor recording.
     */
    public bool $webvisor = false;

    /**
     * @var bool Yandex Metrika click map.
     */
    public bool $clickmap = true;

    /**
     * @var bool Yandex Metrika link tracking.
     */
    public bool $trackLinks = true;

    /**
     * @var bool|int Yandex Metrika bounce tracking.
     */
    public $accurateTrackBounce = true;

    /**
     * @var string|bool|null Yandex Metrika ecommerce data layer name (e.g. 'dataLayer').
     */
    public $ecommerce = null;

    /**
     * @var bool Yandex Metrika triggerEvent flag.
     */
    public bool $triggerEvent = false;

    /**
     * @var array Additional parameters passed directly into ym(id, 'init', ...).
     */
    public array $params = [];

    /**
     * @var array Allowed hostnames where Metrika should be loaded.
     * Empty array means all hosts are allowed.
     */
    public array $allowedHosts = [];

    /**
     * @var string|callable|null CSP nonce string or callable returning a nonce.
     */
    public $nonce = null;

    /**
     * @var bool Whether to render <noscript> pixel fallback.
     */
    public bool $renderNoscript = true;

    /**
     * @var bool Whether cookie/privacy consent is required before loading tag.js.
     */
    public bool $requireConsent = false;

    /**
     * @var string|null LocalStorage key used to check user consent.
     */
    public ?string $consentStorageKey = null;

    /**
     * @var string Window event dispatched when analytics consent is granted.
     */
    public string $consentEvent = 'cookie_analytics_enabled';

    /**
     * @var string Window event dispatched when analytics consent is rejected.
     */
    public string $rejectEvent = 'cookie_analytics_disabled';

    /**
     * @var bool Whether to embed loader JS inline (recommended for 0 extra HTTP requests) or use AssetBundle.
     */
    public bool $inline = true;

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        if ($this->enabled && empty($this->counterId)) {
            throw new InvalidConfigException('The "counterId" property must be set for MetrikaLoader.');
        }

        if ($this->counterId !== null) {
            $this->counterId = (int) $this->counterId;
        }

        if (!in_array($this->mode, [self::MODE_DEFERRED, self::MODE_IMMEDIATE], true)) {
            $this->mode = self::MODE_DEFERRED;
        }
    }

    /**
     * Checks if the loader is currently enabled and matches allowed hosts.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        if (!$this->enabled || empty($this->counterId)) {
            return false;
        }

        if (!empty($this->allowedHosts) && Yii::$app !== null && Yii::$app->has('request')) {
            $request = Yii::$app->request;
            if ($request instanceof Request) {
                try {
                    $currentHost = strtolower((string) $request->getHostName());
                    if (!empty($currentHost)) {
                        $normalizedHosts = array_map('strtolower', $this->allowedHosts);
                        if (!in_array($currentHost, $normalizedHosts, true)) {
                            return false;
                        }
                    }
                } catch (\Throwable $e) {
                    // Non-blocking fallback
                }
            }
        }

        return true;
    }

    /**
     * Compiles Metrika counter initialization options.
     *
     * @return array
     */
    public function getInitOptions(): array
    {
        $options = [
            'clickmap' => (bool) $this->clickmap,
            'trackLinks' => (bool) $this->trackLinks,
            'accurateTrackBounce' => is_numeric($this->accurateTrackBounce) ? (int) $this->accurateTrackBounce : (bool) $this->accurateTrackBounce,
            'webvisor' => (bool) $this->webvisor,
        ];

        if ($this->ecommerce !== null) {
            $options['ecommerce'] = $this->ecommerce;
        }

        if ($this->triggerEvent) {
            $options['triggerEvent'] = true;
        }

        foreach ($this->params as $k => $v) {
            $options[$k] = $v;
        }

        return $options;
    }

    /**
     * Compiles client config passed to JavaScript loader.
     *
     * @param ?View $view
     * @return array
     */
    public function getClientConfig(?View $view = null): array
    {
        return [
            'counterId' => $this->counterId,
            'mode' => $this->mode,
            'tagUrl' => $this->tagUrl,
            'maxDelay' => $this->maxDelay,
            'idleTimeout' => $this->idleTimeout,
            'loadOnInteraction' => $this->loadOnInteraction,
            'interactionEvents' => $this->interactionEvents,
            'initOptions' => $this->getInitOptions(),
            'allowedHosts' => $this->allowedHosts,
            'nonce' => $this->getEffectiveNonce($view),
            'requireConsent' => $this->requireConsent,
            'consentStorageKey' => $this->consentStorageKey,
            'consentEvent' => $this->consentEvent,
            'rejectEvent' => $this->rejectEvent,
        ];
    }

    /**
     * Resolves the effective CSP nonce.
     *
     * @param ?View $view
     * @return string
     */
    public function getEffectiveNonce(?View $view = null): string
    {
        if (is_callable($this->nonce)) {
            return (string) call_user_func($this->nonce, $view);
        }

        if (!empty($this->nonce)) {
            return (string) $this->nonce;
        }

        if ($view !== null && !empty($view->params['csp-nonce'])) {
            return (string) $view->params['csp-nonce'];
        }

        if ($view !== null && !empty($view->params['nonce'])) {
            return (string) $view->params['nonce'];
        }

        return '';
    }

    /**
     * Renders the head script tag containing early queue and deferred loader logic.
     *
     * @param ?View $view
     * @return string
     */
    public function renderHead(?View $view = null): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $config = $this->getClientConfig($view);
        $jsonConfig = Json::htmlEncode($config);
        $nonce = $this->getEffectiveNonce($view);
        $scriptAttrs = ['type' => 'text/javascript'];
        if (!empty($nonce)) {
            $scriptAttrs['nonce'] = $nonce;
        }

        if (!$this->inline) {
            if ($view !== null) {
                MetrikaAsset::register($view);
            }
            $js = "(function(){if(typeof initMetrikaLoader==='function'){initMetrikaLoader({$jsonConfig});}else{window.addEventListener('DOMContentLoaded',function(){if(typeof initMetrikaLoader==='function'){initMetrikaLoader({$jsonConfig});}});}})();";
            return Html::tag('script', $js, $scriptAttrs);
        }

        $loaderJsPath = __DIR__ . '/assets/metrika-loader.js';
        $loaderCode = file_exists($loaderJsPath) ? file_get_contents($loaderJsPath) : '';

        $js = "/* Universal Yii2 Metrika Loader (Deferred) */\n"
            . $loaderCode . "\n"
            . "if(typeof initMetrikaLoader==='function'){initMetrikaLoader({$jsonConfig});}\n";

        return Html::tag('script', $js, $scriptAttrs);
    }

    /**
     * Renders noscript image pixel fallback.
     *
     * @return string
     */
    public function renderNoscript(): string
    {
        if (!$this->isEnabled() || !$this->renderNoscript) {
            return '';
        }

        $pixelUrl = 'https://mc.yandex.ru/watch/' . $this->counterId;
        $img = Html::img($pixelUrl, [
            'style' => 'position:absolute; left:-9999px;',
            'alt' => '',
        ]);

        return "<noscript><div>{$img}</div></noscript>";
    }

    /**
     * Renders both head script and noscript fallback.
     *
     * @param ?View $view
     * @return string
     */
    public function render(?View $view = null): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        return $this->renderHead($view) . "\n" . $this->renderNoscript();
    }

    /**
     * Registers the loader directly into a Yii View object.
     *
     * @param View $view
     */
    public function register(View $view): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $config = $this->getClientConfig($view);
        $jsonConfig = Json::htmlEncode($config);

        if ($this->inline) {
            $loaderJsPath = __DIR__ . '/assets/metrika-loader.js';
            $loaderCode = file_exists($loaderJsPath) ? file_get_contents($loaderJsPath) : '';
            $js = $loaderCode . "\ninitMetrikaLoader({$jsonConfig});";
            $view->registerJs($js, View::POS_HEAD, 'yandex_metrika_loader_' . $this->counterId);
        } else {
            MetrikaAsset::register($view);
            $js = "initMetrikaLoader({$jsonConfig});";
            $view->registerJs($js, View::POS_HEAD, 'yandex_metrika_loader_' . $this->counterId);
        }

        if ($this->renderNoscript) {
            $pixelUrl = 'https://mc.yandex.ru/watch/' . $this->counterId;
            $noscript = "<noscript><div><img src=\"{$pixelUrl}\" style=\"position:absolute; left:-9999px;\" alt=\"\" /></div></noscript>";
            $view->registerJs($noscript, View::POS_BEGIN, 'yandex_metrika_noscript_' . $this->counterId);
        }
    }
}
