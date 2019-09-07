<?php


use app\assets\AppAsset;
use app\assets\CssJsAsset;
use app\assets\LoginAsset;
use yii\helpers\Html;

//AppAsset::register($this);
LoginAsset::register($this);
//
//$button['rectangle'] = "01";
//$button['square'] = "02";
//Html::a('url','https://steamcommunity-a.akamaihd.net/public/images/signinthroughsteam/sits_02.png')?>
<div id="center-login">
    <div id="a-steam-button">
<a  href='/auth/steam?login'><img src="https://steamcommunity-a.akamaihd.net/public/images/signinthroughsteam/sits_02.png" alt="error"></a>
    <div id="LoginAsset" ">
<!--        <h1>Авторизуйтесь для доступа в личный кабинет</h1>-->
<!--        <a href="/auth/steam?login"><button class="btn btn-success">Вход</button></a>-->
    </div>
    </div>

<?