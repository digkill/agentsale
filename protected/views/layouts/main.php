<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;

/* @var $this \yii\web\View */
/* @var $content string */

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <?= Html::csrfMetaTags() ?>
    <script type="text/javascript">
        var EYii = new Object;
    </script>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <script type="text/javascript">var packagesData = {}</script>
</head>
<body>

<?php $this->beginBody() ?>
    <div class="wrap">
        <?php
            if(isset($_SERVER["REQUEST_URI"]) && $_SERVER["REQUEST_URI"] != '/login') {
                NavBar::begin([
                    'brandLabel' => Yii::$app->session->get('currentCityName'),
                    'brandUrl' => Yii::$app->homeUrl,
                    'options' => [
                        'class' => 'navbar navbar-default navbar-fixed-top',
                    ],
                ]);

                echo Nav::widget([
                    'options' => ['class' => 'navbar-nav navbar-right'],
                    'items' => [
                        Yii::$app->user->isGuest ?
                            ['label' => 'Вход', 'url' => ['/site/login']] :
                            ['label' => 'Выход (' . Yii::$app->user->identity->username . ')',
                                'url' => ['/site/logout'],
                                'linkOptions' => ['data-method' => 'post']],
                    ],
                ]);
                NavBar::end();
            }
        ?>
        <div class="container">
            <?php print $content; ?>
        </div>
    </div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>