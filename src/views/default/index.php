<?php

/** @var yii\web\View $this */
/** @var larikmc\metrika\MetrikaLoader $loader */
/** @var string $componentId */
/** @var bool $hasComponent */

use larikmc\metrika\MetrikaAdminAsset;
use yii\helpers\Html;

MetrikaAdminAsset::register($this);

$this->title = 'Яндекс.Метрика — Настройки';
$counterId = $loader->counterId;
$isDeferred = $loader->mode === \larikmc\metrika\MetrikaLoader::MODE_DEFERRED;
?>

<div class="ym-dashboard">
    <?php if (Yii::$app->session->hasFlash('metrika_success') || Yii::$app->session->hasFlash('success')): ?>
        <div class="ym-alert ym-alert--success">
            <span class="ym-alert__icon">✅</span>
            <div class="ym-alert__content">
                <strong>Успешно!</strong>
                <?= Html::encode(Yii::$app->session->getFlash('metrika_success') ?: Yii::$app->session->getFlash('success')) ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (Yii::$app->session->hasFlash('metrika_error') || Yii::$app->session->hasFlash('error')): ?>
        <div class="ym-alert ym-alert--error">
            <span class="ym-alert__icon">❌</span>
            <div class="ym-alert__content">
                <strong>Ошибка!</strong>
                <?= Html::encode(Yii::$app->session->getFlash('metrika_error') ?: Yii::$app->session->getFlash('error')) ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="ym-form" id="ymSettingsForm">
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

        <!-- Top Hero Bar -->
        <div class="ym-hero">
            <div class="ym-hero__left">
                <div class="ym-hero__badge">
                    <span>⚡</span> Ускоритель Яндекс.Метрики
                </div>
                <h1 class="ym-hero__title">
                    <?= !empty($counterId) ? 'Счётчик № ' . Html::encode($counterId) : 'Настройка счётчика Яндекс.Метрики' ?>
                </h1>
                <p class="ym-hero__desc">
                    Управляйте параметрами Яндекс.Метрики прямо из панели управления. 
                    Отложенная загрузка исключает торможение сайта: первый экран открывается мгновенно (PageSpeed 90–100, TBT = 0 мс),
                    а клики, звонки и цели фиксируются без задержек.
                </p>
            </div>
            <div class="ym-hero__right">
                <?php if (!empty($loader->counterId) && is_numeric($loader->counterId)): ?>
                    <a href="https://metrika.yandex.ru/dashboard?id=<?= urlencode((string)$loader->counterId) ?>"
                       target="_blank" rel="noopener noreferrer" class="ym-btn ym-btn--outline" title="Открыть в Яндекс.Метрике">
                        <span>В кабинет Метрики</span>
                        <span>↗</span>
                    </a>
                <?php endif; ?>
                <button type="submit" class="ym-btn ym-btn--primary">
                    <span>💾 Сохранить настройки</span>
                </button>
            </div>
        </div>

        <!-- Quick Status Stats -->
        <div class="ym-stats-grid">
            <div class="ym-stat-card">
                <div class="ym-stat-card__label">Статус работы</div>
                <div class="ym-stat-card__val" style="color: <?= $loader->enabled && !empty($loader->counterId) ? 'var(--ym-success)' : 'var(--ym-warning)' ?>;">
                    <?= $loader->enabled && !empty($loader->counterId) ? 'Активен' : ($loader->enabled ? 'Ждёт номер' : 'Отключен') ?>
                </div>
                <div class="ym-stat-card__hint">
                    <?= $loader->enabled && !empty($loader->counterId) ? 'Код загружается на сайте' : 'Сбор статистики выключен' ?>
                </div>
            </div>
            <div class="ym-stat-card">
                <div class="ym-stat-card__label">Режим загрузки</div>
                <div class="ym-stat-card__val" style="color: <?= $isDeferred ? 'var(--ym-success)' : 'var(--ym-warning)' ?>;">
                    <?= $isDeferred ? 'Отложенный' : 'Обычный' ?>
                </div>
                <div class="ym-stat-card__hint">
                    <?= $isDeferred ? 'PageSpeed 90–100, TBT = 0 мс' : 'Синхронная загрузка' ?>
                </div>
            </div>
            <div class="ym-stat-card">
                <div class="ym-stat-card__label">Фиксация целей</div>
                <div class="ym-stat-card__val" style="color: var(--ym-success);">
                    100%
                </div>
                <div class="ym-stat-card__hint">
                    Очередь ym() готова сразу до tag.js
                </div>
            </div>
        </div>

        <!-- Section 1: Главные параметры -->
        <div class="ym-section-title">
            <span>⚙️</span> Основные параметры счётчика
        </div>

        <div class="ym-config-card">
            <div class="ym-config-grid">
                <!-- Counter ID Field -->
                <div class="ym-field ym-field--counter">
                    <label class="ym-label" for="ymCounterId">
                        Номер счётчика Яндекс.Метрики
                        <span class="ym-label__required">*</span>
                    </label>
                    <div class="ym-input-group">
                        <input type="text"
                               id="ymCounterId"
                               name="MetrikaSetting[counterId]"
                               value="<?= Html::encode($loader->counterId ?? '') ?>"
                               placeholder="Например: 113667541"
                               class="ym-input ym-input--counter"
                               autocomplete="off"
                               required>
                        <?php if (!empty($loader->counterId) && is_numeric($loader->counterId)): ?>
                            <a href="https://metrika.yandex.ru/dashboard?id=<?= urlencode((string)$loader->counterId) ?>"
                               target="_blank" rel="noopener noreferrer" class="ym-input-group__btn" title="Проверить в Яндекс.Метрике">
                                В Метрику ↗
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="ym-field__hint">
                        Укажите 8-значный ID вашего счётчика. Найти его можно на странице <a href="https://metrika.yandex.ru/list" target="_blank" rel="noopener">metrika.yandex.ru</a> в шапке счётчика.
                    </div>
                </div>

                <!-- Enabled Switch Field -->
                <div class="ym-field ym-field--switch">
                    <label class="ym-label">Активность кода на страницах</label>
                    <div class="ym-toggle-wrapper">
                        <input type="hidden" name="MetrikaSetting[enabled]" value="0">
                        <label class="ym-switch">
                            <input type="checkbox" name="MetrikaSetting[enabled]" value="1" id="ymEnabled" <?= $loader->enabled ? 'checked' : '' ?>>
                            <span class="ym-switch__slider"></span>
                        </label>
                        <label for="ymEnabled" class="ym-toggle-label" id="ymEnabledLabel">
                            <?= $loader->enabled ? 'Включен на сайте' : 'Отключен' ?>
                        </label>
                    </div>
                    <div class="ym-field__hint">
                        Если выключить — Метрика перестанет загружаться на страницах сайта без необходимости удалять код из шаблонов.
                    </div>
                </div>
            </div>

            <!-- Mode Selector -->
            <div class="ym-field ym-field--mode" style="margin-top: 20px;">
                <label class="ym-label">Режим загрузки скрипта Метрики</label>
                <div class="ym-mode-grid">
                    <label class="ym-mode-card <?= $isDeferred ? 'is-active' : '' ?>">
                        <input type="radio" name="MetrikaSetting[mode]" value="<?= \larikmc\metrika\MetrikaLoader::MODE_DEFERRED ?>" <?= $isDeferred ? 'checked' : '' ?>>
                        <div class="ym-mode-card__content">
                            <div class="ym-mode-card__header">
                                <span class="ym-mode-card__icon">⚡</span>
                                <span class="ym-mode-card__title">Отложенный (Deferred)</span>
                                <span class="ym-pill ym-pill--ok">Рекомендуется</span>
                            </div>
                            <div class="ym-mode-card__desc">
                                Скрипт загружается в свободную паузу браузера (requestIdleCallback) или по первому касанию/скроллу. 
                                <strong>Google PageSpeed 90–100</strong>, TBT = 0 мс, первый экран открывается мгновенно. 
                                Все вызовы <code>ym(id, 'reachGoal')</code> буферизируются и срабатывают без потерь!
                            </div>
                        </div>
                    </label>

                    <label class="ym-mode-card <?= !$isDeferred ? 'is-active' : '' ?>">
                        <input type="radio" name="MetrikaSetting[mode]" value="<?= \larikmc\metrika\MetrikaLoader::MODE_IMMEDIATE ?>" <?= !$isDeferred ? 'checked' : '' ?>>
                        <div class="ym-mode-card__content">
                            <div class="ym-mode-card__header">
                                <span class="ym-mode-card__icon">⏱️</span>
                                <span class="ym-mode-card__title">Стандартный (Immediate)</span>
                                <span class="ym-pill ym-pill--warning">Обычный</span>
                            </div>
                            <div class="ym-mode-card__desc">
                                Классическая синхронная загрузка tag.js сразу при старте страницы. 
                                Может снижать показатели PageSpeed и задерживать отрисовку контента на мобильных телефонах.
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Section 2: Функции и отслеживание -->
        <div class="ym-section-title">
            <span>🛠️</span> Функции и возможности счётчика
        </div>

        <div class="ym-features-grid">
            <!-- Webvisor -->
            <div class="ym-feature-card">
                <div class="ym-feature-card__top">
                    <span class="ym-feature-card__icon">🎥</span>
                    <div class="ym-feature-card__meta">
                        <div class="ym-feature-card__title">Вебвизор 2.0</div>
                        <div class="ym-feature-card__sub">Запись действий посетителей</div>
                    </div>
                    <div class="ym-switch-box">
                        <input type="hidden" name="MetrikaSetting[webvisor]" value="0">
                        <label class="ym-switch">
                            <input type="checkbox" name="MetrikaSetting[webvisor]" value="1" <?= $loader->webvisor ? 'checked' : '' ?>>
                            <span class="ym-switch__slider"></span>
                        </label>
                    </div>
                </div>
                <div class="ym-feature-card__summary">
                    Записывает видео визитов, клики и движения курсора мыши. Включайте, если вы просматриваете видеозаписи в интерфейсе Яндекс.Метрики.
                </div>
            </div>

            <!-- Clickmap -->
            <div class="ym-feature-card">
                <div class="ym-feature-card__top">
                    <span class="ym-feature-card__icon">🗺️</span>
                    <div class="ym-feature-card__meta">
                        <div class="ym-feature-card__title">Карта кликов</div>
                        <div class="ym-feature-card__sub">Тепловая карта нажатий</div>
                    </div>
                    <div class="ym-switch-box">
                        <input type="hidden" name="MetrikaSetting[clickmap]" value="0">
                        <label class="ym-switch">
                            <input type="checkbox" name="MetrikaSetting[clickmap]" value="1" <?= $loader->clickmap ? 'checked' : '' ?>>
                            <span class="ym-switch__slider"></span>
                        </label>
                    </div>
                </div>
                <div class="ym-feature-card__summary">
                    Строит тепловую карту кликов по кнопкам, ссылкам и элементам страницы. Показывает, куда чаще всего кликают пользователи.
                </div>
            </div>

            <!-- Track Links -->
            <div class="ym-feature-card">
                <div class="ym-feature-card__top">
                    <span class="ym-feature-card__icon">🔗</span>
                    <div class="ym-feature-card__meta">
                        <div class="ym-feature-card__title">Отслеживание ссылок</div>
                        <div class="ym-feature-card__sub">Телефоны, почта, файлы</div>
                    </div>
                    <div class="ym-switch-box">
                        <input type="hidden" name="MetrikaSetting[trackLinks]" value="0">
                        <label class="ym-switch">
                            <input type="checkbox" name="MetrikaSetting[trackLinks]" value="1" <?= $loader->trackLinks ? 'checked' : '' ?>>
                            <span class="ym-switch__slider"></span>
                        </label>
                    </div>
                </div>
                <div class="ym-feature-card__summary">
                    Автоматически фиксирует клики по телефонным номерам (tel:), электронной почте (mailto:) и скачивание файлов (.pdf, .zip, .xlsx).
                </div>
            </div>

            <!-- Accurate Track Bounce -->
            <div class="ym-feature-card">
                <div class="ym-feature-card__top">
                    <span class="ym-feature-card__icon">⏱️</span>
                    <div class="ym-feature-card__meta">
                        <div class="ym-feature-card__title">Точный отказ (15 сек)</div>
                        <div class="ym-feature-card__sub">Честная статистика отказов</div>
                    </div>
                    <div class="ym-switch-box">
                        <input type="hidden" name="MetrikaSetting[accurateTrackBounce]" value="0">
                        <label class="ym-switch">
                            <input type="checkbox" name="MetrikaSetting[accurateTrackBounce]" value="1" <?= $loader->accurateTrackBounce ? 'checked' : '' ?>>
                            <span class="ym-switch__slider"></span>
                        </label>
                    </div>
                </div>
                <div class="ym-feature-card__summary">
                    Не считает визит «отказом», если человек читал страницу более 15 секунд, даже если он не переходил на другие страницы сайта.
                </div>
            </div>

            <!-- Ecommerce -->
            <div class="ym-feature-card ym-feature-card--wide">
                <div class="ym-feature-card__top">
                    <span class="ym-feature-card__icon">🛒</span>
                    <div class="ym-feature-card__meta">
                        <div class="ym-feature-card__title">Электронная коммерция (Ecommerce)</div>
                        <div class="ym-feature-card__sub">Имя JavaScript-массива dataLayer для товаров и заказов</div>
                    </div>
                </div>
                <div class="ym-feature-card__input">
                    <input type="text"
                           name="MetrikaSetting[ecommerce]"
                           value="<?= Html::encode($loader->ecommerce ?? '') ?>"
                           placeholder="dataLayer (оставьте пустым, если электронная коммерция не нужна)"
                           class="ym-input">
                </div>
                <div class="ym-feature-card__summary">
                    Сбор данных о корзине, оформленных заказах, просмотрах товаров и выручке через массив <code>window.dataLayer</code>. Если интернет-магазин не используется, оставьте поле пустым.
                </div>
            </div>

            <!-- Allowed Hosts -->
            <div class="ym-feature-card ym-feature-card--wide">
                <div class="ym-feature-card__top">
                    <span class="ym-feature-card__icon">🛡️</span>
                    <div class="ym-feature-card__meta">
                        <div class="ym-feature-card__title">Фильтр рабочих доменов</div>
                        <div class="ym-feature-card__sub">Защита от сбора мусорной статистики с локальных копий</div>
                    </div>
                </div>
                <div class="ym-feature-card__input">
                    <input type="text"
                           name="MetrikaSetting[allowedHosts]"
                           value="<?= Html::encode(implode(', ', $loader->allowedHosts)) ?>"
                           placeholder="mysite.ru, www.mysite.ru (оставьте пустым для работы на всех доменах)"
                           class="ym-input">
                </div>
                <div class="ym-feature-card__summary">
                    Укажите домены через запятую, на которых Метрика должна собирать данные. Метрика не будет срабатывать на тестовых серверах, доменах <code>.loc</code> и <code>localhost</code>.
                </div>
            </div>
        </div>

        <!-- Section 3: Тонкие технические параметры -->
        <div class="ym-tech-box">
            <details class="ym-details">
                <summary class="ym-details__summary">
                    <span>⚙️ Тонкие технические параметры скорости (таймауты и события)</span>
                    <span class="ym-details__arrow">▼</span>
                </summary>
                <div class="ym-details__body">
                    <div class="ym-tech-grid">
                        <div class="ym-tech-item">
                            <label class="ym-label">Максимальная задержка (мс)</label>
                            <input type="number" name="MetrikaSetting[maxDelay]" value="<?= (int)$loader->maxDelay ?>" min="500" max="30000" step="100" class="ym-input ym-input--sm">
                            <div class="ym-field__hint">Рекомендовано: 3500 мс. Страховочный таймаут принудительного запуска tag.js, если пользователь не двигал мышь и не скроллил.</div>
                        </div>

                        <div class="ym-tech-item">
                            <label class="ym-label">Таймаут простоя (Idle, мс)</label>
                            <input type="number" name="MetrikaSetting[idleTimeout]" value="<?= (int)$loader->idleTimeout ?>" min="200" max="10000" step="100" class="ym-input ym-input--sm">
                            <div class="ym-field__hint">Рекомендовано: 1500 мс. Запуск скрипта в свободную паузу браузера (requestIdleCallback) после завершения отрисовки экрана.</div>
                        </div>

                        <div class="ym-tech-item">
                            <label class="ym-label">Старт по касанию / скроллу</label>
                            <div class="ym-toggle-wrapper">
                                <input type="hidden" name="MetrikaSetting[loadOnInteraction]" value="0">
                                <label class="ym-switch">
                                    <input type="checkbox" name="MetrikaSetting[loadOnInteraction]" value="1" <?= $loader->loadOnInteraction ? 'checked' : '' ?>>
                                    <span class="ym-switch__slider"></span>
                                </label>
                                <span class="ym-toggle-label">Мгновенный запуск при взаимодействии</span>
                            </div>
                            <div class="ym-field__hint">Рекомендовано: Включено. Запускает скрипт при первом движении мыши, касании экрана, нажатии клавиш или скролле.</div>
                        </div>

                        <div class="ym-tech-item">
                            <label class="ym-label">Ожидание согласия куки-баннера (Consent)</label>
                            <div class="ym-toggle-wrapper">
                                <input type="hidden" name="MetrikaSetting[requireConsent]" value="0">
                                <label class="ym-switch">
                                    <input type="checkbox" name="MetrikaSetting[requireConsent]" value="1" <?= $loader->requireConsent ? 'checked' : '' ?>>
                                    <span class="ym-switch__slider"></span>
                                </label>
                                <span class="ym-toggle-label">Блокировать до согласия</span>
                            </div>
                            <div class="ym-field__hint">По умолчанию выключено. Включайте только для сайтов в юрисдикции ЕС/GDPR, требующих предварительного согласия на аналитику.</div>
                        </div>

                        <div class="ym-tech-item">
                            <label class="ym-label">Пиксель fallback (&lt;noscript&gt;)</label>
                            <div class="ym-toggle-wrapper">
                                <input type="hidden" name="MetrikaSetting[renderNoscript]" value="0">
                                <label class="ym-switch">
                                    <input type="checkbox" name="MetrikaSetting[renderNoscript]" value="1" <?= $loader->renderNoscript ? 'checked' : '' ?>>
                                    <span class="ym-switch__slider"></span>
                                </label>
                                <span class="ym-toggle-label">Рендерить 1x1 пиксель</span>
                            </div>
                            <div class="ym-field__hint">Резервный учет визитов для редких браузеров с полностью отключенным JavaScript.</div>
                        </div>

                        <div class="ym-tech-item">
                            <label class="ym-label">Встраивание скрипта в HTML (Inline)</label>
                            <div class="ym-toggle-wrapper">
                                <input type="hidden" name="MetrikaSetting[inline]" value="0">
                                <label class="ym-switch">
                                    <input type="checkbox" name="MetrikaSetting[inline]" value="1" <?= $loader->inline ? 'checked' : '' ?>>
                                    <span class="ym-switch__slider"></span>
                                </label>
                                <span class="ym-toggle-label">Inline (0 дополнительных HTTP-запросов)</span>
                            </div>
                            <div class="ym-field__hint">Рекомендовано: Включено. Встраивает микро-загрузчик прямо в HTML, экономя время на отдельный HTTP-запрос.</div>
                        </div>
                    </div>
                </div>
            </details>
        </div>

        <!-- Sticky / Bottom Save Bar -->
        <div class="ym-save-bar">
            <button type="submit" class="ym-btn ym-btn--save">
                <span>💾 Сохранить настройки Метрики</span>
            </button>
            <div class="ym-save-bar__info">
                <span>Файл конфигурации: <code><?= Html::encode($loader->getStorageFilePath() ?: 'не определен') ?></code></span>
                <?php if ($loader->isStorageWritable()): ?>
                    <span class="ym-pill ym-pill--ok">Готов к записи</span>
                <?php else: ?>
                    <span class="ym-pill ym-pill--warning">Проверьте права на запись</span>
                <?php endif; ?>
            </div>
        </div>
    </form>

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