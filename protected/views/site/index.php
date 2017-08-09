<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use yii\web\JsExpression;
use kartik\select2\Select2;
use kartik\depdrop\DepDrop;
use yii\widgets\MaskedInput;

$this->title = 'Оформление заявки';
?>
<div class="site-index">
    
    <div class="body-content container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2 col-xs-10 col-xs-offset-1">
                <?php $form = ActiveForm::begin([
                    'id' => 'request-form',
                    'action' => '/confirm',
                    'options' => ['class' => 'form-horizontal'],
                    'fieldConfig' => [                        
                        'labelOptions' => ['class' => 'col-lg-1 control-label'],                        
                    ]                    
                ]); ?>
                
                <div class="form-group margin-b-0">                    
                    <div class="col-xs-12">
                        <?= $form->field($model, 'lastname', [
                            'template' => "{input}"
                        ])->textInput([
                                'class'=>'form-control',                            
                                'placeholder' => $model->getAttributeLabel('lastname'),
                                'value' => isset($formData['lastname']) ? $formData['lastname'] : ''
                            ])
                        ?>                        
                    </div>  
                    
                    <div class="col-xs-12">                       
                        <?= $form->field($model, 'firstname', [
                            'template' => "{input}"
                        ])->textInput([
                                'class'=>'form-control',                            
                                'placeholder' => $model->getAttributeLabel('firstname'),
                                'value' => isset($formData['firstname']) ? $formData['firstname'] : ''
                            ])
                        ?>
                    </div>    
                    
                    <div class="col-xs-12">
                        <?= $form->field($model, 'patronymic', [
                            'template' => "{input}"
                        ])->textInput([
                                'class'=>'form-control',                            
                                'placeholder' => $model->getAttributeLabel('patronymic'),
                                'value' => isset($formData['patronymic']) ? $formData['patronymic'] : ''
                            ])
                        ?>                        
                    </div>                    
                </div>
                
                <div class="form-group" style="margin-right:-30px;margin-left:-30px;">                    
                    <div class="col-xs-12 margin-b-10">
                        
                        <?php 
