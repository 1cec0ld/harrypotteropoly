<?php
/*House 0.6.26 Crest 1.2.24   Liver  brown*/

function loadBeans($tableID){
    $beans=array();
    $beanResult=debugQuery('SELECT * FROM `'.$tableID.'Beans`');
        while($beans[]=$beanResult->fetch_object()){
        } array_pop($beans);
    return $beans;
}
function processBean($beanObject,$username,$gameid){
    $SELECTResult=debugQuery('SELECT `money` FROM `myPHPusers` WHERE `username`="'.$username.'"');
    if(is_numeric($beanObject->value)){
        $value=intval($beanObject->value);
        $newMoney=$SELECTResult->fetch_object()->money+$value;
        printBean($value>0,'You '.($value<=0?'lost':'gained').' '.printHPMoney($value).'!',$beanObject);
        return $newMoney;
    } else if($beanObject->value=="GotoGo"){
        printBean(true,'You Get a Letter to Hogwarts! This does nothing yet...',$beanObject);
    } else if($beanObject->value=="GotoJail"){
        printBean(false,'Now Go To Azkaban! This does nothing yet...',$beanObject);
    } else if($beanObject->value=="Fromeach250"){
        $result=debugQuery('SELECT `money`,`username` FROM `myPHPusers` WHERE `gameid`="'.$gameid.'"');
        $avar=0;
        while($target=$result->fetch_object()){
            $decr=$target->money-250;
            debugQuery('UPDATE `myPHPusers` SET `money`='.$decr.' WHERE `username`="'.$target->username.'"');
            $avar+=250;
        }
        $newMoney=$SELECTResult->fetch_object()->money+$avar;
        printBean(true,'You coerce others into paying you 8 Sickles 18 Knuts!',$beanObject);
        return $newMoney;
    } else {
        debugQuery('UPDATE `'.$gameid.'Beans` SET `owner`="'.$username.'" WHERE `name`="'.$beanObject->name.'"');
        printBean(true,'You found a Get Out of Azaban Free Card! This does nothing yet...',$beanObject);
    }
    return $SELECTResult->fetch_object()->money;
}
function printBean($good,$effect,$beanObject){
    if($good){
        $border='solid';
    } else {
        $border='dashed';
    }
    echo '<span style="border: 3px '.$border.' '.$beanObject->color.'"><strong> You ate a bean! It tasted like '.$beanObject->name.'! '.$effect.'</strong></span><br>';
}

?>
