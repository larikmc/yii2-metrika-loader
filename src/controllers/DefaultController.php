<?php

namespace larikmc\metrika\controllers;

use larikmc\metrika\MetrikaLoader;
use larikmc\metrika\Module;
use Yii;
use yii\web\Controller;

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

        $primary = $this->getPrimaryFeatures($loader);
        $technical = $this->getTechnicalSettings($loader);

        return $this->render('index', [
            'loader' => $loader,
            'componentId' => $componentId,
            'hasComponent' => $hasComponent,
            'primary' => $primary,
            'technical' => $technical,
        ]);
    }

    /**
     * Essential, human-understandable features.
     */
    protected function getPrimaryFeatures(MetrikaLoader $loader): array
    {
        return [
            [
                'code' => 'mode',
                'icon' => '⚡',
                'title' => 'Режим загрузки',
                'value' => $loader->mode === MetrikaLoader::MODE_DEFERRED ? 'Отложенный (Deferred)' : 'Стандартный (Immediate)',
                'isGood' => $loader->mode === MetrikaLoader::MODE_DEFERRED,
                'status' => $loader->mode === MetrikaLoader::MODE_DEFERRED ? 'Рекомендовано' : 'Обычный',
                'summary' => 'Скрипт не тормозит первый экран сайта. Оценка Google PageSpeed 90–100, при этом цели работают сразу.',
            ],
            [
                'code' => 'webvisor',
                'icon' => '🎥',
                'title' => 'Вебвизор 2.0',
                'value' => $loader->webvisor ? 'Включен' : 'Выключен',
                'isGood' => true,
                'status' => $loader->webvisor ? 'Запись активна' : 'Экономит скорость',
                'summary' => 'Записывает видео визитов и движения мыши. Включайте, если смотрите видеозаписи пользователей.',
            ],
            [
                'code' => 'clickmap',
                'icon' => '🗺️',
                'title' => 'Карта кликов',
                'value' => $loader->clickmap ? 'Включена' : 'Выключена',
                'isGood' => (bool)$loader->clickmap,
                'status' => 'Рекомендовано',
                'summary' => 'Тепловая карта кликов по кнопкам и ссылкам. Показывает, куда чаще всего нажимают посетители.',
            ],
            [
                'code' => 'trackLinks',
                'icon' => '🔗',
                'title' => 'Отслеживание ссылок',
                'value' => $loader->trackLinks ? 'Включено' : 'Выключено',
                'isGood' => (bool)$loader->trackLinks,
                'status' => 'Рекомендовано',
                'summary' => 'Автоматически фиксирует клики по номерам телефонов (tel:), почте (mailto:) и скачивание файлов.',
            ],
            [
                'code' => 'accurateTrackBounce',
                'icon' => '⏱️',
                'title' => 'Точный отказ (15 сек)',
                'value' => $loader->accurateTrackBounce ? 'Включен (15 сек)' : 'Выключен',
                'isGood' => (bool)$loader->accurateTrackBounce,
                'status' => 'Рекомендовано',
                'summary' => 'Не считает визит отказом, если человек читал страницу дольше 15 секунд. Показывает честную статистику.',
            ],
            [
                'code' => 'ecommerce',
                'icon' => '🛒',
                'title' => 'Электронная коммерция',
                'value' => !empty($loader->ecommerce) ? (string)$loader->ecommerce : 'Не используется',
                'isGood' => true,
                'status' => !empty($loader->ecommerce) ? 'Активна' : 'Отключена',
                'summary' => 'Сбор данных о заказах, корзине и товарах через стандартный массив window.dataLayer.',
            ],
            [
                'code' => 'allowedHosts',
                'icon' => '🛡️',
                'title' => 'Фильтр доменов',
                'value' => !empty($loader->allowedHosts) ? implode(', ', $loader->allowedHosts) : 'Все домены',
                'isGood' => true,
                'status' => !empty($loader->allowedHosts) ? 'Защита включена' : 'Без фильтра',
                'summary' => 'Метрика собирает данные только на ваших рабочих доменах, отсекая визиты разработчиков с .loc и localhost.',
            ],
            [
                'code' => 'enabled',
                'icon' => '🟢',
                'title' => 'Активность кода',
                'value' => $loader->enabled ? 'Активен' : 'Отключен',
                'isGood' => $loader->enabled,
                'status' => $loader->enabled ? 'Работает' : 'Приостановлен',
                'summary' => 'Включает или выключает генерацию кода счётчика на страницах сайта.',
            ],
        ];
    }

    /**
     * Technical parameters for fine-tuning.
     */
    protected function getTechnicalSettings(MetrikaLoader $loader): array
    {
        return [
            [
                'param' => 'maxDelay',
                'label' => 'Макс. задержка',
                'value' => $loader->maxDelay . ' мс (' . round($loader->maxDelay / 1000, 1) . ' сек)',
                'optimal' => '3500 мс',
                'desc' => 'Страховочный запуск скрипта, если пользователь не нажимал на экран и не скроллил.',
            ],
            [
                'param' => 'idleTimeout',
                'label' => 'Таймаут простоя (Idle)',
                'value' => $loader->idleTimeout . ' мс (' . round($loader->idleTimeout / 1000, 1) . ' сек)',
                'optimal' => '1500 мс',
                'desc' => 'Запуск скрипта в свободную паузу браузера после завершения отрисовки экрана.',
            ],
            [
                'param' => 'loadOnInteraction',
                'label' => 'Старт по касанию',
                'value' => $loader->loadOnInteraction ? 'Да (true)' : 'Нет (false)',
                'optimal' => 'true',
                'desc' => 'Мгновенно запускает загрузку Метрики при первом касании, клике или скролле.',
            ],
            [
                'param' => 'requireConsent',
                'label' => 'Ожидание куки-баннера',
                'value' => $loader->requireConsent ? 'Да (ждёт согласия)' : 'Нет (автостарт)',
                'optimal' => 'false (для РФ)',
                'desc' => 'Блокировка Метрики до нажатия кнопки согласия в куки-баннере (нужна только для ЕС/GDPR).',
            ],
            [
                'param' => 'renderNoscript',
                'label' => 'Пиксель <noscript>',
                'value' => $loader->renderNoscript ? 'Да' : 'Нет',
                'optimal' => 'true',
                'desc' => 'Резервный 1x1 пиксель для редких браузеров с полностью отключенным JavaScript.',
            ],
            [
                'param' => 'inline',
                'label' => 'Встраивание скрипта',
                'value' => $loader->inline ? 'Inline (0 доп. HTTP-запросов)' : 'Отдельный файл',
                'optimal' => 'true',
                'desc' => 'Встраивает микро-загрузчик прямо в HTML, экономя время на отдельный HTTP-запрос.',
            ],
        ];
    }
}
