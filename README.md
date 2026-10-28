# Yii2 Metrika Loader (`larikmc/yii2-metrika-loader`)

[![Latest Version](https://img.shields.io/github/v/release/larikmc/yii2-metrika-loader?label=release)](https://github.com/larikmc/yii2-metrika-loader/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)
[![PHP](https://img.shields.io/badge/PHP-7.4%20|%208.0%20|%208.1%20|%208.2%20|%208.3%20|%208.4-blue)](https://php.net)
[![Yii2](https://img.shields.io/badge/Yii2-2.0.x-orange.svg)](https://yiiframework.com)

**Высокопроизводительный загрузчик Яндекс.Метрики для Yii2 с визуальной панелью управления.**  
Оптимизирует **Google PageSpeed Insights** (PSI 90–100, TBT = 0 мс), устраняет блокировку основного потока браузера и гарантирует **100% фиксацию конверсий и вызовов целей** (`reachGoal`).

---

## ⚡ Главные преимущества

1. **Идеальная скорость (PageSpeed 90–100 & TBT = 0 мс)**:  
   Тяжёлый скрипт `tag.js` не скачивается в первые секунды. Он загружается в свободную паузу браузера (`requestIdleCallback`) либо мгновенно по первому касанию/скроллу страницы.
2. **0 потерянных целей**:  
   Очередь `window.ym` создаётся мгновенно (< 0.2 мс) в `<head>`. Любые клики, формы и квизы могут сразу вызывать `reachGoal` — ни одна конверсия не пропадёт.
3. **Управление из панели администратора**:  
   Смена номера счётчика, включение Вебвизора, Карты кликов, Ecommerce или смена режима — прямо в веб-интерфейсе без правок PHP-конфигов. Настройки сохраняются автоматически.
4. **Обратная совместимость**:  
   Поддерживает как современный синтаксис `ym(id, ...)`, так и старый вызов `yaCounterXXXXX.reachGoal(...)`.
5. **Фильтр доменов**:  
   Защита от сбора мусорной статистики с локальных копий (`.loc`, `localhost`).

---

## 📦 Установка

Установите пакет через Composer:

```bash
composer require larikmc/yii2-metrika-loader
```

Или добавьте в `composer.json`:

```json
{
    "require": {
        "larikmc/yii2-metrika-loader": "^1.2.0"
    }
}
```

---

## 🚀 Быстрый старт за 3 шага

### Шаг 1. Подключите компонент (`common/config/main.php`)

```php
'components' => [
    'metrika' => [
        'class' => \larikmc\metrika\MetrikaLoader::class,
        'counterId' => 113667541, // номер счётчика (можно задать и в админке)
    ],
],
```

### Шаг 2. Подключите модуль админки (`backend/config/main.php`)

```php
'modules' => [
    'metrika' => [
        'class' => \larikmc\metrika\Module::class,
        'accessRoles' => ['admin'], // доступ только для администраторов
    ],
],
```

Добавьте пункт в меню админки (по желанию):

```php
[
    'icon' => 'query_stats',
    'label' => 'Яндекс.Метрика',
    'url' => ['/metrika'],
],
```

Панель управления доступна по адресу: `/admin/metrika`.

### Шаг 3. Вставьте виджет в шаблон сайта (`frontend/views/layouts/main.php`)

В секцию `<head>`:

```php
<?= \larikmc\metrika\MetrikaWidget::widget() ?>
```

Готово! Метрика работает в отложенном режиме с максимальным баллом PageSpeed.

---

## 🎛️ Панель управления (Admin Dashboard)

В модуль встроен удобный интерфейс управления (`/admin/metrika`):

- **Номер счётчика**: просмотр и сменяемость ID счётчика с быстрой ссылкой в кабинет Яндекс.Метрики.
- **Тумблеры функций**:
  - 🎥 **Вебвизор 2.0** (`webvisor`) — включение видеозаписи действий пользователей.
  - 🗺️ **Карта кликов** (`clickmap`) — тепловая карта нажатий.
  - 🔗 **Отслеживание ссылок** (`trackLinks`) — автофиксация переходов, кликов по `tel:`, `mailto:` и скачиваний файлов.
  - ⏱️ **Точный отказ (15 сек)** (`accurateTrackBounce`) — честный показатель отказов.
- **Режим работы**:
  - `⚡ Отложенный (Deferred)` — рекомендуемый режим высокой скорости (PageSpeed 90–100).
  - `⏱️ Стандартный (Immediate)` — классическая синхронная загрузка.
- **Электронная коммерция**: поддержка передачи контейнера `dataLayer`.
- **Фильтр доменов**: ограничение сбора статистики рабочими доменами сайта.
- **Шпаргалка**: готовые сниппеты с кнопками копирования в 1 клик.

Все настройки автоматически сохраняются в файл `common/config/metrika.json` и мгновенно применяются на фронтенде с очисткой кэша.

---

## 🎯 Отправка целей (Goal Tracking)

Цели работают сразу после открытия страницы, даже если скрипт Метрики ещё не успел загрузиться:

```javascript
// Стандартный вызов цели:
ym(113667541, 'reachGoal', 'lead_form_submitted');

// С передачей параметров:
ym(113667541, 'reachGoal', 'order_success', { order_price: 4990, currency: 'RUB' });

// Старый формат yaCounter (автоматически перехватывается):
yaCounter113667541.reachGoal('lead_form_submitted');
```

Принудительно загрузить Метрику в любой момент из JavaScript:

```javascript
window.loadYandexMetrika();
```

---

## ⚙️ Справочник параметров конфигурации

| Параметр | Тип | По умолчанию | Описание |
|---|---|---|---|
| `counterId` | `int\|string` | `null` | Номер счётчика Метрики |
| `mode` | `string` | `'deferred'` | Режим загрузки: `'deferred'` (отложенный) или `'immediate'` |
| `enabled` | `bool` | `true` | Включение / выключение вывода кода |
| `webvisor` | `bool` | `false` | Запись действий посетителей (Вебвизор 2.0) |
| `clickmap` | `bool` | `true` | Тепловая карта кликов |
| `trackLinks` | `bool` | `true` | Автоотслеживание кликов по ссылкам, телефонам и файлам |
| `accurateTrackBounce` | `bool\|int` | `true` | Точный показатель отказов (15 секунд) |
| `ecommerce` | `string\|null` | `null` | Имя контейнера электронной коммерции (`'dataLayer'`) |
| `allowedHosts` | `array` | `[]` | Список разрешённых доменов (пусто = все домены) |
| `maxDelay` | `int` | `3500` | Макс. таймаут до принудительного старта (мс) |
| `idleTimeout` | `int` | `1500` | Таймаут ожидания простоя в `requestIdleCallback` (мс) |
| `loadOnInteraction` | `bool` | `true` | Запуск по первому клику, скроллу, тачу или нажатию клавиш |
| `storageFile` | `string\|null` | `null` | Путь к JSON-файлу сохранения (по умолчанию `@common/config/metrika.json`) |
| `renderNoscript` | `bool` | `true` | Вывод резервного 1x1 пикселя `<noscript>` |
| `inline` | `bool` | `true` | Встраивать загрузчик инлайново (0 лишних HTTP-запросов) |
| `requireConsent` | `bool` | `false` | Ожидание куки-баннера перед загрузкой (GDPR) |

---

## 🧪 Тестирование

```bash
vendor/bin/phpunit
```

---

## 📄 Лицензия

MIT License. Автор: [LarikMC](https://64web.ru)
