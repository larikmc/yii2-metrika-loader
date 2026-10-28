# Universal Yii2 Metrika Loader (`larikmc/yii2-metrika-loader`)

> **Высокопроизводительный загрузчик Яндекс Метрики для Yii2**, оптимизирующий Google PageSpeed Insights (PSI), снижающий Total Blocking Time (TBT) до минимума и сохраняющий 100% точность аналитики и вызовов целей (`reachGoal`).

---

## 🎯 Проблема и решение

### Проблема стандартного счётчика Метрики
Официальный асинхронный сниппет Яндекс Метрики скачивает тяжёлый скрипт `tag.js` (~70-90 КБ gzipped, ~250 КБ распакованный JS) в первые секунды загрузки страницы. Это приводит к:
1. **Штрафам Google PageSpeed Insights (Lighthouse)**: высокий TBT (Total Blocking Time), задержка отрисовки (LCP/FCP), перегрузка главного потока (Long Tasks).
2. **Блокировке гидратации и интерактивности**: парсинг и компиляция `tag.js` конкурируют с критическим JS-кодом сайта.

### Решение MetrikaLoader
1. **Ранняя очередь `window.ym`**: регистрируется синхронно и мгновенно (< 0.2 мс) в `<head>`, благодаря чему любые скрипты на странице (клик по кнопкам, квизы, отправка форм) могут сразу вызывать `ym(id, 'reachGoal', ...)` — ни одно событие не потеряется.
2. **Прокси для обратной совместимости с `yaCounter<ID>`**: устаревшие скрипты или расширения, обращающиеся к `window.yaCounterXXXXX.reachGoal()`, прозрачно перенаправляются в очередь `window.ym`.
3. **Отложенная загрузка `tag.js`**: скачивание откладывается до завершения первоначального рендеринга страницы с использованием двойного `requestAnimationFrame` и `requestIdleCallback` (с таймаутом).
4. **Реакция на первое действие пользователя (`loadOnInteraction`)**: при первом скролле, касании экрана, клике или нажатии клавиши скрипт загружается немедленно.
5. **Предохранитель максимальной задержки (`maxDelay`)**: если пользователь не взаимодействует со страницей, `tag.js` загружается по истечении таймаута (по умолчанию 3500 мс).
6. **Приоритет сети**: тег `<script>` создаётся с `async=true` и `fetchPriority="low"`.
7. **Поддержка Cookie Consent**: интеграция с баннерами согласия (GDPR / 152-ФЗ).
8. **Защита от дублей**: гарантия однократного подключения скрипта.

---

## 📦 Установка

Через Composer:

```bash
composer require larikmc/yii2-metrika-loader
```

Или добавьте в `composer.json` вашего проекта:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/larikmc/yii2-metrika-loader.git"
        }
    ],
    "require": {
        "larikmc/yii2-metrika-loader": "^1.1.0"
    }
}
```

---

## ⚙️ Конфигурация

### 1. Как компонент приложения (`common/config/main.php` или `frontend/config/main.php`)

```php
'components' => [
    'metrika' => [
        'class' => \larikmc\metrika\MetrikaLoader::class,
        'counterId' => 113667541, // ID вашего счётчика
        'mode' => \larikmc\metrika\MetrikaLoader::MODE_DEFERRED, // 'deferred' или 'immediate'
        'enabled' => !YII_DEBUG || YII_ENV_PROD, // Включение/отключение
        'maxDelay' => 3500, // Максимальная задержка до принудительной загрузки (мс)
        'idleTimeout' => 1500, // Таймаут для requestIdleCallback (мс)
        'loadOnInteraction' => true, // Загрузка при первом взаимодействии пользователя
        'webvisor' => true,
        'clickmap' => true,
        'trackLinks' => true,
        'accurateTrackBounce' => true,
        'ecommerce' => 'dataLayer', // Поддержка электронной коммерции
        'params' => [
            'ssr' => true,
        ],
        // Фильтрация по доменам (опционально)
        'allowedHosts' => [
            '64web.ru',
            'www.64web.ru',
        ],
        // Интеграция с политикой согласия (Cookie Consent)
        'requireConsent' => false,
        'consentStorageKey' => '64web_cookie_consent_v1',
        'consentEvent' => 'cookie_analytics_enabled',
        'rejectEvent' => 'cookie_analytics_disabled',
    ],
],
```

---

## 🚀 Использование в шаблонах (Layout)

В шаблоне `views/layouts/main.php` (в секции `<head>`):

```php
use larikmc\metrika\MetrikaWidget;

<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <title><?= Html::encode($this->title) ?></title>

    <!-- Подключение Яндекс Метрики (ранняя очередь + отложенный tag.js) -->
    <?= MetrikaWidget::widget() ?>

    <?php $this->head() ?>
