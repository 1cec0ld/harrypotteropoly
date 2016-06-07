<?php
echo '<title>HP Monopoly</title>';
require 'sql_connect.php';
require 'bertiebotts.php';

session_start();

const GALLEONS = 493;
const SICKLES = 29;
const KNUTS = 1;

require 'functions.php';
function formatError($str){
    return '<strong>'.$str.'</strong><br>';
}
function showMainMenu(){
    session_destroy();
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
function debugQuery($statement){
    global $db;
    if($result=$db->query($statement)){
        return $result;
    } else {
        logError($db->error);
        session_destroy();
        die('Fatal error');
    }
}
function logError($data){
    $file=fopen('HPLOG.log','a');
    fwrite($file,PHP_EOL .$data);
    fwrite($file,PHP_EOL .'POST Dump: '.var_export($_POST,true).''.PHP_EOL .'| SESSION Dump: '.var_export($_SESSION,true).PHP_EOL);
    fclose($file);
}
function listGames(){
    $results=debugQuery('SELECT * FROM myPHPgames');
    while($row=$results->fetch_object()){
        echo '<span style="background-color:white">'.$row->id.' has '.$row->currentplayers.'/'.$row->maxplayers.' people playing.</span><br>';
        $gameresults=debugQuery('SELECT `username`,`color` FROM myPHPusers WHERE `gameid`="'.$row->id.'"');
        while($gamerow=$gameresults->fetch_object()){
            echo '<span style="margin-left:35px;background-color:'.$gamerow->color.'">    '.$gamerow->username.'</span><br>';
        }
    }
}
function printHPMoney($input){
    $results = array();
    $money=abs($input);
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
function createGame($username,$gameid,$maxplayers){
    $result=debugQuery('SELECT `id` from `myPHPgames` WHERE `id`="'.$gameid.'"');
    if($result->num_rows){
        echo formatError('Game already exists! Try to Join instead!');
        showMainMenu();
    } else {
        $userResult=debugQuery('SELECT `gameid` FROM `myPHPusers` WHERE `username`="'.$username.'"');
        if($userResult->num_rows){
            $container=$userResult->fetch_object();
            $oldresult=debugQuery('SELECT `creator` FROM `myPHPgames` WHERE `id`="'.$container->gameid.'"');
            if($oldresult->num_rows){
                $creatorObject=$oldresult->fetch_object();
                if($username==$creatorObject->creator){
                    deleteGame($container->gameid);
                } else {
                    removeUser($username,$container->gameid);
                }
            }
        } else {
            debugQuery('INSERT INTO `myPHPusers` (`id` ,`username` ,`money` ,`gameid`, `created`, `position`, `jailcount`, `doublescount`) VALUES (NULL , "'.$username.'", "7500", "", NULL, "0","0","0")');
        }
        //create gameMaster for new game
        debugQuery('INSERT INTO `myPHPgames` (`id` ,`creator` ,`currentplayers` ,`maxplayers`, `created`) VALUES ("'.$gameid.'" , "'.$username.'", 1, "'.$maxplayers.'", NULL)');
        //create beans for new game
        debugQuery('CREATE TABLE `'.$gameid.'Beans` LIKE `myPHPbeans`');
        debugQuery('INSERT `'.$gameid.'Beans` SELECT * FROM `myPHPbeans`');
        debugQuery('ALTER TABLE `'.$gameid.'Beans` ADD `owner` VARCHAR( 16 ) NOT NULL');
        //create wizard cards for new game
        /*
        debugQuery('CREATE TABLE `'.$gameid.'Wizards` LIKE `myPHPwizards`');
        debugQuery('INSERT `'.$gameid.'Wizards` SELECT * FROM `myPHPwizards`');
        debugQuery('ALTER TABLE `'.$gameid.'Wizards` ADD `owner` VARCHAR( 16 ) NOT NULL');
        */
        //create properties for new game
        debugQuery('CREATE TABLE `'.$gameid.'Properties` LIKE `myPHPproperties`');
        debugQuery('INSERT `'.$gameid.'Properties` SELECT * FROM `myPHPproperties`');
        
        //join player into game
        debugQuery('UPDATE `myPHPusers` SET `jailcount`=0,`doublescount`=0,`position`=0,`money`=7500,`gameid`="'.$gameid.'" WHERE `username`="'.$username.'"');
        $_SESSION['gameid']=$gameid;
        $_SESSION['username']=$username;
        echo formatError('Created Game!');
        doGame($username,$gameid);
    }
}
function joinGame($username,$gameid){
    $result=debugQuery('SELECT `currentplayers`,`maxplayers` from `myPHPgames` WHERE `id`="'.$gameid.'"');
    if($result->num_rows){
        $countObject=$result->fetch_object();
        $userResult=debugQuery('SELECT `gameid` FROM `myPHPusers` WHERE `username`="'.$username.'"');
        if($userResult->num_rows){
            $userData=$userResult->fetch_object();
            if($gameid==$userData->gameid){
                $_SESSION['gameid'] = $_POST['gameid'];
                $_SESSION['username'] = $_POST['username'];
                echo formatError('Joined Game!');
                doGame($username,$gameid);
            } else if($countObject->currentplayers<$countObject->maxplayers){
                $newCount=$countObject->currentplayers+1;
                debugQuery('UPDATE `myPHPgames` SET `currentplayers`='.$newCount.' WHERE `id`="'.$gameid.'"');
                debugQuery('UPDATE `myPHPusers` SET `jailcount`=0,`doublescount`=0,`position`=0,`money`=7500,`gameid`="'.$gameid.'" WHERE `username`="'.$username.'"');
                $_SESSION['gameid'] = $_POST['gameid'];
                $_SESSION['username'] = $_POST['username'];
                echo formatError('Joined Game!');
                doGame($username,$gameid);
            } else {
                echo formatError('Game is full!');
                showMainMenu();
            }
        } else if($countObject->currentplayers<$countObject->maxplayers){
            debugQuery('INSERT INTO `myPHPusers` (`id` ,`username` ,`money` ,`gameid`, `created`,`position`, `jailcount`, `doublescount`) VALUES (NULL , "'.$username.'", "7500", "'.$gameid.'", NULL, "0", "0","0")');
            $newCount=$countObject->currentplayers+1;
            debugQuery('UPDATE `myPHPgames` SET `currentplayers`='.$newCount.' WHERE `id`="'.$gameid.'"');
            $_SESSION['gameid'] = $_POST['gameid'];
            $_SESSION['username'] = $_POST['username'];
            echo formatError('Joined Game!');
            doGame($username,$gameid);
        } else {
            echo formatError('Game is full!');
            showMainMenu();
        }
    } else {
        echo formatError("Game doesn't exist! Try to Create it instead!");
        showMainMenu();
    }
}
function deleteGame($gameid){
    //assume the game exists
    
    //delete all beans
    debugQuery('DROP TABLE '.$gameid.'Beans');
    //delete all wizard cards
    /*debugQuery('DROP TABLE '.$gameid.'Wizards');*/
    //delete all properties
    debugQuery('DROP TABLE '.$gameid.'Properties');
    //erase the gameMaster
    debugQuery('DELETE FROM `myPHPgames` WHERE `id`="'.$gameid.'"');
    //update all users with that gameid to no gameid and 7500 money
    debugQuery('UPDATE `myPHPusers` SET `jailcount`=0,`doublescount`=0,`position`=0,`money`=7500,`gameid`="" WHERE `gameid`="'.$gameid.'"');
}
function removeUser($username,$gameid){
    //assume the user exists
    //assume the game exists
    
    //get amount of current players
    $result=debugQuery('SELECT `currentplayers`,`creator` FROM `myPHPgames` WHERE `id`="'.$gameid.'"');
    $gameData=$result->fetch_object();
    //if there are still players after this operation
    if($gameData->currentplayers > 1 and $username!=$gameData->creator){
        //decrement gameMaster's currentplayer count
        $newplayers=$gameData->currentplayers-1;
        debugQuery('UPDATE `myPHPusers` SET `gameid`="" WHERE `username`="'.$username.'" AND `gameid`="'.$gameid.'"');
        debugQuery('UPDATE `myPHPgames` SET `currentplayers`='.$newplayers.' WHERE `id`="'.$gameid.'"');
        //remove their name from every bean
        debugQuery('UPDATE `'.$gameid.'Beans` SET `owner`="" WHERE `owner`="'.$username.'"');
        //remove their name from every wizard
        /*debugQuery('UPDATE `'.$gameid.'Wizards` SET `owner`="" WHERE `owner`="'.$username.'"');*/
        //remove every upgrade from their properties
        /*TO BE FIGURED OUT AFTER PROPERTIES EXIST*/
        //remove their name from every property
        debugQuery('UPDATE `'.$gameid.'Properties` SET `owner`="" WHERE `owner`="'.$username.'"');
    } else {
        //otherwise delete the entire game
        deleteGame($gameid);
    }
}
function printBoard($gameArray){
    echo "<table style='float:right;height:95vh;width:95vh;' border='1'>
                <tr style='height:9.5vh'>
                    <td style='width:9.5vh'></td>
                    
                    <td style='width:10.34vh'></td>
                    
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    
                    <td></td>
                    
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    
                    <td style='width:10.34vh'></td>
                    
                    <td style='width:9.5vh'></td>
                </tr>
                <tr style='height:10.76vh'>
                    <td></td>
                    
                    <td rowspan='11' colspan='11'><img style='height:76vh;' src='complete.png'></td>
                    
                    <td style='width:9.5vh'></td>
                </tr>
                <tr>
                    <td></td>
                    
                    <td style='width:9.5vh'></td>
                </tr>
                <tr>
                    <td></td>
                    
                    <td style='width:9.5vh'></td>
                </tr>
                <tr>
                    <td></td>
                    
                    <td style='width:9.5vh'></td>
                </tr>
                <tr>
                    <td></td>
                    
                    <td style='width:9.5vh'></td>
                </tr>
                <tr>
                    <td></td>
                    
                    <td style='width:9.5vh'></td>
                </tr>
                <tr>
                    <td></td>
                    
                    <td style='width:9.5vh'></td>
                </tr>
                <tr>
                    <td></td>
                    
                    <td style='width:9.5vh'></td>
                </tr>
                <tr>
                    <td></td>
                    
                    <td style='width:9.5vh'></td>
                </tr>
                <tr>
                    <td></td>
                    
                    <td style='width:9.5vh'></td>
                </tr>
                <tr style='height:10.76vh'>
                    <td></td>
                    
                    <td></td>
                </tr>
                <tr style='height:9.5vh'>
                    <td style='width:9.5vh'></td>
                    
                    <td style='width:10.34vh'></td>
                    
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    
                    <td></td>
                    
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    
                    <td style='width:10.34vh'></td>
                    
                    <td style='width:9.5vh'></td>
                </tr>
            </table>";
}

function doGame($username,$gameid){
    global $database;
    header('Refresh:15');
    echo "<div style='width:80vw'>";
    printBoard(1);
    $gameBeans=loadBeans($gameid);
    if(isset($_POST['color']) and !empty($_POST['color'])){
        debugQuery('UPDATE myPHPusers SET `color`="'.$_POST['color'].'" WHERE `username`="'.$username.'"');
    }
    if(isset($_POST['deleteGame'])){
        //clear every player with that gameid
        deleteGame($gameid);
        showMainMenu();
        return;
    }
    if($currentgame=debugQuery('SELECT * FROM `myPHPgames` WHERE `id`="'.$gameid.'"')){
        if ($currentgame->num_rows) {
            $game=$currentgame->fetch_object();
        } else {
            showMainMenu();
            return;
        }
    }
    $currentuser = debugQuery('SELECT * FROM `myPHPusers` WHERE `username`="'.$username.'" AND `gameid`="'.$gameid.'"');
        if ($currentuser->num_rows) {
            $player=$currentuser->fetch_object();            
            $currenttile = debugQuery('SELECT * FROM `'.$gameid.'Properties` WHERE `id`="'.$player->position.'"');
            if ($currenttile->num_rows){
                $property=$currenttile->fetch_object();
            }
        } else {
            showMainMenu();
            return;
        }

        $userMoney=$player->money;
        //if (isset($_POST['go'])){
        //    $userMoney += toNormalMoney(2,0,14);
        if(isset($_POST['diceRoll'])){
            processRoll($player);
        } else if(isset($_POST['reset'])){
            debugQuery('UPDATE myPHPusers SET `money`=7500 WHERE `gameid`="'.$gameid.'"');
        } else if (isset($_POST['buyProp'])){
            if($player->money > $property->purchaseprice){
                debugQuery('UPDATE `'.$gameid.'Properties` SET `owner`="'.$username.'" WHERE `id`="'.$player->position.'"');
            } else {
                echo '<span style="border:3px solid crimson">Not Enough Funds!</span><br>';
            }
        } else if(isset($_POST['send'])){
            if(isset($_POST['toWhom'])){
                if(isset($_POST['galleonsTo']) and isset($_POST['sicklesTo']) and isset($_POST['knutsTo'])){
                    $subtotal=toNormalMoney($_POST['galleonsTo'], $_POST['sicklesTo'], $_POST['knutsTo']);
                    if( $subtotal < 0){
                        echo '<span style="border:3px solid crimson">Gringotts disapproves of your attempted treachery...</span><br>';
                    } else if( $subtotal <= $player->money ){
                        echo '<span style="border:3px solid '.$player->color.'">Money Sent!</span><br>';
                        sendMoney($username,$_POST['toWhom'],$subtotal);
                    } else {
                        echo '<span style="border:3px solid crimson">Not Enough Funds!</span><br>';
                    }
                }
            }
        } else if(isset($_POST['setMoney'])){
            if(isset($_POST['forWhom'])){
                if(isset($_POST['galleonsSet']) and isset($_POST['sicklesSet']) and isset($_POST['knutsSet'])){
                    $subtotal=toNormalMoney($_POST['galleonsSet'], $_POST['sicklesSet'], $_POST['knutsSet']);
                    if( $subtotal < 0){
                        echo '<span style="border:3px solid crimson">Gringotts disapproves...</span><br>';
                    } else {
                        echo '<span style="border:3px solid '.$player->color.'">Money Set!</span><br>';
                        debugQuery('UPDATE myPHPusers SET `money`='.$subtotal.' WHERE `username`="'.$_POST['forWhom'].'"');
                    }
                }
            }
        } else if(isset($_POST['drawBean'])){
            processBean($_SESSION['bean'],$username,$gameid);
        }
        $rando=array_rand($gameBeans);
        $_SESSION['bean']=$gameBeans[$rando];
        $currentuser = debugQuery('SELECT * FROM `myPHPusers` WHERE `username`="'.$username.'" AND `gameid`="'.$gameid.'"');
            if ($currentuser->num_rows) {
                $player=$currentuser->fetch_object();            
                $currenttile = debugQuery('SELECT * FROM `'.$gameid.'Properties` WHERE `id`="'.$player->position.'"');
                if ($currenttile->num_rows){
                    $property=$currenttile->fetch_object();
                }
            } else {
                showMainMenu();
                return;
            }
        $players=array();
        if($allUsers = debugQuery('SELECT `money`,`username`,`color`,`position`,`jailcount` FROM `myPHPusers` WHERE `gameid`="'.$gameid.'"')){
            if($allUsers->num_rows){
                echo /*'<div style="float:left">*/'<strong>Members of '.$gameid.':</strong><br>';
                while($row=$allUsers->fetch_object()){
                    $players[]=$row->username;
                    echo '<span style="margin:0px';
                    if($row->username==$username){
                        $player = $row;
                        echo ';background-color:'.$row->color.'';
                    }
                    echo '">';
                    $propRow=debugQuery('SELECT `name` FROM `'.$gameid.'Properties` WHERE `id`='.$row->position.'');
                    echo $row->username.' with '.printHPMoney($row->money).', at '.$propRow->fetch_object()->name.'.';
                    if($row->position==10){
                        echo $row->jailcount==0?' (Just Visiting)':' (Imprisoned)';
                    }
                    echo '</span><br>';
                }
            }
        }
        echo "<form action='index.php' method='POST'>";
        echo "    <input type='submit' name='diceRoll' value='Roll the Dice!'>";
        echo '    <input type="submit" name="buyProp" value="Buy this Property!" ';
        if($property->purchaseprice == 0 or $property->purchaseprice > $player->money){
            echo 'disabled';
        }
        echo '>';
        echo '    <input type="submit" name="drawBean" value="Draw A Bean!" style="background-color:'.$_SESSION['bean']->color.'" ';
        if($property->id != 7 and $property->id != 22 and $property->id != 36){
            echo 'disabled';
        }
        echo '><br>';
        echo '    Pay:    <input style="margin-left:10px" type="text" name="galleonsTo" size="1" value=0>-Galleons,
                    <input type="text" name="sicklesTo" size="1" value=0>-Sickles,
                    <input type="text" name="knutsTo" size="1" value=0>-Knuts, To:
                    <select name="toWhom">
                        <option disabled selected value=""> </option>';
                        foreach ($players as $name){
                            if($name!=$username){
                                echo '<option value="'.$name.'">'.$name.'</option>';
                            }
                        }
                    echo '</select>';
                    echo '<input type="submit" name="send" value="Send!">';
        echo "</form>";
    if($username == $database){
        echo "<form action='index.php' method='POST'>";
        echo '    Set Money To:    <input style="margin-left:10px" type="text" name="galleonsSet" size="1" value=0>-Galleons,
                    <input type="text" name="sicklesSet" size="1" value=0>-Sickles,
                    <input type="text" name="knutsSet" size="1" value=0>-Knuts, For:
                    <select name="forWhom">
                        <option disabled selected value=""> </option>';
                        foreach ($players as $name){
                            if($name!=$username){
                                echo '<option value="'.$name.'">'.$name.'</option>';
                            }
                        }
                    echo '</select>';
                    echo '<input type="submit" name="setMoney" value="Set!">';
        echo "</form>";
    }
    echo "<form action='index.php' method='POST'>";
    echo "    <input type='submit' name='logout' value='Log Out'>";
    echo "    <input type='submit' name='reset' value='Reset Game'>";
    if($username == $game->creator || $username == $database){
        echo "    <input type='submit' name='deleteGame' value='Delete The Game'>";
    }
    echo "    <br><input type='submit' name='changeColor' value='Customize Color'>";
    echo "    <input type='text' name='color' maxlength=12>";
    echo "</form>";
    echo "</div>";
    //echo "<a target='_blank' href='http://www.w3schools.com/colors/colors_names.asp'>Full Color List Here!</a></div>";
    //echo "<div><img height='800px' src='complete.png'></div>";
}

if(isset($_POST['logout'])){
    echo formatError('Logged out!');
    showMainMenu();
} else if(isset($_SESSION['gameid']) and isset($_SESSION['username'])){
    doGame($_SESSION['username'],$_SESSION['gameid']);
} else if(isset($_POST['join'])){
    if(isset($_POST['username']) and preg_match('/^[A-Za-z0-9]{1,16}$/',$_POST['username'])){
        if (isset($_POST['gameid']) and preg_match('/^[A-Za-z0-9]{1,6}$/',$_POST['gameid'])){
            echo "Joining... ";
            joinGame($_POST['username'],strtolower($_POST['gameid']));
        } else {
            echo formatError('Invalid Game ID filled in!');
            showMainMenu();
        }
    } else {
        echo formatError('Invalid Username filled in!');
        showMainMenu();
    }
} else if(isset($_POST['create'])){
    if(isset($_POST['username']) and preg_match('/^[A-Za-z0-9]{1,16}$/',$_POST['username'])){
        if (isset($_POST['gameid']) and preg_match('/^[A-Za-z0-9]{1,6}$/',$_POST['gameid'])){
            if(isset($_POST['maxplayers']) and $_POST['maxplayers'] > 1){
                echo "Creating... ";
                createGame($_POST['username'],strtolower($_POST['gameid']),$_POST['maxplayers']);
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
