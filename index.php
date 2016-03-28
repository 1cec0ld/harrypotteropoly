<?php
echo '<title>HP Monopoly</title>';
require 'sql_connect.php';

session_start();

const GALLEONS = 493;
const SICKLES = 29;
const KNUTS = 1;

function formatError($str){
    return '<strong>'.$str.'</strong><br>';
}
function showMainMenu(){
    echo "Usernames are limited to 16 alphanumeric characters, Game IDs are limited to 6.";
    echo "<form action='index.php' method='POST'>
                Username: <input type='text' name='username' maxlength=16> 
                Game ID: <input type='text' name='gameid' maxlength=6>
                <input type='submit' name='join' value='Join Game'>
                <br>
                Max Players:
                    <select name='maxplayers'>
                        <option disabled selected value=1> </option>
                        <option value=2>2</option>
                        <option value=3>3</option>
                        <option value=4>4</option>
                        <option value=5>5</option>
                        <option value=6>6</option>
                        <option value=7>7</option>
                        <option value=8>8</option>
                    </select>
                <input type='submit' name='create' value='Create New Game'>
            </form><br>";
    listGames();
}
function listGames(){
    global $db;
    $results=$db->query('SELECT * FROM myPHPgames');
    while($row=$results->fetch_object()){
        echo '<span style="background-color:white">'.$row->id.' has '.$row->currentplayers.'/'.$row->maxplayers.' people playing.</span><br>';
        $gameresults=$db->query('SELECT `username`,`color` FROM myPHPusers WHERE `gameid`="'.$row->id.'"');
        while($gamerow=$gameresults->fetch_object()){
            echo '<span style="margin-left:35px;background-color:'.$gamerow->color.'">    '.$gamerow->username.'</span><br>';
        }
    }
}
function getGameByID($id){
    global $db;
    return $db->query('SELECT * FROM `myPHPgames` WHERE `id`="'.$id.'"');
}
function getUserByUsername($name){
    global $db;
    return $db->query('SELECT * FROM `myPHPusers` WHERE `username`="'.$name.'"');
}
function printHPMoney($money){
    $results = array();
    $results[0]= floor($money/GALLEONS);
    $results[1]= floor(($money-($results[0]*GALLEONS))/SICKLES);
    $results[2]= $money-($results[0]*GALLEONS)-($results[1]*SICKLES);
    return $results[0].' Galleon'.($results[0]==1?'':'s').', '
                                .$results[1].' Sickle'.($results[1]==1?'':'s').', and '
                                .$results[2].' Knut'.($results[2]==1?'':'s').'';
}
function toNormalMoney($galleon,$sickle,$knut){
    return ($galleon*GALLEONS)+($sickle*SICKLES)+($knut*KNUTS);
}
function doGame(){
    global $db;
    if(isset($_POST['color'])){
        $db->query('UPDATE myPHPusers SET `color`="'.$_POST['color'].'" WHERE `username`="'.$_SESSION['username'].'"');
    }
    if($currentuser = $db->query('SELECT `money`,`color` FROM `myPHPusers` WHERE `username`="'.$_SESSION['username'].'" AND `gameid`="'.$_SESSION['gameid'].'"')){
        $player=$currentuser->fetch_object();

    

        $userMoney=$player->money;
        if (isset($_POST['go'])){
            $userMoney += toNormalMoney(2,0,14);
        } else if(isset($_POST['reset'])){
            $userMoney = toNormalMoney(15, 3, 18);
            $db->query('UPDATE myPHPusers SET `money`=7500 WHERE `gameid`="'.$_SESSION['gameid'].'"');
        } else if(isset($_POST['send'])){
            if(isset($_POST['toWhom'])){
                if(isset($_POST['galleonsTo']) and isset($_POST['sicklesTo']) and isset($_POST['knutsTo'])){
                    $subtotal=toNormalMoney($_POST['galleonsTo'], $_POST['sicklesTo'], $_POST['knutsTo']);
                    if( $subtotal < 0){
                        echo '<span style="border:3px solid crimson">Gringotts Disapproves...</span><br>';
                    } else if( $subtotal < $userMoney ){
                        echo '<span style="border:3px solid '.$player->color.'">Money Sent!</span><br>';
                        $userMoney-=$subtotal;
                        $db->query('UPDATE myPHPusers SET `money`='.$userMoney.' WHERE `username`="'.$_SESSION['username'].'"');
                        $SELECTResult=$db->query('SELECT `money` FROM `myPHPusers` WHERE `username`="'.$_POST['toWhom'].'"');
                        $newMoney=$SELECTResult->fetch_object()->money+$subtotal;
                        $db->query('UPDATE myPHPusers SET `money`='.$newMoney.' WHERE `username`="'.$_POST['toWhom'].'"');
                    } else {
                        echo '<span style="border:3px solid crimson">Not Enough Funds.</span><br>';
                    }
                }
            }
        }
        $uploadUserData = 'UPDATE myPHPusers SET `money`='.$userMoney.' WHERE `username`="'.$_SESSION['username'].'"';
        if($UPDATEResult=$db->query($uploadUserData)){
        } else {
            echo formatError('UPDATE Error!'.$db->error);
            session_destroy();
            die();
        }
        $players=array();
        if($allUsers = $db->query('SELECT `money`,`username`,`color` FROM `myPHPusers` WHERE `gameid`="'.$_SESSION['gameid'].'"')){
            if($allUsers->num_rows){
                echo '<strong>Members of '.$_SESSION['gameid'].':</strong><br>';
                while($row=$allUsers->fetch_object()){
                    $players[]=$row->username;
                    echo '<span style="margin:0px';
                    if($row->username==$_SESSION['username']){
                        $player = $row;
                        echo ';background-color:'.$row->color.'';
                    }
                    echo '">';
                    
                    echo $row->username.' with '.printHPMoney($row->money).'</span><br>';
                }
            }
        }
        echo "<form action='index.php' method='POST'>";
        echo "    <input type='submit' name='go' value='Pass Go'><br>";
        echo '    Pay:    <input style="margin-left:10px" type="text" name="galleonsTo" size="1" value=0>-Galleons,
                    <input type="text" name="sicklesTo" size="1" value=0>-Sickles,
                    <input type="text" name="knutsTo" size="1" value=0>-Knuts, To:
                    <select name="toWhom">
                        <option disabled selected value=""> </option>';
                        foreach ($players as $name){
                            if($name!=$_SESSION['username']){
                                echo '<option value="'.$name.'">'.$name.'</option>';
                            }
                        }
                    echo '</select>';
                    echo '<input type="submit" name="send" value="Send!">';
        echo "</form>";
    }
    echo "<form action='index.php' method='POST'>";
    echo "    <input type='submit' name='logout' value='Log Out'>";
    echo "    <input type='submit' name='reset' value='Reset Game'>";
    echo "</form>";
    echo "<form action='index.php' method='POST'>";
    echo "    <input type='submit' name='changeColor' value='Customize Color'>";
    echo "    <input type='text' name='color' maxlength=12>";
    echo "</form>";
}

