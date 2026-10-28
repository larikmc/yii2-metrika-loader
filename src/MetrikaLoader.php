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
 * Supports persistent JSON storage so settings can be changed
 * directly via the admin panel without modifying PHP configs.
 *
 * @author LarikMC <https://64web.ru>
 * @license MIT
 */
class MetrikaLoader extends Component
{
    public const MODE_DEFERRED = 'deferred';
    public const MODE_IMMEDIATE = 'immediate';

    /**
     * @var int|string|null Yandex Metrika Counter ID (e.g. 113667541).
     */
    public $counterId;

    /**
     * @var string Loading mode: 'deferred' (optimized) or 'immediate' (standard fallback).
     */
    public string $mode = self::MODE_DEFERRED;

    /**
     * @var bool Whether Metrika is enabled.
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
     * @var string|null Path or alias to persistent settings file (e.g. '@common/config/metrika.json' or '@runtime/metrika.json').
     * If null, automatically resolves to '@common/config/metrika.json' if '@common/config' alias exists,
     * otherwise '@runtime/metrika.json'.
     */
    public ?string $storageFile = null;

    /**
     * @var bool Whether to throw InvalidConfigException if counterId is empty when enabled is true.
     * Defaults to false to allow opening the admin panel to configure the counter ID.
     */
    public bool $throwOnEmptyCounterId = false;

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        $this->loadStoredSettings();

        if ($this->throwOnEmptyCounterId && $this->enabled && empty($this->counterId)) {
            throw new InvalidConfigException('The "counterId" property must be set for MetrikaLoader.');
        }

        if ($this->counterId !== null && is_numeric($this->counterId)) {
            $this->counterId = (int) $this->counterId;
        }

