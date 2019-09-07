<?php


namespace app\controllers;


use app\controllers\base\SecuredController;


use app\models\LightOpenID;
use app\models\Logs;
use app\models\Players;
use ErrorException;


use Yii;


use yii\web\ServerErrorHttpException;

class AuthController extends SecuredController
{
    protected $steamconf = [];
    protected $steamProfile = [];

    public function beforeAction($action)
    {


        if ($action->actionMethod == 'actionLogin')
            return parent::beforeAction($action);
//        elseif (isset($_SESSION['steamid']))
//            return $this->redirect(['site/index']);
        else
            return parent::beforeAction($action);
    }


    private function steamConfig()
    {
        $this->steamconf['apikey'] = "683BC7DF861D8A48C692483A58AEC39B"; // Your Steam WebAPI-Key found at https://steamcommunity.com/dev/apikey
        $this->steamconf['domainname'] = "http://lk.theranos-rpg.ru" ; // The main URL of your website displayed in the login page
        $this->steamconf['logoutpage'] = ""; // Page to redirect to after a successfull logout (from the directory the SteamAuth-folder is located in) - NO slash at the beginning!
        $this->steamconf['loginpage'] = ""; // Page to redirect to after a successfull login (from the directory the SteamAuth-folder is located in) - NO slash at the beginning!
        if (empty($this->steamconf['apikey'])) {
            die("<div style='display: block; width: 100%; background-color: red; text-align: center;'>SteamAuth:<br>Please supply an API-Key!<br>Find this in steamauth/SteamConfig.php, Find the '<b>\$steamauth['apikey']</b>' Array. </div>");
        }
        if (empty($this->steamconf['domainname'])) {
            $this->steamconf['domainname'] = $_SERVER['SERVER_NAME'];
        }
        if (empty($this->steamconf['logoutpage'])) {
            $this->steamconf['logoutpage'] = $_SERVER['PHP_SELF'];
        }
        if (empty($this->steamconf['loginpage'])) {
            $this->steamconf['loginpage'] = $_SERVER['PHP_SELF'];
        }

    }

    private function userInfo()
    {
        $this->steamConfig();
        if (empty($_SESSION['steam_uptodate']) or empty($_SESSION['steam_personaname'])) {
            //    require 'SteamConfig.php';
            $url = file_get_contents("https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . $this->steamconf['apikey'] . "&steamids=" . Yii::$app->session->get('steamid')/*$_SESSION['steamid']*/);
            $contentArr = json_decode($url, true);

            $content = $contentArr['response']['players'][0];

            $this->steamProfile['steamid'] = $content['steamid'];
            $this->steamProfile['communityvisibilitystate'] = $content['communityvisibilitystate'];
            $this->steamProfile['profilestate'] = $content['profilestate'];
            $this->steamProfile['personaname'] = $content['personaname'];
            $this->steamProfile['lastlogoff'] = $content['lastlogoff'];
            $this->steamProfile['profileurl'] = $content['profileurl'];
            $this->steamProfile['avatar'] = $content['avatar'];
            $this->steamProfile['avatarmedium'] = $content['avatarmedium'];
            $this->steamProfile['avatarfull'] = $content['avatarfull'];
            $this->steamProfile['personastate'] = $content['personastate'];
            if (isset($content['realname'])) {
                $this->steamProfile['realname'] = $content['realname'];
            } else {
                $this->steamProfile['realname'] = "Real name not given";
            }
            $this->steamProfile['primaryclanid'] = $content['primaryclanid'];
            $this->steamProfile['timecreated'] = $content['timecreated'];
            $this->steamProfile['uptodate'] = time();
        }


    }

    /**
     *
     */
    private function logoutbutton()
    {
        return "<form action='' method='get'><button name='logout' type='submit'>Logout</button></form>"; //logout button
    }

    /**
     * @param string $buttonstyle
     * @return string
     */
    private function loginbutton($buttonstyle = "square")
    {
        $button['rectangle'] = "01";
        $button['square'] = "02";


        return $this->render('button');
    }

    public function actionLogin()
    {
//        $this->layout = false;
        if (isset($_SESSION['steamid']))
            return $this->redirect(['site/index']);
        if(intval(Yii::$app->session->get('steamid')))
            return $this->redirect(['site/index']);
//        if(isset($_SESSION['steamid']))
//            unset($_SESSION['steamid']);

        return $this->render('button');
//        $this->userInfo();
    }

    public function actionSteam()
    {
        $this->steamConfig();
        if (isset($_GET['login'])) {
            // require 'openid.php';
            $openid = new LightOpenID($this->steamconf['domainname']);

            if (!$openid->mode) {
                $openid->identity = 'https://steamcommunity.com/openid';
                return $this->redirect($openid->authUrl());
            } elseif ($openid->mode == 'cancel')
                echo 'User has canceled authentication!';
            else {
                if ($openid->validate()) {
                    $id = $openid->identity;
                    $ptn = "/^https?:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/";
                    preg_match($ptn, $id, $matches);

                    //$_SESSION['steamid'] = $matches[1];
                    $player = Players::find()->andWhere(['playerid' => $matches[1]])->all();
                    if(!$player){
                        echo 'Чтобы войти в ЛК нужно зайти на сервер';
                        Logs::trace('Trying to get in '.$matches[1]);
                        exit();
                    }
                    Yii::$app->session->set('steamid', $matches[1]);
                    return $this->redirect(['site/index']);
                } else {
                    ?>
<!--                    <noscript>-->
<!--                        <meta http-equiv="refresh" content="0;url=--><?//= $this->steamconf['loginpage'] ?><!--"/>-->
<!--                    </noscript>-->
                    <?php

                }
            }


        }
    }

    public function actionLogout()
    {
        Yii::$app->session->destroy();
        Logs::trace('Player '.Yii::$app->session->get('steamid'). ' logout from lk');
        return $this->redirect(['auth/login']);

    }

    /**
     * @throws ServerErrorHttpException
     */
    public function actionError()
    {
        throw new ServerErrorHttpException('An internal server error.');
    }
}
