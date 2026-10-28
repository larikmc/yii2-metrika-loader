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
    private string $tempConfigFile;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = '64web.ru';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
        $this->tempConfigFile = sys_get_temp_dir() . '/metrika_test_' . bin2hex(random_bytes(4)) . '.json';
        $this->mockApplication();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempConfigFile)) {
            @unlink($this->tempConfigFile);
        }
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

    public function testGracefulFallbackWhenEmptyCounterId(): void
    {
        $loader = new MetrikaLoader([
            'enabled' => true,
            'counterId' => null,
        ]);

        $this->assertFalse($loader->isEnabled());
        $this->assertSame('', $loader->render());
    }

    public function testExceptionWhenEmptyCounterIdWithStrictFlag(): void
    {
        $this->expectException(InvalidConfigException::class);
        new MetrikaLoader([
            'enabled' => true,
            'counterId' => null,
            'throwOnEmptyCounterId' => true,
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
            'params' => ['user_id' => 42],
        ]);

        $opts = $loader->getInitOptions();
        $this->assertTrue($opts['clickmap']);
        $this->assertTrue($opts['trackLinks']);
        $this->assertTrue($opts['webvisor']);
        $this->assertSame('dataLayer', $opts['ecommerce']);
        $this->assertSame(42, $opts['user_id']);
    }

    public function testClientConfigCompilation(): void
    {
        $loader = new MetrikaLoader([
            'counterId' => 113667541,
            'maxDelay' => 4000,
            'idleTimeout' => 2000,
        ]);

        $config = $loader->getClientConfig();
        $this->assertSame(113667541, $config['counterId']);
        $this->assertSame(MetrikaLoader::MODE_DEFERRED, $config['mode']);
        $this->assertSame(4000, $config['maxDelay']);
        $this->assertSame(2000, $config['idleTimeout']);
        $this->assertTrue($config['loadOnInteraction']);
        $this->assertIsArray($config['initOptions']);
    }

    public function testRenderHeadContainsDeferredLogic(): void
    {
        $loader = new MetrikaLoader([
            'counterId' => 113667541,
            'inline' => true,
        ]);

        $head = $loader->renderHead();
        $this->assertStringContainsString('requestIdleCallback', $head);
        $this->assertStringContainsString('initMetrikaLoader', $head);
        $this->assertStringContainsString('113667541', $head);
        $this->assertStringContainsString('tag.js', $head);
        $this->assertStringContainsString('window.ym', $head);
    }

    public function testRenderNoscript(): void
    {
        $loader = new MetrikaLoader([
            'counterId' => 113667541,
            'renderNoscript' => true,
        ]);

        $noscript = $loader->renderNoscript();
        $this->assertStringContainsString('<noscript>', $noscript);
        $this->assertStringContainsString('https://mc.yandex.ru/watch/113667541', $noscript);

        $loaderNoPixel = new MetrikaLoader([
            'counterId' => 113667541,
            'renderNoscript' => false,
        ]);
        $this->assertSame('', $loaderNoPixel->renderNoscript());
    }

    public function testSaveAndLoadStoredSettings(): void
    {
        $loader = new MetrikaLoader([
            'storageFile' => $this->tempConfigFile,
            'counterId' => 11111111,
            'clickmap' => true,
            'webvisor' => false,
        ]);

        // Save new settings
        $loader->saveSettings([
            'counterId' => '99887766',
            'clickmap' => 0,
            'webvisor' => 1,
            'ecommerce' => 'dataLayer',
            'allowedHosts' => '64web.ru, example.com',
        ]);

        $this->assertSame(99887766, $loader->counterId);
        $this->assertFalse($loader->clickmap);
        $this->assertTrue($loader->webvisor);
        $this->assertSame('dataLayer', $loader->ecommerce);
        $this->assertSame(['64web.ru', 'example.com'], $loader->allowedHosts);
        $this->assertFileExists($this->tempConfigFile);

        // Instantiate a fresh loader with same storageFile -> should load saved settings
        $freshLoader = new MetrikaLoader([
            'storageFile' => $this->tempConfigFile,
        ]);

        $this->assertSame(99887766, $freshLoader->counterId);
        $this->assertFalse($freshLoader->clickmap);
        $this->assertTrue($freshLoader->webvisor);
        $this->assertSame('dataLayer', $freshLoader->ecommerce);
        $this->assertSame(['64web.ru', 'example.com'], $freshLoader->allowedHosts);
    }

    public function testMetrikaWidgetIntegration(): void
    {
        $comp = new MetrikaLoader([
            'counterId' => 113667541,
            'webvisor' => true,
        ]);
        Yii::$app->set('metrika', $comp);

        $output = MetrikaWidget::widget();
        $this->assertStringContainsString('113667541', $output);
        $this->assertStringContainsString('initMetrikaLoader', $output);
    }
}