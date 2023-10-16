<?php
(isset($LANG)) ? $LANG : 'ru';
if ($LANG == 'ru') {
    $ERROR["err"] = "Ошибка:";
    
    $BTNS['startGame'] = "Начать";

    $RETURNTXT['selectAction'] = "Выберите действие:";
}

if ($LANG == 'en') {
    $ERROR["err"] = "Error:";

    $BTNS['startGame'] = "Start";

    $RETURNTXT['selectAction'] = "Select an action:";
}

?>