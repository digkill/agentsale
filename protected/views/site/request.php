<?php
$this->title = 'Отправка заявки';
?>
<div class="jumbotron">  
    <?php if(isset($answer['answer']) && !empty($answer['answer']) && !$answer['error']): ?>
        <h1>Заявка отправлена</h1>
        <p><?= $answer['answer'] ?></p>
        <?php if($code !== 'PARAM_IS_LOCKED'):?>
        <p><a class="btn btn-success btn-lg" href="/" role="button">ОК</a></p>
        <?php endif; ?>
    <?php else: ?>
        <h1>Ошибка</h1>
        <p><?= $answer['answer'] ?></p>
        <p><a class="btn btn-danger btn-lg" href="/" role="button">ОК</a></p>
    <?php endif; ?>    
</div>