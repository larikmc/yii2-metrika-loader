<?php

namespace larikmc\metrika\tests;

use larikmc\metrika\MetrikaLoader;
use larikmc\metrika\MetrikaWidget;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\Application;
use yii\web\View;

class MetrikaLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = '64web.ru';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
        $this->mockApplication();
    }

    protected function tearDown(): void
    {
        $this->destroyApplication();
        @restore_error_handler();
        @restore_exception_handler();
        parent::tearDown();
    }

    protected function mockApplication(): void
    {
        new Application([
            'id' => 'test-app',
            'basePath' => __DIR__,
            'components' => [
                'request' => [
                    'class' => 'yii\web\Request',
                    'cookieValidationKey' => 'test-key-123456789012345678901234',
                    'scriptFile' => __DIR__ . '/index.php',
                    'scriptUrl' => '/index.php',
                ],
            ],
        ]);
    }

    protected function destroyApplication(): void
    {
        Yii::$app = null;
    }

    public function testDefaultValues(): void
    {
        $loader = new MetrikaLoader([
            'counterId' => 113667541,
        ]);

        $this->assertSame(113667541, $loader->counterId);
        $this->assertSame(MetrikaLoader::MODE_DEFERRED, $loader->mode);
        $this->assertTrue($loader->enabled);
        $this->assertSame(3500, $loader->maxDelay);
        $this->assertSame(1500, $loader->idleTimeout);
        $this->assertTrue($loader->loadOnInteraction);
        $this->assertTrue($loader->clickmap);
        $this->assertTrue($loader->trackLinks);
        $this->assertTrue($loader->accurateTrackBounce);
        $this->assertFalse($loader->webvisor);
        $this->assertNull($loader->ecommerce);
        $this->assertTrue($loader->renderNoscript);
        $this->assertTrue($loader->inline);
        $this->assertTrue($loader->isEnabled());
    }

    public function testExceptionWhenEmptyCounterId(): void
    {
        $this->expectException(InvalidConfigException::class);
        new MetrikaLoader([
            'enabled' => true,
            'counterId' => null,
        ]);
    }

    public function testDisabledRendersEmpty(): void
    {
        $loader = new MetrikaLoader([
            'counterId' => 113667541,
            'enabled' => false,
        ]);

        $this->assertFalse($loader->isEnabled());
        $this->assertSame('', $loader->render());
        $this->assertSame('', $loader->renderHead());
        $this->assertSame('', $loader->renderNoscript());
    }

    public function testHostFilter(): void
    {
        $loader = new MetrikaLoader([
            'counterId' => 113667541,
            'allowedHosts' => ['example.com'],
        ]);

        $this->assertFalse($loader->isEnabled());
        $this->assertSame('', $loader->render());

        $loaderMatching = new MetrikaLoader([
            'counterId' => 113667541,
            'allowedHosts' => ['64web.ru', 'test.com'],
        ]);

        $this->assertTrue($loaderMatching->isEnabled());
    }

    public function testInitOptionsCompilation(): void
    {
        $loader = new MetrikaLoader([
            'counterId' => 113667541,
            'webvisor' => true,
            'ecommerce' => 'dataLayer',
            'params' => [
                'ssr' => true,
                'customParam' => 'value123',
            ],
        ]);

        $options = $loader->getInitOptions();
        $this->assertTrue($options['webvisor']);
        $this->assertTrue($options['clickmap']);
        $this->assertTrue($options['trackLinks']);
        $this->assertTrue($options['accurateTrackBounce']);
        $this->assertSame('dataLayer', $options['ecommerce']);
        $this->assertTrue($options['ssr']);
        $this->assertSame('value123', $options['customParam']);
    }

    public function testDeferredRenderContainsQueueAndLegacyProxy(): void
    {
        $loader = new MetrikaLoader([
            'counterId' => 113667541,
            'mode' => MetrikaLoader::MODE_DEFERRED,
            'webvisor' => true,
            'ecommerce' => 'dataLayer',
        ]);

        $output = $loader->render();

        $this->assertStringContainsString('window.ym = window.ym', $output);
        $this->assertStringContainsString("'yaCounter' + counterId", $output);
        $this->assertStringContainsString('requestIdleCallback', $output);
        $this->assertStringContainsString('requestAnimationFrame', $output);
        $this->assertStringContainsString('https://mc.yandex.ru/watch/113667541', $output);
        $this->assertStringContainsString('"counterId":113667541', $output);
        $this->assertStringContainsString('"ecommerce":"dataLayer"', $output);
    }

    public function testImmediateRender(): void
    {
        $loader = new MetrikaLoader([
            'counterId' => 113667541,
            'mode' => MetrikaLoader::MODE_IMMEDIATE,
        ]);

        $output = $loader->render();

        $this->assertStringContainsString('"mode":"immediate"', $output);
        $this->assertStringContainsString('window.ym = window.ym', $output);
    }

    public function testCspNonce(): void
    {
        $loader = new MetrikaLoader([
            'counterId' => 113667541,
            'nonce' => 'test-nonce-abcdef',
        ]);

        $output = $loader->render();
        $this->assertStringContainsString('nonce="test-nonce-abcdef"', $output);
        $this->assertStringContainsString('"nonce":"test-nonce-abcdef"', $output);
    }

    public function testConsentOptions(): void
    {
        $loader = new MetrikaLoader([
            'counterId' => 113667541,
            'requireConsent' => true,
            'consentStorageKey' => '64web_cookie_consent_v1',
            'consentEvent' => 'cookie_analytics_enabled',
            'rejectEvent' => 'cookie_analytics_disabled',
        ]);

        $output = $loader->render();
        $this->assertStringContainsString('"requireConsent":true', $output);
        $this->assertStringContainsString('"consentStorageKey":"64web_cookie_consent_v1"', $output);
        $this->assertStringContainsString('"consentEvent":"cookie_analytics_enabled"', $output);
        $this->assertStringContainsString('"rejectEvent":"cookie_analytics_disabled"', $output);
    }

    public function testRegisterInView(): void
    {
        $view = new View();
        $loader = new MetrikaLoader([
            'counterId' => 113667541,
            'mode' => MetrikaLoader::MODE_DEFERRED,
        ]);

        $loader->register($view);

        $this->assertNotEmpty($view->js[View::POS_HEAD]);
        $this->assertNotEmpty($view->js[View::POS_BEGIN]);
    }

    public function testWidgetInheritsComponentSettings(): void
    {
        Yii::$app->set('metrika', [
            'class' => MetrikaLoader::class,
            'counterId' => 113667541,
            'webvisor' => true,
            'ecommerce' => 'dataLayer',
            'maxDelay' => 4200,
        ]);

        // Widget without explicit props
        $widget = new MetrikaWidget();
        $loader = $widget->getLoader();

        $this->assertInstanceOf(MetrikaLoader::class, $loader);
        $this->assertSame(113667541, $loader->counterId);
        $this->assertTrue($loader->webvisor);
        $this->assertSame('dataLayer', $loader->ecommerce);
        $this->assertSame(4200, $loader->maxDelay);

        // Widget with override
        $overrideWidget = new MetrikaWidget([
            'webvisor' => false,
            'maxDelay' => 5000,
        ]);
        $overrideLoader = $overrideWidget->getLoader();
        $this->assertFalse($overrideLoader->webvisor);
        $this->assertSame(5000, $overrideLoader->maxDelay);
        $this->assertSame(113667541, $overrideLoader->counterId);
    }
}
