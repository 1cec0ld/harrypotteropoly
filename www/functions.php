<?php

function sendMoney($fromName,$toName,$amount){
    $from=debugQuery('SELECT `money` FROM `myPHPusers` WHERE `username`="'.$fromName.'"');
    $fromPlayer=$from->fetch_object();
    $fromPlayer->money -= $amount;
    debugQuery('UPDATE myPHPusers SET `money`='.$fromPlayer->money.' WHERE `username`="'.$fromName.'"');
    
    $to=debugQuery('SELECT `money` FROM `myPHPusers` WHERE `username`="'.$toName.'"');
    $toPlayer=$to->fetch_object();
    $toPlayer->money += $amount;
    debugQuery('UPDATE myPHPusers SET `money`='.$toPlayer->money.' WHERE `username`="'.$toName.'"');
}


?>
