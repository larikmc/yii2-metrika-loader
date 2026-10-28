<?php

/** @var yii\web\View $this */
/** @var larikmc\metrika\MetrikaLoader $loader */
/** @var string $componentId */
/** @var bool $hasComponent */
/** @var array $settings */
/** @var array $stats */

use larikmc\metrika\MetrikaAdminAsset;
use yii\helpers\Html;

MetrikaAdminAsset::register($this);

$this->title = 'Яндекс.Метрика — Настройки и Аудит Скорости';
$counterId = $loader->counterId ?: 'Не задан';
$isDeferred = $loader->mode === \larikmc\metrika\MetrikaLoader::MODE_DEFERRED;
?>

<div class="ym-dashboard">
    <!-- Hero Banner -->
    <div class="ym-hero">
        <div class="ym-hero__left">
            <div class="ym-hero__badge">
                <span style="font-size: 14px;">⚡</span> Universal PageSpeed & Analytics Optimizer
            </div>
            <h1 class="ym-hero__title">Яндекс.Метрика — Настройки модуля</h1>
            <p class="ym-hero__desc">
                Модуль отложенной загрузки Яндекс.Метрики устраняет штрафы за Total Blocking Time (TBT) в Google PageSpeed Insights,
                при этом сохраняет 100% точность аналитики, запись целей и сессий.
            </p>
        </div>
        <div class="ym-hero__right">
            <div class="ym-hero__counter-box">
                <div class="ym-hero__counter-label">Счётчик Метрики</div>
                <div class="ym-hero__counter-id"><?= Html::encode($counterId) ?></div>
            </div>
            <?php if (!empty($loader->counterId) && is_numeric($loader->counterId)): ?>
                <a href="https://metrika.yandex.ru/dashboard?id=<?= urlencode((string)$loader->counterId) ?>"
                   target="_blank" rel="noopener noreferrer" class="ym-btn ym-btn--primary">
                    <span>Открыть кабинет Метрики</span>
                    <span style="font-size: 12px;">↗</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="ym-stats-grid">
        <div class="ym-stat-card">
            <div class="ym-stat-card__label">Режим работы</div>
            <div class="ym-stat-card__val" style="color: <?= $isDeferred ? 'var(--ym-success)' : 'var(--ym-warning)' ?>;">
                <?= $isDeferred ? 'Deferred' : 'Immediate' ?>
            </div>
            <div class="ym-stat-card__hint">
                <?= $isDeferred ? 'Отложенный запуск (PageSpeed 90-100)' : 'Синхронный запуск' ?>
            </div>
        </div>
        <div class="ym-stat-card">
            <div class="ym-stat-card__label">Состояние компонента</div>
            <div class="ym-stat-card__val">
                <?= $hasComponent ? 'Registered' : 'Standalone' ?>
            </div>
            <div class="ym-stat-card__hint">
                <?= $hasComponent ? "Компонент Yii::\$app->{$componentId}" : 'Экземпляр модуля' ?>
            </div>
        </div>
        <div class="ym-stat-card">
            <div class="ym-stat-card__label">Оптимальных параметров</div>
            <div class="ym-stat-card__val" style="color: var(--ym-success);">
                <?= $stats['optimal'] ?> / <?= $stats['total'] ?>
            </div>
            <div class="ym-stat-card__hint">
                <?= $stats['warnings'] === 0 ? 'Все параметры настроены идеально' : "Внимания требуют: {$stats['warnings']}" ?>
            </div>
        </div>
        <div class="ym-stat-card">
            <div class="ym-stat-card__label">Влияние на TBT</div>
            <div class="ym-stat-card__val" style="color: var(--ym-success);">
                ~0 мс
            </div>
            <div class="ym-stat-card__hint">
                Блокировка основного потока снята
            </div>
        </div>
    </div>

    <!-- Category Filter Navigation -->
    <div class="ym-nav">
        <button type="button" class="ym-nav__btn active" data-category="all">Все параметры (<?= count($settings) ?>)</button>
        <button type="button" class="ym-nav__btn" data-category="performance">⚡ Скорость & PSI</button>
        <button type="button" class="ym-nav__btn" data-category="features">📊 Сбор данных & Вебвизор</button>
        <button type="button" class="ym-nav__btn" data-category="analytics">🎯 Цели & Отказы</button>
        <button type="button" class="ym-nav__btn" data-category="security">🛡️ Домены & Безопасность</button>
        <button type="button" class="ym-nav__btn" data-category="privacy">🍪 Конфиденциальность</button>
    </div>

    <!-- Settings Detailed Cards -->
    <div class="ym-settings-list">
        <?php foreach ($settings as $key => $s): ?>
            <div class="ym-setting-card" data-category="<?= Html::encode($s['category']) ?>">
                <div class="ym-setting-card__left">
                    <div class="ym-setting-card__header">
                        <span class="ym-setting-card__code"><?= Html::encode($s['name']) ?></span>
                        <h3 class="ym-setting-card__title"><?= Html::encode($s['label']) ?></h3>
                        <?php if ($s['psiImpact'] !== 'none'): ?>
                            <span class="ym-pill ym-pill--<?= $s['psiImpact'] ?>" title="Влияние на скорость загрузки (PSI)">
                                PSI: <?= strtoupper($s['psiImpact']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="ym-setting-card__desc"><?= Html::encode($s['description']) ?></p>
                    <div class="ym-setting-card__why">
                        <strong>💡 Назначение & Рекомендация:</strong> <?= Html::encode($s['whyOptimal']) ?>
                    </div>
                </div>
                <div class="ym-setting-card__values">
                    <div class="ym-val-row">
                        <span class="ym-val-row__label">Текущее значение:</span>
                        <div class="ym-val-row__current">
                            <?= Html::encode($s['currentDisplay']) ?>
                            <?php if ($s['isOptimal']): ?>
                                <span class="ym-pill ym-pill--ok" title="Оптимальное значение">✓ OK</span>
                            <?php elseif ($s['severity'] === 'warning'): ?>
                                <span class="ym-pill ym-pill--warning" title="Требует внимания">! Внимание</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ym-val-row">
                        <span class="ym-val-row__label">Рекомендовано:</span>
                        <div class="ym-val-row__optimal"><?= Html::encode($s['optimal']) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Developer Cheat Sheet -->
    <div class="ym-section-title">
        <span>📖</span> Шпаргалка для разработчика
    </div>

    <div class="ym-code-card">
        <button type="button" class="ym-copy-btn" data-target="codeLayout">Копировать</button>
        <div style="font-size: 12px; color: var(--ym-text-subtle); margin-bottom: 8px; text-transform: uppercase; font-weight: 700;">
            1. Подключение виджета в layout (frontend/views/layouts/main.php)
        </div>
        <pre id="codeLayout">&lt;!-- Universal Yandex Metrika Loader --&gt;
&lt;?= \larikmc\metrika\MetrikaWidget::widget([
    'counterId' =&gt; <?= is_numeric($counterId) ? $counterId : 113667541 ?>,
    'mode' =&gt; 'deferred',
    'webvisor' =&gt; true,
    'clickmap' =&gt; true,
    'trackLinks' =&gt; true,
    'accurateTrackBounce' =&gt; true,
    'ecommerce' =&gt; 'dataLayer',
    'params' =&gt; [
        'ssr' =&gt; true,
    ],
]) ?&gt;</pre>
    </div>

    <div class="ym-code-card">
        <button type="button" class="ym-copy-btn" data-target="codeGoals">Копировать</button>
        <div style="font-size: 12px; color: var(--ym-text-subtle); margin-bottom: 8px; text-transform: uppercase; font-weight: 700;">
            2. Отправка целей в JavaScript (работает моментально даже до загрузки скрипта)
        </div>
        <pre id="codeGoals">// Отправка цели конверсии (например, после отправки заявки)
ym(<?= is_numeric($counterId) ? $counterId : '113667541' ?>, 'reachGoal', 'lead_form_submitted', {
    formName: 'Заказ звонка',
    service: 'Создание сайтов'
});

// Достижение цели через устаревший синтаксис (модуль автоматически поддерживает проксирование):
window.yaCounter<?= is_numeric($counterId) ? $counterId : '113667541' ?>.reachGoal('target_name');</pre>
    </div>

    <div class="ym-code-card">
        <button type="button" class="ym-copy-btn" data-target="codeConfig">Копировать</button>
        <div style="font-size: 12px; color: var(--ym-text-subtle); margin-bottom: 8px; text-transform: uppercase; font-weight: 700;">
            3. Конфигурация компонента в common/config/main.php
        </div>
        <pre id="codeConfig">'components' =&gt; [
    'metrika' =&gt; [
        'class' =&gt; \larikmc\metrika\MetrikaLoader::class,
        'counterId' =&gt; <?= is_numeric($counterId) ? $counterId : 113667541 ?>,
        'mode' =&gt; \larikmc\metrika\MetrikaLoader::MODE_DEFERRED,
        'enabled' =&gt; !YII_DEBUG || YII_ENV_PROD,
        'maxDelay' =&gt; 3500,
        'idleTimeout' =&gt; 1500,
        'loadOnInteraction' =&gt; true,
        'webvisor' =&gt; false, // Выключите, если не просматриваете видеозаписи
        'clickmap' =&gt; true,
        'trackLinks' =&gt; true,
        'accurateTrackBounce' =&gt; true,
        'ecommerce' =&gt; 'dataLayer',
    ],
],</pre>
    </div>
</div>

<div id="ymToast" class="ym-toast"></div>
