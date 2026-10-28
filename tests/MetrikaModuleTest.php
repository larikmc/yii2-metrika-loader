<?php

namespace larikmc\metrika\tests;

use larikmc\metrika\MetrikaLoader;
use larikmc\metrika\Module;
use PHPUnit\Framework\TestCase;
use Yii;

class MetrikaModuleTest extends TestCase
{
    public function testModuleInitialization(): void
    {
        $module = new Module('metrika', Yii::$app, [
            'counterId' => 113667541,
        ]);

        $this->assertEquals('larikmc\metrika\controllers', $module->controllerNamespace);
        $this->assertEquals('default/index', $module->defaultRoute);
        $this->assertEquals(113667541, $module->counterId);

        $loader = $module->getLoader();
        $this->assertInstanceOf(MetrikaLoader::class, $loader);
        $this->assertEquals(113667541, $loader->counterId);
    }
}