$url = '/get-streets';           
$initScript = <<< SCRIPT
function (element, callback) {
    var id=\$(element).val();
    if (id !== "") {
        \$.ajax("{$url}?id=" + id, {
            dataType: "json"
        }).done(function(data) { callback(data.results);});
    }
}
SCRIPT;
                        ?>
                        
                        <?= Select2::widget([
                            'name' => 'street',
                            'id' => 'street-select',                            
                            'options' => [
                                'placeholder' => $model->getAttributeLabel('street'),                                 
                            ],
                            'pluginOptions' => [
                                'allowClear' => false,
                                'minimumInputLength' => 2,
                                'ajax' => [
                                    'url' => $url,
                                    'dataType' => 'json',
                                    'data' => new JsExpression('function(term,page) { return {search:term}; }'),
                                    'results' => new JsExpression('function(data,page) { return {results:data.results}; }'),
                                ],
                                'initSelection' => new JsExpression($initScript)
                            ],
                        ]) ?>
                        
                    </div>                  
                    
                    <div class="col-xs-6" style="padding-left:30px;padding-right:25px;">                       
                                              
                        <?= $form->field($model, 'house', ['template' => "{input}"])->widget(DepDrop::classname(), [
                                'name' => 'house',
                                'id' => 'house',
                                'data'=> [],
                                'options' => ['placeholder' => 'Дом'],
                                'type' => DepDrop::TYPE_DEFAULT,
                                'select2Options'=>['pluginOptions'=>['allowClear'=>true]],
                                'pluginOptions'=>[
                                        'depends'=>['street-select'],
                                        'url' => Url::to(['/get-houses']),
                                        'loadingText' => 'Загрузка ...',
                                ]
                        ]); ?>
                        
                    </div>                   
                    
                    <div class="col-xs-6" style="padding-left:20px;padding-right:30px;">
                         <?= $form->field($model, 'flat', [
                            
                            'template' => "{input}"
                        ])->input('number', [
                                'class'=>'form-control',                            
                                'placeholder' => $model->getAttributeLabel('flat'),
                                'value' => isset($formData['flat']) ? $formData['flat'] : ''
                            ])
                        ?>
                    </div>
                    <?= Html::error($model, 'flat') ?>
                </div> 
                                               
                <div class="well" style="margin-right: -15px; margin-left: -15px;">
                    <p class="no-margin text-info">Статус дома: <span id="house-status" class="text-success"></span></p>
                </div>
                
                <div class="form-group" style="margin-right: -30px;">                    
                    <div class="col-xs-6">
                        <?= $form->field($model, 'telephone', ['template' => "<div style='margin-right:15px;'>{input}</div>"])->widget(\yii\widgets\MaskedInput::classname(), [
                            'mask' => '+7(999)9-999-999',
                        ])->input('tel', [
                            'class' => 'form-control',
                            'placeholder' => $model->getAttributeLabel('telephone'),
                            'value' => isset($formData['telephone']) ? $formData['telephone'] : ''
                        ]) ?>                        
                    </div>                   
                    
                    <div class="col-xs-6">
                        <?= $form->field($model, 'addTelephone', ['template' => "<div style='margin-right:15px;'>{input}</div>"])->widget(\yii\widgets\MaskedInput::classname(), [
                            'mask' => '+7(999)9-999-999',
                        ])->input('tel', [
                            'class' => 'form-control',
                            'placeholder' => $model->getAttributeLabel('addTelephone'),
                            'value' => isset($formData['addTelephone']) ? $formData['addTelephone'] : ''
                        ]) ?>
                    </div> 
                </div>                
               
                <hr>
                
                <!-- Nav tabs -->
                <ul class="nav nav-tabs" role="tablist" style="margin-right: -15px; margin-left: -15px;">
                    <li class="active"><a href="#bundles" role="tab" data-toggle="tab"><strong>Пакеты</strong></a></li>
                    <?php if(!empty($products['mono'])): ?>
                        <li><a href="#mono" role="tab" data-toggle="tab"><strong>Моно</strong></a></li>
                    <?php endif; ?>
                   <!-- <li style="width:40%;"><a href="#add" role="tab" data-toggle="tab"><strong>Допродажа</strong></a></li> -->
                </ul>

                <!-- Tab panes -->
                <div class="tab-content">
                    <div class="tab-pane active" id="bundles">
                        <?php
                           print Html::radioList('product', array(current(array_keys($products['packages']))), $products['packages'], array(
                               'item' => function($index, $label, $name, $checked, $value) {
                                    return '<div class="radio padding-t-5 padding-b-5 product-item">
                                                <label>
                                                    <input type="radio" name="'.$name.'" value="'.$value.'"' . ($checked ? 'checked="checked"' : '') . '>' .
                                                    $label
                                                .'</label>
                                            </div>';
                               }
                           ));
                        ?>                       
                    </div>
                    <?php if(!empty($products['mono'])): ?>                       
                        <div class="tab-pane" id="mono">
                            <?php
                               print Html::radioList('product', array(4), $products['mono'], array(
                                   'item' => function($index, $label, $name, $checked, $value) {
                                        return '<div class="radio padding-t-5 padding-b-5 product-item">
                                                    <label>
                                                        <input type="radio" name="'.$name.'" value="'.$value.'">' .
                                                        $label
                                                    .'</label> 
                                                </div>';
                                   }
                               ));
                            ?>
                        </div>
                    <?php endif; ?>                   
                </div>
                
                <p class="margin-t-15 text-info"><strong>Номер договора:</strong></p>
                
                <div class="form-group">
                    <div class="col-xs-6">
                        <?= Html::radioList('agreement', isset($formData['agreementNumber']) && !empty($formData['agreementNumber']) ? 1 : 0, array('автоматически', 'вручную'), array(
                               'item' => function($index, $label, $name, $checked, $value) {
                                    return '<div class="radio">
                                                <label>
                                                    <input type="radio" name="'.$name.'" value="'.$value.'"' . ($checked ? 'checked="checked"' : '') . '>' .
                                                    $label
                                                .'</label>'
                                            . '</div>';
                               }
                           )); ?>                        
                    </div>                    
                    <div class="col-xs-6 js-agreement-value">
                        <?= $form->field($model, 'agreementNumber', ['template' => "<div style='margin-right:15px;'>{input}</div>"])->widget(\yii\widgets\MaskedInput::classname(), [
                            'mask' => '999999999999',
                        ])->input('tel', [
                            'class' => 'form-control',
                            'placeholder' => $model->getAttributeLabel('agreementNumber'),
                            'value' => isset($formData['agreementNumber']) ? $formData['agreementNumber'] : ''                            
                        ]) ?>
                        <p class="help-block help-block-error js-agreement-value-text"></p>
                    </div>                    
                </div>                
                
                <p class="margin-t-15 text-info js-devices"><strong>Необходимое оборудование:</strong></p>
                
                <div class="form-group">                    
                    <label for="selectDevices" class="col-xs-6 col-md-6 control-label">Для Интернет</label>
                    <div class="col-xs-12 col-md-6">
                        <select class="form-control" id="selectDevices-internet" name="selectDevices[]">
                            <?php 
                                $items = array();
                                $tagOptions = array();
                                print Html::renderSelectOptions('', $items, $tagOptions);
                            ?>                                                
                        </select>
                    </div>
                </div>
                <div class="form-group">                    
                    <label for="selectDevices" class="col-xs-6 col-md-6 control-label">Для Дом.ru TV</label>
                    <div class="col-xs-12 col-md-6">
                        <select class="form-control" id="selectDevices-domrutv" name="selectDevices[]">
                            <?php 
                                $items = array();
                                $tagOptions = array();
                                print Html::renderSelectOptions('', $items, $tagOptions);
                            ?>
                        </select>
                    </div> 
                </div>
                
                <div class="form-group margin-b-0">                    
                    <div class="col-xs-12">
                        <?php $model->comment = isset($formData['comment']) ? $formData['comment'] : ''; ?>
                        <?= $form->field($model, 'comment', [
                            'template' => "{input}"
                        ])->textArea([
                                'class'=>'form-control',
                                'style'=>'resize:vertical;',
                                'placeholder' => $model->getAttributeLabel('comment'),
                            ])
                        ?>                        
                    </div>
                </div>
                
                <hr>
                
                <div class="form-group">
                    <div class="col-md-12 col-xs-12">     
                        <input id="packages-data" name="packages-data" type="hidden" value="">
                        <input id="products-data" name="products-data" type="hidden" value="">
                        <input id="street_name" name="street_name" type="hidden" value="">
                        <div class="pull-right">
                            <?= Html::submitButton('Далее', ['class' => 'btn btn-lg btn-success js-submit-request', 'name' => 'next-button']) ?>
                        </div>
                        <!--div class="pull-right">
                            <?= Html::Button('Сохранить', ['class' => 'btn btn-lg btn-warning', 'name' => 'save-button']) ?>
                        </div-->
                    </div>
                </div>
                
                <?php ActiveForm::end(); ?>             
                
            </div>                
        </div>
        
    </div>
</div>