</head>
```

Либо с передачей параметров напрямую в виджет (без регистрации компонента):

```php
<?= MetrikaWidget::widget([
    'counterId' => 113667541,
    'mode' => 'deferred',
    'webvisor' => true,
    'ecommerce' => 'dataLayer',
]) ?>
```

---

## 🎛️ Панель управления и аудит настроек (Admin Dashboard)

Пакет включает встроенную панель администратора для визуального просмотра настроек, аудита влияния на PageSpeed Insights (PSI/TBT), копирования сниппетов и перехода в кабинет Метрики.

### 1. Подключение модуля в `backend/config/main.php`:

```php
'modules' => [
    'metrika' => [
        'class' => \larikmc\metrika\Module::class,
        // Опционально: контроль доступа (по умолчанию ['@'] — только авторизованные)
        'accessRoles' => ['admin'],
    ],
],
```

### 2. URL панели управления
После регистрации модуля страница открывается по адресу:
- `http://ваш-домен/admin/metrika` (или `/metrika` в зависимости от `baseUrl`)
- Прямой маршрут Yii2: `metrika/default/index`

### 3. Добавление пункта в меню админки (например, в `backend/config/main.php`):
```php
'menu' => [
    // ...
    [
        'icon' => 'query_stats',
        'label' => 'Аналитика',
        'items' => [
            ['label' => 'Яндекс.Метрика', 'url' => ['/metrika']],
        ],
    ],
],
```

### Возможности панели:
- **Сводка**: статус работы, режим (`deferred` / `immediate`), влияние на TBT (~0 мс).
- **Аудит каждого параметра**: таблица с текущим значением, рекомендованным оптимальным значением и объяснением, на что параметр влияет.
- **Фильтрация**: быстрые вкладки по категориям (Скорость & PSI, Сбор данных, Отказы, Домены).
- **Шпаргалка**: готовые сниппеты для layout и отправки целей JavaScript (`ym(..., 'reachGoal')`) с кнопками копирования в буфер обмена.
- **Ссылка в 1 клик**: прямой переход в кабинет Яндекс.Метрики для вашего счётчика.
- **Независимые стили**: панель содержит встроенный адаптивный дизайн с поддержкой светлой и тёмной тем и не зависит от сторонних CSS-фреймворков.

---

## 📋 Таблица параметров

| Параметр | Тип | По умолчанию | Описание |
|---|---|---|---|
| `counterId` | `int\|string` | *(обязательный)* | Номер счётчика Яндекс Метрики |
| `mode` | `string` | `'deferred'` | Режим загрузки: `'deferred'` (оптимизированный) или `'immediate'` (стандартный сниппет) |
| `enabled` | `bool` | `true` | Включение / выключение вывода кода |
| `maxDelay` | `int` | `3500` | Максимальная задержка (мс) до принудительной загрузки `tag.js` |
| `idleTimeout` | `int` | `1500` | Таймаут ожидания простоя в `requestIdleCallback` (мс) |
| `loadOnInteraction`| `bool` | `true` | Запуск загрузки при первом событии пользователя (scroll, click, touch, keydown) |
| `interactionEvents`| `array`| `['pointerdown', 'touchstart', 'scroll', 'keydown', 'wheel']` | Список событий пользовательского взаимодействия |
| `webvisor` | `bool` | `false` | Запись Вебвизора |
| `clickmap` | `bool` | `true` | Карта кликов |
| `trackLinks` | `bool` | `true` | Отслеживание внешних ссылок |
| `accurateTrackBounce` | `bool\|int` | `true` | Точный показатель отказов |
| `ecommerce` | `string\|bool\|null` | `null` | Имя контейнера электронной коммерции (`'dataLayer'`) |
| `triggerEvent` | `bool` | `false` | Генерация события инициализации в DOM |
| `params` | `array` | `[]` | Дополнительные параметры инициализации (`ssr`, и др.) |
| `allowedHosts` | `array` | `[]` | Список разрешённых хостов (пустой = любые) |
| `nonce` | `string\|callable` | `null` | CSP nonce для Content Security Policy |
| `renderNoscript` | `bool` | `true` | Вывод резервного пикселя `<noscript>` |
| `requireConsent` | `bool` | `false` | Ожидать согласия пользователя перед загрузкой скрипта |
| `consentStorageKey`| `string\|null` | `null` | Ключ в LocalStorage с состоянием согласия |
| `consentEvent` | `string` | `'cookie_analytics_enabled'` | Имя события согласия на аналитику |
| `rejectEvent` | `string` | `'cookie_analytics_disabled'` | Имя события отказа от аналитики |
| `inline` | `bool` | `true` | Встраивать загрузчик инлайново (0 лишних HTTP-запросов) |

---

## 🛠 API в JavaScript

Загрузчик экспортирует удобные глобальные функции:

### 1. Принудительная загрузка
```javascript
// Загрузить немедленно (например, при начале оформления заказа)
window.loadYandexMetrika();
```

### 2. Отслеживание целей
Работает в любой момент, даже до загрузки `tag.js`:
```javascript
// Официальный современный синтаксис
ym(113667541, 'reachGoal', 'lead_form_submitted', { price: 5000 });

// Устаревший синтаксис yaCounter (поддерживается автоматически через прокси)
window.yaCounter113667541.reachGoal('lead_form_submitted');
```

---

## 🧪 Тестирование

Пакет покрыт модульными тестами PHPUnit:

```bash
vendor/bin/phpunit
```

---

## 📄 Лицензия

MIT License. Copyright (c) 2026 LarikMC.
