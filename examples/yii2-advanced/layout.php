<?php

/** @var \yii\web\View $this */
/** @var string $content */

use larikmc\metrika\MetrikaWidget;

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= \yii\helpers\Html::encode($this->title) ?></title>

    <!-- Universal Yandex Metrika Loader: Early queue + deferred tag.js -->
    <?= MetrikaWidget::widget() ?>

    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<?= $content ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage(); ?>
