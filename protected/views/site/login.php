<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\LoginForm */

$this->title = 'Вход';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="col-md-8 col-md-offset-3">
	<h1><img src="/images/b2c_logo.png" style="margin-right: 20px;"><?= Html::encode($this->title) ?></h1>

	<p>Пожалуйста, заполните следующие поля для входа:</p>
   
	<?php $form = ActiveForm::begin([
		'id' => 'login-form',
		'options' => ['class' => 'form-horizontal'],
		'fieldConfig' => [
			'template' => "{label}\n<div class=\"col-lg-5\">{input}</div>\n<div class=\"col-lg-8\">{error}</div>",
			'labelOptions' => ['class' => 'col-lg-1 control-label'],
		],
	]); ?>

	<?= $form->field($model, 'username') ?>

	<?= $form->field($model, 'password')->passwordInput() ?>        
	
	<?= $form->field($model, 'city')->dropDownList($items,
			array('options' => ['perm' => ['selected' => 'selected']] 
		)) ?>
	
	<?/*= $form->field($model, 'rememberMe')->checkbox([
		'template' => "<div class=\"col-lg-offset-1 col-lg-3\">{input}\n{label}</div>"        
	]) */?>

	<div class="form-group">
		<div class="col-lg-offset-1 col-lg-11">
			<?= Html::submitButton('Вход', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
		</div>
	</div>

	<?php ActiveForm::end(); ?>
   
</div>