if(isset($_POST['logout'])){
    session_destroy();
    showMainMenu();
} else if(isset($_SESSION['gameid']) and isset($_SESSION['username'])){
    doGame();
} else if(isset($_POST['join'])){
    if(isset($_POST['username']) and preg_match('/^[A-Za-z0-9]{1,16}$/',$_POST['username'])){
        if (isset($_POST['gameid']) and preg_match('/^[A-Za-z0-9]{1,6}$/',$_POST['gameid'])){
            if($SELECTResult=getGameByID($_POST['gameid'])){
                if($SELECTResult->num_rows){
                    $game=$SELECTResult->fetch_object();
                    if($SELECTResult = getUserByUsername($_POST['username'])){
                        if($SELECTResult->num_rows){
                            $player = $SELECTResult->fetch_object();
                            if($player->gameid == $game->id){
                                $_SESSION['gameid'] = $_POST['gameid'];
                                $_SESSION['username'] = $_POST['username'];
                                doGame();
                            } else if($game->currentplayers < $game->maxplayers){
                                if($SELECTResult=getGameByID($player->gameid)){
                                    if($SELECTResult->num_rows){
                                        $oldGame=$SELECTResult->fetch_object();
                                        $decr=$oldGame->currentplayers-1;
                                        if($UPDATEResult=$db->query('UPDATE `myPHPgames` SET `currentplayers`='.$decr.' WHERE `id`="'.$player->gameid.'"')){
                                            if($decr==0){
                                                $db->query('DELETE FROM `myPHPgames` WHERE `id` = "'.$player->gameid.'"');
                                            }
                                        } else {
                                            echo formatError('UPDATE Error!'.$db->error);
                                        }
                                    }
                                } else {
                                    echo formatError('SELECT Error!'.$db->error);
                                }
                                if($UPDATEResult=$db->query('UPDATE `myPHPusers` SET `money`=7500,`gameid`="'.$game->id.'" WHERE `username`="'.$_POST['username'].'"')){
                                    $incr=$game->currentplayers+1;
                                    if($UPDATEResult=$db->query('UPDATE `myPHPgames` SET `currentplayers`='.$incr.' WHERE `id`="'.$game->id.'"')){
                                        $_SESSION['gameid'] = $_POST['gameid'];
                                        $_SESSION['username'] = $_POST['username'];
                                        doGame();
                                    } else {
                                        echo formatError('UPDATE Error!'.$db->error);
                                    }
                                } else {
                                    echo formatError('UPDATE Error!'.$db->error);
                                }
                            } else {
                                echo formatError('Game is full!');
                                showMainMenu();
                            }
                        } else {
                            if($game->currentplayers < $game->maxplayers){
                                if($INSERTResult=$db->query('INSERT INTO `myPHPusers` (`id` ,`username` ,`money` ,`gameid`, `created`) VALUES (NULL , "'.$_POST['username'].'", "7500", "'.$_POST['gameid'].'", NULL)')){
                                    $incr=$game->currentplayers+1;
                                    if($UPDATEResult=$db->query('UPDATE `myPHPgames` SET `currentplayers`='.$incr.' WHERE `id`="'.$game->id.'"')){
                                        $_SESSION['gameid'] = $_POST['gameid'];
                                        $_SESSION['username'] = $_POST['username'];
                                        doGame();
                                    } else {
                                        echo formatError('UPDATE Error!'.$db->error);
                                    }
                                } else {
                                    echo formatError('INSERT Error!'.$db->error);
                                }
                            } else {
                                echo formatError('Game is full!');
                                showMainMenu();
                            }
                        }
                    } else {
                        echo formatError('SELECT Error!'.$db->error);
                    }
                } else {
                    echo formatError('No game found with that Game ID!');
                    showMainMenu();
                }
            } else {
                echo formatError('SELECT Error!'.$db->error);
            }
        } else {
            echo formatError('Invalid Game ID filled in!');
            showMainMenu();
        }
    } else {
        echo formatError('Invalid Username filled in!');
        showMainMenu();
    }
} else if(isset($_POST['create'])){
    if(isset($_POST['username']) ){//and preg_match('/^[A-Za-z0-9]{1,16}$/',$_POST['username'])){
        if (isset($_POST['gameid']) and preg_match('/^[A-Za-z0-9]{1,6}$/',$_POST['gameid'])){
            if(isset($_POST['maxplayers']) and $_POST['maxplayers'] > 1){
                if($SELECTResult=getGameByID($_POST['gameid'])){
                    if($SELECTResult->num_rows){
                        echo formatError('Game already exists! Try to Join instead!');
                        showMainMenu();
                    } else {
                        if($INSERTResult=$db->query('INSERT INTO `myPHPgames` (`id` ,`creator` ,`currentplayers` ,`maxplayers`, `created`) VALUES ("'.$_POST['gameid'].'" , "'.$_POST['username'].'", 1, "'.$_POST['maxplayers'].'", NULL)')){
                            if($SELECTResult=getUserByUsername($_POST['username'])){
                                if($SELECTResult->num_rows){
                                    $player=$SELECTResult->fetch_object();
                                    if($UPDATEResult=$db->query('UPDATE `myPHPusers` SET `money`=7500,`gameid`="'.$_POST['gameid'].'" WHERE `username`="'.$_POST['username'].'"')){
                                        if($SELECTResult=getGameByID($player->gameid)){
                                            if($SELECTResult->num_rows){
                                                $oldGame=$SELECTResult->fetch_object();
                                                $decr=$oldGame->currentplayers-1;
                                                if($UPDATEResult=$db->query('UPDATE `myPHPgames` SET `currentplayers`='.$decr.' WHERE `id`="'.$player->gameid.'"')){
                                                    if($decr==0){
                                                        $db->query('DELETE FROM `myPHPgames` WHERE `id` = "'.$player->gameid.'"');
                                                    }
                                                } else {
                                                    echo formatError('UPDATE Error!'.$db->error);
                                                }
                                            }
                                        } else {
                                            echo formatError('SELECT Error!'.$db->error);
                                        }
                                    } else {
                                        echo formatError('UPDATE Error!'.$db->error);
                                    }
                                } else {
                                    $db->query('INSERT INTO `myPHPusers` (`id` ,`username` ,`money` ,`gameid`, `created`) VALUES (NULL , "'.$_POST['username'].'", "7500", "'.$_POST['gameid'].'", NULL)');
                                }
                                $_SESSION['gameid']=$_POST['gameid'];
                                $_SESSION['username']=$_POST['username'];
                                doGame();
                            } else {
                                echo formatError('SELECT Error!'.$db->error);
                            }
                        } else {
                            echo formatError('INSERT Error!'.$db->error);
                        }
                    }
                } else {
                    echo formatError('SELECT Error!'.$db->error);
                }
            } else {
                echo formatError('Please choose how many players can enter your game!!');
                showMainMenu();
            }
        } else {
            echo formatError('Invalid Game ID filled in!!');
            showMainMenu();
        }
    } else {
        echo formatError('Invalid Username filled in!!');
        showMainMenu();
    }
} else {
    echo formatError("Welcome! You can either Join an existing game if you know the Game ID, or start a new one!");
    showMainMenu();
}

?>
