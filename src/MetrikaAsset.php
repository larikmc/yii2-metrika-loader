<?php

namespace larikmc\metrika;

use yii\web\AssetBundle;

/**
 * Asset bundle for Yandex Metrika Loader JavaScript file.
 *
 * @author LarikMC <https://64web.ru>
 * @license MIT
 */
class MetrikaAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/assets';

    public $js = [
        'metrika-loader.js',
    ];

    public $depends = [];
}
