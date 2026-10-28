<?php

/** @var yii\web\View $this */
/** @var larikmc\metrika\MetrikaLoader $loader */
/** @var string $componentId */
/** @var bool $hasComponent */
/** @var array $primary */
/** @var array $technical */

use larikmc\metrika\MetrikaAdminAsset;
use yii\helpers\Html;

MetrikaAdminAsset::register($this);

$this->title = 'Яндекс.Метрика — Настройки';
$counterId = $loader->counterId ?: 'Не задан';
$isDeferred = $loader->mode === \larikmc\metrika\MetrikaLoader::MODE_DEFERRED;
?>

<div class="ym-dashboard">
    <!-- Top Hero Bar -->
    <div class="ym-hero">
        <div class="ym-hero__left">
            <div class="ym-hero__badge">
                <span>⚡</span> Ускоритель Яндекс.Метрики
            </div>
            <h1 class="ym-hero__title">Счётчик № <?= Html::encode($counterId) ?></h1>
            <p class="ym-hero__desc">
                Модуль отложенной загрузки исключает торможение сайта: первый экран открывается мгновенно (PageSpeed 90–100, TBT = 0 мс),
                а все цели и клики фиксируются без задержек.
            </p>
        </div>
        <div class="ym-hero__right">
            <?php if (!empty($loader->counterId) && is_numeric($loader->counterId)): ?>
                <a href="https://metrika.yandex.ru/dashboard?id=<?= urlencode((string)$loader->counterId) ?>"
                   target="_blank" rel="noopener noreferrer" class="ym-btn ym-btn--primary">
                    <span>Перейти в кабинет Метрики</span>
                    <span>↗</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="ym-stats-grid">
        <div class="ym-stat-card">
            <div class="ym-stat-card__label">Режим загрузки</div>
            <div class="ym-stat-card__val" style="color: <?= $isDeferred ? 'var(--ym-success)' : 'var(--ym-warning)' ?>;">
                <?= $isDeferred ? 'Отложенный' : 'Обычный' ?>
            </div>
            <div class="ym-stat-card__hint">
                <?= $isDeferred ? 'Максимальная скорость (PageSpeed 90-100)' : 'Синхронный запуск' ?>
            </div>
        </div>
        <div class="ym-stat-card">
            <div class="ym-stat-card__label">Влияние на скорость</div>
            <div class="ym-stat-card__val" style="color: var(--ym-success);">
                0 мс
            </div>
            <div class="ym-stat-card__hint">
                Основной поток браузера свободен
            </div>
        </div>
        <div class="ym-stat-card">
            <div class="ym-stat-card__label">Фиксация целей</div>
            <div class="ym-stat-card__val" style="color: var(--ym-success);">
                100%
            </div>
            <div class="ym-stat-card__hint">
                Очередь window.ym активна сразу
            </div>
        </div>
    </div>

    <!-- Primary Features Cards Grid -->
    <div class="ym-section-title">
        <span>📌</span> Основные возможности
    </div>

    <div class="ym-features-grid">
        <?php foreach ($primary as $item): ?>
            <div class="ym-feature-card">
                <div class="ym-feature-card__top">
                    <span class="ym-feature-card__icon"><?= $item['icon'] ?></span>
                    <div class="ym-feature-card__meta">
                        <div class="ym-feature-card__title"><?= Html::encode($item['title']) ?></div>
                        <div class="ym-feature-card__value"><?= Html::encode($item['value']) ?></div>
                    </div>
                    <span class="ym-pill <?= $item['isGood'] ? 'ym-pill--ok' : 'ym-pill--warning' ?>">
                        <?= Html::encode($item['status']) ?>
                    </span>
                </div>
                <div class="ym-feature-card__summary">
                    <?= Html::encode($item['summary']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Technical Settings Accordion -->
    <div class="ym-tech-box">
        <details class="ym-details">
            <summary class="ym-details__summary">
                <span>⚙️ Тонкие технические параметры скорости (таймауты и события)</span>
                <span class="ym-details__arrow">▼</span>
            </summary>
            <div class="ym-details__body">
                <table class="ym-tech-table">
                    <thead>
                        <tr>
                            <th>Параметр</th>
                            <th>Текущее значение</th>
                            <th>Рекомендация</th>
                            <th>Пояснение</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($technical as $t): ?>
                            <tr>
                                <td><code><?= Html::encode($t['param']) ?></code></td>
                                <td><strong><?= Html::encode($t['value']) ?></strong></td>
                                <td><span class="ym-tech-table__opt"><?= Html::encode($t['optimal']) ?></span></td>
                                <td class="ym-tech-table__desc"><?= Html::encode($t['desc']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </details>
    </div>

    <!-- Developer Cheat Sheet -->
    <div class="ym-section-title">
        <span>📖</span> Шпаргалка: как отправлять цели в Метрику
    </div>

    <div class="ym-code-card">
        <button type="button" class="ym-copy-btn" data-target="codeGoals">Копировать</button>
        <div style="font-size: 13px; font-weight: 700; margin-bottom: 8px;">
            Вызов цели при отправке формы или клике (JavaScript):
        </div>
        <pre id="codeGoals">// Отправка цели конверсии (работает сразу, даже до загрузки скрипта)
ym(<?= is_numeric($counterId) ? $counterId : '113667541' ?>, 'reachGoal', 'lead_form_submitted');</pre>
    </div>

    <div class="ym-code-card">
        <button type="button" class="ym-copy-btn" data-target="codeLayout">Копировать</button>
        <div style="font-size: 13px; font-weight: 700; margin-bottom: 8px;">
            Вызов счётчика в шаблоне сайта (frontend/views/layouts/main.php):
        </div>
        <pre id="codeLayout">&lt;?= \larikmc\metrika\MetrikaWidget::widget() ?&gt;</pre>
    </div>
</div>

<div id="ymToast" class="ym-toast"></div>
