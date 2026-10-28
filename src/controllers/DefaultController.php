<?php

namespace larikmc\metrika\controllers;

use larikmc\metrika\MetrikaLoader;
use larikmc\metrika\Module;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Default controller for Metrika Admin Dashboard.
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
     */
    public function actionIndex(): string
    {
        $loader = $this->module->getLoader();
        $componentId = $this->module->componentId;
        $hasComponent = Yii::$app !== null && Yii::$app->has($componentId);

        $settings = $this->buildSettingsData($loader);

        $stats = [
            'total' => count($settings),
            'optimal' => count(array_filter($settings, static fn($s) => $s['isOptimal'])),
            'warnings' => count(array_filter($settings, static fn($s) => !$s['isOptimal'] && $s['severity'] === 'warning')),
        ];

        return $this->render('index', [
            'loader' => $loader,
            'componentId' => $componentId,
            'hasComponent' => $hasComponent,
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    /**
     * Builds structured documentation & diagnostic metadata for each configuration option.
     */
    protected function buildSettingsData(MetrikaLoader $loader): array
    {
        return [
            'counterId' => [
                'name' => 'counterId',
                'label' => 'Номер счётчика (Counter ID)',
                'category' => 'core',
                'current' => $loader->counterId,
                'currentDisplay' => !empty($loader->counterId) ? (string)$loader->counterId : '— Не задан —',
                'optimal' => 'Числовой ID (напр. 113667541)',
                'isOptimal' => !empty($loader->counterId) && is_numeric($loader->counterId),
                'severity' => empty($loader->counterId) ? 'warning' : 'ok',
                'psiImpact' => 'none',
                'description' => 'Уникальный цифровой номер вашего счётчика в Яндекс.Метрике. Без него сбор аналитики и отправка целей невозможны.',
                'whyOptimal' => 'Должен быть указан корректный номер из личного кабинета metrika.yandex.ru.',
            ],
            'mode' => [
                'name' => 'mode',
                'label' => 'Режим загрузки (Loading Mode)',
                'category' => 'performance',
                'current' => $loader->mode,
                'currentDisplay' => $loader->mode === MetrikaLoader::MODE_DEFERRED ? 'deferred (Отложенный, PageSpeed 90-100)' : 'immediate (Стандартный)',
                'optimal' => MetrikaLoader::MODE_DEFERRED,
                'isOptimal' => $loader->mode === MetrikaLoader::MODE_DEFERRED,
                'severity' => $loader->mode !== MetrikaLoader::MODE_DEFERRED ? 'warning' : 'ok',
                'psiImpact' => 'high',
                'description' => 'Управляет моментом загрузки тяжелого скрипта tag.js. В режиме deferred скрипт подключается через requestIdleCallback / requestAnimationFrame или по первому пользовательскому действию, сохраняя 0ms блокировки основного потока (TBT = 0).',
                'whyOptimal' => 'Режим "deferred" исключает штрафы Google PageSpeed / Lighthouse за блокировку потока и рендеринга.',
            ],
            'enabled' => [
                'name' => 'enabled',
                'label' => 'Активность счётчика (Enabled)',
                'category' => 'core',
                'current' => $loader->enabled,
                'currentDisplay' => $loader->enabled ? 'Включен (true)' : 'Отключен (false)',
                'optimal' => '!YII_DEBUG || YII_ENV_PROD',
                'isOptimal' => $loader->enabled,
                'severity' => 'ok',
                'psiImpact' => 'none',
                'description' => 'Глобальный переключатель работы счётчика. Позволяет автоматически отключать отправку статистики в окружении разработки (dev/debug).',
                'whyOptimal' => 'На боевом сервере всегда должен быть включен; на локалке можно выключать, чтобы не искажать метрики.',
            ],
            'maxDelay' => [
                'name' => 'maxDelay',
                'label' => 'Максимальная задержка (Max Delay, мс)',
                'category' => 'performance',
                'current' => $loader->maxDelay,
                'currentDisplay' => $loader->maxDelay . ' мс (' . round($loader->maxDelay / 1000, 1) . ' сек)',
                'optimal' => '3000 – 4000 мс (3.5 сек)',
                'isOptimal' => $loader->maxDelay >= 2500 && $loader->maxDelay <= 5000,
                'severity' => ($loader->maxDelay < 1500) ? 'warning' : 'ok',
                'psiImpact' => 'medium',
                'description' => 'Страховочный тайм-аут. Если пользователь открыл вкладку и не совершил ни одного движения мышью или свайпа, Метрика всё равно принудительно инициализируется по истечении этого времени.',
                'whyOptimal' => 'Значение 3500 мс идеально: роботы Google успевают замерить скорость без нагрузки, а реальный визит гарантированно фиксируется.',
            ],
            'idleTimeout' => [
                'name' => 'idleTimeout',
                'label' => 'Таймаут простоя (Idle Timeout, мс)',
                'category' => 'performance',
                'current' => $loader->idleTimeout,
                'currentDisplay' => $loader->idleTimeout . ' мс (' . round($loader->idleTimeout / 1000, 1) . ' сек)',
                'optimal' => '1000 – 2000 мс',
                'isOptimal' => $loader->idleTimeout >= 800 && $loader->idleTimeout <= 3000,
                'severity' => 'ok',
                'psiImpact' => 'medium',
                'description' => 'Параметр requestIdleCallback({ timeout }). Браузер ждёт момента затишья в основном потоке и только тогда подгружает скрипт аналитики.',
                'whyOptimal' => 'Значение 1500 мс даёт браузеру время плавно закончить отрисовку страницы до запуска парсинга Метрики.',
            ],
            'loadOnInteraction' => [
                'name' => 'loadOnInteraction',
                'label' => 'Загрузка по взаимодействию (Load On Interaction)',
                'category' => 'performance',
                'current' => $loader->loadOnInteraction,
                'currentDisplay' => $loader->loadOnInteraction ? 'Включено (true)' : 'Выключено (false)',
                'optimal' => 'true',
                'isOptimal' => (bool)$loader->loadOnInteraction,
                'severity' => !$loader->loadOnInteraction ? 'warning' : 'ok',
                'psiImpact' => 'high',
                'description' => 'При первом же таче, скролле, клике или движении мыши Метрика моментально стартует, перехватывая реальное поведение пользователя без секундных ожиданий.',
                'whyOptimal' => 'Обеспечивает 100% точность: как только живой человек дотронулся до экрана, отложенность снимается.',
            ],
            'webvisor' => [
                'name' => 'webvisor',
                'label' => 'Вебвизор 2.0 (Webvisor)',
                'category' => 'features',
                'current' => $loader->webvisor,
                'currentDisplay' => $loader->webvisor ? 'Включен (true)' : 'Выключен (false)',
                'optimal' => 'false (для максимальной скорости) / true (для записи UX)',
                'isOptimal' => true,
                'severity' => 'ok',
                'psiImpact' => $loader->webvisor ? 'high' : 'low',
                'description' => 'Запись видео экрана и движений курсора. Добавляет дополнительный скрипт вебвизора и увеличивает фоновый трафик посетителя.',
                'whyOptimal' => 'Рекомендуется включать, если вы регулярно смотрите видеозаписи визитов. Для исключительно быстрой загрузки можно выключить.',
            ],
            'clickmap' => [
                'name' => 'clickmap',
                'label' => 'Карта кликов (Clickmap)',
                'category' => 'features',
                'current' => $loader->clickmap,
                'currentDisplay' => $loader->clickmap ? 'Включена (true)' : 'Выключена (false)',
                'optimal' => 'true',
                'isOptimal' => (bool)$loader->clickmap,
                'severity' => 'ok',
                'psiImpact' => 'low',
                'description' => 'Сбор тепловой карты кликов по кнопкам, ссылкам и баннерам.',
                'whyOptimal' => 'Практически не создаёт нагрузки, но даёт ценные маркетинговые данные о поведении на сайте.',
            ],
            'trackLinks' => [
                'name' => 'trackLinks',
                'label' => 'Отслеживание ссылок (Track Links)',
                'category' => 'features',
                'current' => $loader->trackLinks,
                'currentDisplay' => $loader->trackLinks ? 'Включено (true)' : 'Выключено (false)',
                'optimal' => 'true',
                'isOptimal' => (bool)$loader->trackLinks,
                'severity' => 'ok',
                'psiImpact' => 'low',
                'description' => 'Автоматический сбор кликов по исходящим ссылкам, кнопкам «mailto:», «tel:» и файлам (pdf, doc, zip).',
                'whyOptimal' => 'Позволяет видеть переходы в мессенджеры и звонки без ручной разметки целей.',
            ],
            'accurateTrackBounce' => [
                'name' => 'accurateTrackBounce',
                'label' => 'Точный отказ (Accurate Track Bounce)',
                'category' => 'analytics',
                'current' => $loader->accurateTrackBounce,
                'currentDisplay' => is_bool($loader->accurateTrackBounce)
                    ? ($loader->accurateTrackBounce ? 'Включен (15 сек)' : 'Выключен')
                    : ($loader->accurateTrackBounce . ' мс'),
                'optimal' => 'true (15000 мс)',
                'isOptimal' => (bool)$loader->accurateTrackBounce,
                'severity' => 'ok',
                'psiImpact' => 'none',
                'description' => 'Не считает визит отказом, если посетитель читал страницу дольше 15 секунд (по умолчанию Яндекс считает отказом любой 1-страничный визит).',
                'whyOptimal' => 'Снижает показатель отказов до реалистичных цифр и улучшает SEO-показатели вовлеченности.',
            ],
            'ecommerce' => [
                'name' => 'ecommerce',
                'label' => 'Электронная коммерция (Ecommerce)',
                'category' => 'analytics',
                'current' => $loader->ecommerce,
                'currentDisplay' => !empty($loader->ecommerce) ? (string)$loader->ecommerce : 'Отключено (null)',
                'optimal' => '\'dataLayer\' (для магазинов) / null (для услуг)',
                'isOptimal' => true,
                'severity' => 'ok',
                'psiImpact' => 'low',
                'description' => 'Имя глобального JavaScript-контейнера для отслеживания покупок, добавления в корзину и просмотров карточек товаров.',
                'whyOptimal' => 'Для интернет-магазинов стандартной практикой является \'dataLayer\'.',
            ],
            'allowedHosts' => [
                'name' => 'allowedHosts',
                'label' => 'Разрешённые хосты (Allowed Hosts)',
                'category' => 'security',
                'current' => $loader->allowedHosts,
                'currentDisplay' => !empty($loader->allowedHosts) ? implode(', ', $loader->allowedHosts) : 'Все хосты разрешены (пустой массив)',
                'optimal' => '[\'64web.ru\', \'www.64web.ru\']',
                'isOptimal' => true,
                'severity' => 'ok',
                'psiImpact' => 'none',
                'description' => 'Фильтр по домену. Если задан, скрипт Метрики загружается ТОЛЬКО на этих доменах, предотвращая мусорный сбор данных с тестовых серверов.',
                'whyOptimal' => 'Защищает ваш рабочий счётчик от визитов разработчиков с .loc / localhost / staging.',
            ],
            'requireConsent' => [
                'name' => 'requireConsent',
                'label' => 'Требовать согласие на куки (Require Consent)',
                'category' => 'privacy',
                'current' => $loader->requireConsent,
                'currentDisplay' => $loader->requireConsent ? 'Да (true, ждёт куки-баннер)' : 'Нет (false, автозапуск)',
                'optimal' => 'false (для РФ) / true (для ЕС/GDPR)',
                'isOptimal' => true,
                'severity' => 'ok',
                'psiImpact' => 'none',
                'description' => 'Интеграция с политикой конфиденциальности. Если true, Метрика не стартует до тех пор, пока пользователь не нажмёт «Принять» в куки-баннере.',
                'whyOptimal' => 'В РФ для 152-ФЗ достаточно информационного баннера, поэтому по умолчанию false.',
            ],
        ];
    }
}
