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
    if($playerObject->jailcount == 0){
        if($dice1 == $dice2){
            $playerObject->doublescount++;
            debugQuery('UPDATE `myPHPusers` SET `doublescount`= '.$playerObject->doublescount.' WHERE `username`="'.$playerObject->username.'"');
            if($playerObject->doublescount==3){
                echo '<span style="border:3px dashed '.$playerObject->color.'">Broke Wizarding Law! Go straight to Azkaban!</span><br>';
                debugQuery('UPDATE `myPHPusers` SET `doublescount`= 0,`jailcount`=1,`position`=10 WHERE `username`="'.$playerObject->username.'"');
                return;
            }
        } else {
            debugQuery('UPDATE `myPHPusers` SET `doublescount`= 0 WHERE `username`="'.$playerObject->username.'"');
        }
        if($playerObject->position+$diceTotal >= 40){
            echo '<span style="border:3px solid '.$playerObject->color.'">Passed GO! +2 Galleons, 14 Knuts!</span><br>';
            $playerObject->money+=1000;
            debugQuery('UPDATE `myPHPusers` SET `money`='.$playerObject->money.' WHERE `username`="'.$playerObject->username.'"');
        }
        $playerObject->position=($playerObject->position+$diceTotal)%40;
        debugQuery('UPDATE `myPHPusers` SET `position`='.$playerObject->position.' WHERE `username`="'.$playerObject->username.'"');
        processArrival($playerObject);
    } else {
        if($dice1 == $dice2){
            echo '<span style="border:3px solid '.$playerObject->color.'">Released from Jail!</span><br>';
            $playerObject->position=($playerObject->position+$diceTotal)%40;
            $playerObject->jailcount=0;
            debugQuery('UPDATE `myPHPusers` SET `jailcount`= 0,`position`='.$playerObject->position.' WHERE `username`="'.$playerObject->username.'"');
            processArrival($playerObject);
        } else {
            if($playerObject->jailcount==3){
                echo '<span style="border:3px dashed '.$playerObject->color.'">Released from Jail, you paid the fine!</span><br>';
                $playerObject->money-=50;
                $playerObject->position=($playerObject->position+$diceTotal)%40;
                $playerObject->jailcount=0;
                debugQuery('UPDATE `myPHPusers` SET `jailcount`=0,`money`='.$playerObject->money.',`position`='.$playerObject->position.' WHERE `username`="'.$playerObject->username.'"');
                processArrival($playerObject);
            } else {
                echo '<span style="border:3px dashed '.$playerObject->color.'">Still in Jail!</span><br>';
                $playerObject->jailcount++;
                debugQuery('UPDATE `myPHPusers` SET `jailcount`='.$playerObject->jailcount.' WHERE `username`="'.$playerObject->username.'"');
            }
        }
    }
}

function processArrival($playerObject){
    $propertyResult=debugQuery('SELECT * FROM `'.$playerObject->gameid.'Properties` WHERE `id`='.$playerObject->position.'');
    $newProperty=$propertyResult->fetch_object();
    if($newProperty->id==30){
        echo '<span style="border:3px dashed '.$playerObject->color.'">DEMENTORS! Go Straight to Azkaban!</span><br>';
        debugQuery('UPDATE `myPHPusers` SET `position`=10,`jailcount`=1 WHERE `username`="'.$playerObject->username.'"');
    }
}
?>
