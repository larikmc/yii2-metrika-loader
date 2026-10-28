<?php

namespace larikmc\metrika;

use yii\web\AssetBundle;

/**
 * Asset bundle for Metrika Admin Dashboard.
 *
 * @author LarikMC <https://64web.ru>
 * @license MIT
 */
class MetrikaAdminAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/web';

    public $css = [
        'css/metrika-admin.css',
    ];

    public $js = [
        'js/metrika-admin.js',
    ];

    public $depends = [
        'yii\web\YiiAsset',
    ];
}
