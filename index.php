<?php
class Player{
    public $galleons =0;
    public $sickles =0;
    public $knuts =0;
}
class game{
    public $players = array();
    public $playermax =0;
    public $id =0;
}



$one = new Player();
$GO = $_POST['go'];
if (isset($GO)){
    $one->galleons+=2;
    $one->knuts+=14;
}

echo '<br><br>'.$one->galleons.'<br>'.$one->sickles.'<br>'.$one->knuts;

?>

<form action='index.php' method='POST'>
    <input type='submit' name='go' value='Pass Go'>
</form>
