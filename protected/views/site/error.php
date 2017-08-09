<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

$this->title = $name;
?>
<div class="site-error">
    <div class="body-content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2 col-xs-10 col-xs-offset-1">
                <h1><?= Html::encode($this->title) ?></h1>

                <div class="alert alert-danger">
                    <?= nl2br(Html::encode($message)) ?>
                </div>

                <p>
                    Возможно, неправильно набран адрес страницы.
                    Или такая страница была, но по этому адресу ее больше нет.
                </p>
                <p>
                    Можно перейти на <a href="/">главную страницу</a>.
                </p>

            </div>
        </div>
    </div>
</div>