<?php
/*House 0.6.26 Crest 1.2.24   Liver  brown*/
/*Get from each 0.8.18   Chocolate  brown*/

if($beanResult=$db->query('SELECT * FROM `myPHPbeans`')){
    $beans=array();
    while($beans[]=$beanResult->fetch_object()){
    }
} else {
    die('SOMETHING BROKE!<br>'.$db->error);
}


?>
