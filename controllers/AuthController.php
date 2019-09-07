<?php


namespace app\controllers;


use app\controllers\base\SecuredController;


use app\models\LightOpenID;

use app\models\User;
use ErrorException;


use Yii;


use yii\web\Controller;
use yii\web\ServerErrorHttpException;

class AuthController extends Controller
{
    protected $steamconf = [];
    protected $steamProfile = [];

    private function steamConfig()
    {
        $this->steamconf['apikey'] = ""; // Your Steam WebAPI-Key found at https://steamcommunity.com/dev/apikey
        $this->steamconf['domainname'] = "" ; // The main URL of your website displayed in the login page
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
        if (empty($_SESSION['steam_uptodate']) || empty($_SESSION['steam_personaname'])) {
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

    public function actionLogin(){
        return $this->render('button');
    }

    public function actionSteam()
    {
        $this->steamConfig();
        /** Login  send with $_GET parameters (login) */
        if (isset($_GET['login'])) {
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
                    preg_match($ptn, $id, $matches);/** $matches[1] have your steam id*/
                    echo '<pre>';
                    print_r($matches);
                    exit();
                    /** Uncomment here. This is event handler if successful login*/
                    /* $user = User::find()->andWhere(['field in database' => $matches[1]])->all();
                     if(!$user){
                         exit();
                     }
                     Yii::$app->session->set('steamid', $matches[1]);
                     return $this->redirect(['site/index']);

                 } */


                }
            }
        }
    }
    public function actionLogout()
    {
        Yii::$app->session->destroy();
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
