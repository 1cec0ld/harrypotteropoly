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
function processRoll($playerObject){
    $dice1=rand(1,6);
    $dice2=rand(1,6);
    $diceTotal=$dice1+$dice2;
    echo '<span style="border:3px solid '.$playerObject->color.'">Rolled '.$dice1.' and '.$dice2.'!</span><br>';
    if($playerObject->position+$diceTotal > 40){
        echo '<span style="border:3px solid '.$playerObject->color.'">Passed GO! +2 Galleons, 14 Knuts!</span><br>';
        $newMoney=$playerObject->money+1000;
        debugQuery('UPDATE `myPHPusers` SET `money`='.$newMoney.' WHERE `username`="'.$playerObject->username.'"');
    } else if($playerObject->position+$diceTotal == 40) {
        echo '<span style="border:3px solid '.$player->color.'">Landed on GO! +4 Galleons, 28 Knuts!</span><br>';
        $newMoney=$playerObject->money+2000;
        debugQuery('UPDATE `myPHPusers` SET `money`='.$newMoney.' WHERE `username`="'.$playerObject->username.'"');
    }
    $playerObject->position=($playerObject->position+$diceTotal)%40;
    debugQuery('UPDATE `myPHPusers` SET `position`='.$playerObject->position.' WHERE `username`="'.$playerObject->username.'"');
    processArrival($playerObject);
}
function processArrival($playerObject){
    $propertyResult=debugQuery('SELECT * FROM `'.$playerObject->gameid.'Properties` WHERE `id`='.$playerObject->position.'');
    $newProperty=$propertyResult->fetch_object();
    if($newProperty->id==30){
        debugQuery('UPDATE `myPHPusers` SET `position`=10,`jailcount`=1 WHERE `username`="'.$playerObject->username.'"');
    }
}
?>
