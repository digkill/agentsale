<?php

use yii\helpers\Html;
use yii\helpers\BaseHtml;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
$this->title = 'Подтверждение заявки';
$this->params['breadcrumbs'][] = $this->title;
//print_r($data);
?>
<div class="site-about lead">
    <div class="body-content container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2 col-xs-10 col-xs-offset-1">
                
                <p>Выбран тариф: <br><span class="text-success"><strong><?= $data['packagesData']->name ?></strong></span></p>
                <p>Включает в себя:</p>
                <ul>
                    <?php foreach($data['packagesData']->products as $id => $item): ?>                            
                        <li><span class="text-success"><strong><?=Yii::$app->params['services'][$id] ?></strong></span></li>                            
                    <?php endforeach; ?>
                        
                        <?php if ($data['selectDevices'])
                            foreach ($data['selectDevices'] as $item): ?>                            
                                <li><span class="text-success"><strong><?= $item['name'] ?></strong></span></li>                            
                        <?php endforeach; ?>                        
                </ul>
                
                <?php if(isset($data['packagesData']->flag_name) && !empty($data['packagesData']->flag_name)): ?>
                    <p>Рекламная акция: <span class="text-success"><strong><?= $data['packagesData']->flag_name ?></strong></span></p>
                <?php endif; ?>
                    
                <?php if (isset($data['packagesData']->cost) && !empty($data['packagesData']->cost)): ?>
                    <p>
                        Абонентская плата: 
                        <span class="text-success"><strong>
                            <?= $data['packagesData']->cost ?>&nbsp;руб./&nbsp;мес.
                            <?php if (isset($data['packagesData']->promo_months) && $data['packagesData']->promo_months == 1): ?>
                                &mdash; первый мес., далее 
                            <?php elseif(isset($data['packagesData']->promo_months) && $data['packagesData']->promo_months > 1): ?>
                                &mdash; первые <?= $data['packagesData']->promo_months; ?> мес., далее 
                            <?php endif; ?>
                            <?php if (isset($data['packagesData']->promo_months) && $data['packagesData']->promo_months > 0): ?>
                                <?= $data['packagesData']->cost_after_promo_months; ?>&nbsp;руб./&nbsp;мес.
                            <?php endif; ?>
                        </strong></span>
                    </p>
                <?php endif; ?>
                
                <?php if (isset($data['packagesData']->cost) && !empty($data['packagesData']->cost)): ?>
                    <p>Стоимость подключения: <span class="text-success"><strong><?= $data['commonConnectPrice'] ?>&nbsp;руб.</strong></span></p>
                <?php endif; ?>                
                
                <ul class="small">
                    <?php if (isset($data['packagesData']->cost) && !empty($data['packagesData']->cost)): ?>
                        <li>Абонентская плата за 1 мес.: <br><span class="text-success"><strong><?= $data['packagesData']->cost ?>&nbsp;руб.</strong></span></li>
                    <?php endif; ?>
                    <?php if (isset($data['commonDevicesPrice'])): ?>
                        <li>Аренда оборудования: <br><span class="text-success"><strong><?= $data['commonDevicesPrice'] ?>&nbsp;руб.</strong></span></li>
                    <?php endif; ?>             
                </ul>                

            </div>
        </div>
        <div class="row">
            <div class="col-md-8 col-md-offset-2 col-xs-10 col-xs-offset-1">   
                <?php $form = ActiveForm::begin([
                    'id' => 'confirm-form',
                    'action' => '/dummy',
                    'options' => ['class' => 'form-horizontal'],
                    'fieldConfig' => [                        
                        'labelOptions' => ['class' => 'col-lg-1 control-label'],                        
                    ]                    
                ]); ?>
                
                <?php 
                    echo 
                        BaseHtml::hiddenInput('street', $data['street']),
                        BaseHtml::hiddenInput('house_num', $data['house']),
                        BaseHtml::hiddenInput('office', $data['flat']),
                        BaseHtml::hiddenInput('client_name', $data['lastname'] . ' ' . $data['firstname'] . ' ' . $data['patronymic']),
                        BaseHtml::hiddenInput('client_phone', $data['telephone']),                       
                        BaseHtml::hiddenInput('agr_pack_id', $data['product']),
                        BaseHtml::hiddenInput('comment', $data['comment']), 
                        BaseHtml::hiddenInput('agreementNumber', $data['agreementNumber']);
                    
                    if(isset($data['selectDevices'][0]['id'])) {
                        if($data['selectDevices'][0]['product'] == 5)
                            print BaseHtml::hiddenInput('materials_ens_id_int', $data['selectDevices'][0]['id']);
                        else
                            print BaseHtml::hiddenInput('materials_ens_id_tv', $data['selectDevices'][0]['id']);
                    }
                    if(isset($data['selectDevices'][1]['id'])){
                        if($data['selectDevices'][1]['product'] == 5)
                            print BaseHtml::hiddenInput('materials_ens_id_int', $data['selectDevices'][1]['id']);
                        else
                            print BaseHtml::hiddenInput('materials_ens_id_tv', $data['selectDevices'][1]['id']);                        
                    }
                    
                    if(isset($data['addTelephone'])) print BaseHtml::hiddenInput('client_phone_extra', $data['addTelephone']);
                ?>                   
                
                <p>Не забудь уточнить у Клиента: </p>
                <ul class="small">
                    <li>Наличие исправного конечного оборудования</li>
                    <li>Готовность подключить услуги в течение 3-5 дней</li>
                    <li>Наличие доп. пакета каналов</li>
                    <li>Мультискрин</li>
                    <li>Email</li>
                </ul>
                
                <p>Итого к оплате: 
                    <span class="text-success">
                        <strong><?= $data['packagesData']->cost; ?> руб. + <?= $data['commonConnectPrice'] ;?> руб. + <?= $data['commonDevicesPrice'] ;?> руб. = <?php print $data['packagesData']->cost + $data['commonConnectPrice'] + $data['commonDevicesPrice']; ?> руб.</strong>
                    </span>
                </p>
                
                <div class="pull-right">
                    <?= Html::submitButton('Отправить', ['id' => 'send-btn', 'class' => 'btn btn-lg btn-success ladda-button', 'data' => ['style' => 'expand-left'], 'name' => 'save-button']) ?>
                </div>
                <div class="pull-left">
                    <?= Html::button('Назад', ['id' => 'return-btn', 'class' => 'btn btn-lg btn-danger', 'name' => 'back-button', 'onclick' => 'window.location.href="/"']) ?>
                </div>
                <?php ActiveForm::end(); ?>    
            </div>
        </div>
    </div>
</div>