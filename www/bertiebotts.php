<?php
//sunflower plains 4480 -1626
/*House 0.6.26 Crest 1.2.24   Liver  brown*/
/*Get from each 0.8.18   Chocolate  brown*/

function loadBeans($tableID){
    $beans=array();
    $beanResult=debugQuery('SELECT * FROM `'.$tableID.'Beans`');
    while($beans[]=$beanResult->fetch_object()){
    }
    return $beans;
}
function processBean($beanObject,$username,$gameid){
    //do the action to the database based on the bean
        //if its a money type
        //else if its a goto
        //else its a getoutofjail
    //tell the user what happened
        //if its a bad bean, border broken
        //else border solid
        //set color to bean color
}

?>