        if (!in_array($this->mode, [self::MODE_DEFERRED, self::MODE_IMMEDIATE], true)) {
            $this->mode = self::MODE_DEFERRED;
        }
    }

    /**
     * Resolves absolute file path to the storage file.
     *
     * @return string|null
     */
    public function resolveStorageFile(): ?string
    {
        if ($this->storageFile !== null) {
            return Yii::getAlias($this->storageFile);
        }

        if (Yii::$app !== null) {
            if (Yii::getAlias('@common/config', false) !== false) {
                return Yii::getAlias('@common/config/metrika.json');
            }
            if (Yii::getAlias('@runtime', false) !== false) {
                return Yii::getAlias('@runtime/metrika.json');
            }
            if (Yii::getAlias('@app/config', false) !== false) {
                return Yii::getAlias('@app/config/metrika.json');
            }
        }

        return null;
    }

    /**
     * Returns the relative or friendly path to the storage file for UI display.
     *
     * @return string|null
     */
    public function getStorageFilePath(): ?string
    {
        $file = $this->resolveStorageFile();
        if ($file === null) {
            return null;
        }

        if (Yii::$app !== null && Yii::getAlias('@app', false) !== false) {
            $appPath = Yii::getAlias('@app');
            $rootPath = dirname($appPath);
            if (str_starts_with($file, $rootPath)) {
                return ltrim(substr($file, strlen($rootPath)), '/\\');
            }
        }

        return $file;
    }

    /**
     * Checks if the storage directory/file is writable.
     *
     * @return bool
     */
    public function isStorageWritable(): bool
    {
        $file = $this->resolveStorageFile();
        if ($file === null) {
            return false;
        }

        if (file_exists($file)) {
            return is_writable($file);
        }

        $dir = dirname($file);
        return is_dir($dir) ? is_writable($dir) : (is_dir(dirname($dir)) && is_writable(dirname($dir)));
    }

    /**
     * Loads stored settings from the JSON storage file into this instance.
     *
     * @return bool
     */
    public function loadStoredSettings(): bool
    {
        $file = $this->resolveStorageFile();
        if ($file === null || !is_file($file) || !is_readable($file)) {
            return false;
        }

        try {
            $content = file_get_contents($file);
            if ($content === false || trim($content) === '') {
                return false;
            }
            $data = Json::decode($content);
            if (is_array($data)) {
                $this->applySettingsArray($data);
                return true;
            }
        } catch (\Throwable $e) {
            Yii::warning('Failed to load Metrika settings from storage: ' . $e->getMessage(), __METHOD__);
        }

        return false;
    }

    /**
     * Applies an associative array of settings to the loader properties.
     *
     * @param array $data
     */
    public function applySettingsArray(array $data): void
    {
        if (array_key_exists('counterId', $data)) {
            $cid = trim((string)$data['counterId']);
            $this->counterId = $cid !== '' ? (is_numeric($cid) ? (int)$cid : $cid) : null;
        }

        if (array_key_exists('enabled', $data)) {
            $this->enabled = (bool)$data['enabled'];
        }

        if (array_key_exists('mode', $data) && in_array($data['mode'], [self::MODE_DEFERRED, self::MODE_IMMEDIATE], true)) {
            $this->mode = $data['mode'];
        }

        if (array_key_exists('webvisor', $data)) {
            $this->webvisor = (bool)$data['webvisor'];
        }

        if (array_key_exists('clickmap', $data)) {
            $this->clickmap = (bool)$data['clickmap'];
        }

        if (array_key_exists('trackLinks', $data)) {
            $this->trackLinks = (bool)$data['trackLinks'];
        }

        if (array_key_exists('accurateTrackBounce', $data)) {
            $this->accurateTrackBounce = is_numeric($data['accurateTrackBounce']) ? (int)$data['accurateTrackBounce'] : (bool)$data['accurateTrackBounce'];
        }

        if (array_key_exists('ecommerce', $data)) {
            $ec = is_string($data['ecommerce']) ? trim($data['ecommerce']) : $data['ecommerce'];
            $this->ecommerce = ($ec === '' || $ec === null) ? null : $ec;
        }

        if (array_key_exists('allowedHosts', $data)) {
            if (is_string($data['allowedHosts'])) {
                $hosts = array_filter(array_map('trim', explode(',', $data['allowedHosts'])));
                $this->allowedHosts = array_values($hosts);
            } elseif (is_array($data['allowedHosts'])) {
                $this->allowedHosts = array_values(array_filter(array_map('trim', $data['allowedHosts'])));
            }
        }

        if (array_key_exists('maxDelay', $data) && is_numeric($data['maxDelay'])) {
            $this->maxDelay = max(500, min(30000, (int)$data['maxDelay']));
        }

        if (array_key_exists('idleTimeout', $data) && is_numeric($data['idleTimeout'])) {
            $this->idleTimeout = max(200, min(10000, (int)$data['idleTimeout']));
        }

        if (array_key_exists('loadOnInteraction', $data)) {
            $this->loadOnInteraction = (bool)$data['loadOnInteraction'];
        }

        if (array_key_exists('requireConsent', $data)) {
            $this->requireConsent = (bool)$data['requireConsent'];
        }

        if (array_key_exists('renderNoscript', $data)) {
            $this->renderNoscript = (bool)$data['renderNoscript'];
        }

        if (array_key_exists('inline', $data)) {
            $this->inline = (bool)$data['inline'];
        }
    }

    /**
     * Persists settings to storage file and flushes cache if available.
     *
     * @param array $data
     * @return bool
     * @throws \RuntimeException
     */
    public function saveSettings(array $data): bool
    {
        $this->applySettingsArray($data);

        $file = $this->resolveStorageFile();
        if ($file === null) {
            throw new \RuntimeException('Cannot resolve storage file for Metrika settings. Set "storageFile" in component config.');
        }

        $dir = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" could not be created.', $dir));
        }

        $payload = [
            'counterId' => $this->counterId,
            'enabled' => $this->enabled,
            'mode' => $this->mode,
            'webvisor' => $this->webvisor,
            'clickmap' => $this->clickmap,
            'trackLinks' => $this->trackLinks,
            'accurateTrackBounce' => $this->accurateTrackBounce,
            'ecommerce' => $this->ecommerce,
            'allowedHosts' => $this->allowedHosts,
            'maxDelay' => $this->maxDelay,
            'idleTimeout' => $this->idleTimeout,
            'loadOnInteraction' => $this->loadOnInteraction,
            'requireConsent' => $this->requireConsent,
            'renderNoscript' => $this->renderNoscript,
            'inline' => $this->inline,
            'updatedAt' => date('c'),
        ];

        $json = Json::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $tmpFile = $file . '.tmp.' . bin2hex(random_bytes(4));
        if (file_put_contents($tmpFile, $json . PHP_EOL, LOCK_EX) === false) {
            throw new \RuntimeException("Failed to write temporary settings file: {$tmpFile}");
        }

        if (!@rename($tmpFile, $file)) {
            @unlink($tmpFile);
            if (file_put_contents($file, $json . PHP_EOL, LOCK_EX) === false) {
                throw new \RuntimeException("Failed to write settings file: {$file}");
            }
        }

        if (Yii::$app !== null && Yii::$app->has('cache') && Yii::$app->cache !== null) {
            try {
                Yii::$app->cache->flush();
            } catch (\Throwable $e) {
                // Non-blocking
            }
        }

        return true;
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