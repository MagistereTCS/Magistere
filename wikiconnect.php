<?php

require_once('config.php');

$debug_level = 2; // 0=debug, 1=error, 2=none
$url = optional_param('u', $CFG->wikiUrl, PARAM_URL);

if (strpos($url,'connexion') !== false)
{
  $url = $CFG->wikiUrlAccueil;
}

//$SESSION->wantsurl = $url;

//unset($SESSION->wantsurl);

if (!isloggedin()) {
  $url = $CFG->wwwroot.$_SERVER['REQUEST_URI'];
  mmcached_add('mmid_session_'.get_mmid().'_hub_redirection', $url);
  redirect($CFG->wwwroot);
}


$apiurl = $CFG->wikiUrl.$CFG->wikiApiPath;
global $wikic_cookie_jar;
$wikic_cookie_jar = tempnam($CFG->tempdir,'wikic');
$global_cookies = array();

function debug($level,$msg)
{
  global $debug_level, $CFG;
  if ($level > $debug_level)
  {
    echo $msg."\n";
  }
  if (file_exists($CFG->moodledataroot.'/logs/wikiconnect'))
  {
    $debugfilepath = $CFG->moodledataroot.'/logs/wikiconnect/tcs_wikiconnect_'.date('Y-m-d').'_debug.log';
    file_put_contents($debugfilepath,"#############################".date('Y-m-d_H:i:s')."\n".print_r($msg,true)."\n#############################\n",FILE_APPEND);
  }
}

debug(2,'<pre>');

function curlResponseHeaderCallback($ch, $headerLine) {
    global $global_cookies;
    if (preg_match('/^Set-Cookie:\s*(([^;=]*)=([^;]*))/mi', $headerLine, $cookie) == 1) {
        $global_cookies[$cookie[2]] = $cookie[3];
    }
    return strlen($headerLine);
}

//Envois d une requette POST
function process_post($url,$vars,$cookie_jar=false)
{
  global $CFG;
  if(is_array($vars))
  {
    $vars2 = '';
    foreach($vars as $key=>$value)
    {
      if (!empty($vars2)){ $vars2 .= '&'; }
      $vars2 .= urlencode($key).'='.urlencode($value);
    }
    $vars = $vars2;
  }

  $cprocess = curl_init($url);
  curl_setopt($cprocess, CURLOPT_POSTFIELDS, $vars);
  curl_setopt($cprocess, CURLOPT_SSL_VERIFYPEER, false);
//  if ($cookie != false)
//  {
//    echo '###COOKIE SET='.$cookie."###\n";
//    curl_setopt($cprocess, CURLOPT_COOKIE, $cookie);
//  }
  curl_setopt($cprocess, CURLOPT_HEADER, false);
  curl_setopt($cprocess, CURLOPT_POST, true);
  curl_setopt($cprocess, CURLOPT_USERAGENT, "Opera/9.80 (Windows NT 6.1; Win64; x64) Presto/2.12.388 Version/12.16");
  curl_setopt($cprocess, CURLOPT_RETURNTRANSFER, true);
  if ($CFG->wiki_proxyenable && !empty($CFG->wiki_proxyhost) && !is_proxybypass($CFG->wiki_proxyhost)) {
    if ($CFG->wiki_proxyport === '0') {
      curl_setopt($cprocess, CURLOPT_PROXY, $CFG->wiki_proxyhost);
    } else {
      curl_setopt($cprocess, CURLOPT_PROXY, $CFG->wiki_proxyhost.':'.$CFG->wiki_proxyport);
    }
  }
  //curl_setopt($cprocess, CURLOPT_FOLLOWLOCATION, true);
  if ($cookie_jar != false)
  {
    curl_setopt($cprocess, CURLOPT_COOKIEFILE, $cookie_jar);
    curl_setopt($cprocess, CURLOPT_COOKIEJAR, $cookie_jar);
  }
  global $global_cookies;
  $global_cookies = array();
  curl_setopt($cprocess, CURLOPT_HEADERFUNCTION, "curlResponseHeaderCallback");

  $ccode=curl_exec($cprocess);
  curl_close($cprocess);

  return $ccode;
}



function wiki_login($apiurl,$user,$password,$setcookies=false)
{
  global $wikic_cookie_jar, $CFG;
  $cookie_jar = '';
  if (isset($wikic_cookie_jar) && $setcookies == false)
  {
    $cookie_jar = $wikic_cookie_jar;
  }else{
    $cookie_jar = tempnam($CFG->tempdir,'wikic');
  }

  // Step 1 : get token
  $data = array(
    'lgname' => $user,
    'lgpassword' => $password
  );

//  debug(1,'####POST_REQUEST='.$apiurl.'?action=login&format=json'.'####DATA='.print_r($data,true)."####\n\n");
//  $res1 = process_post($apiurl.'?action=login&format=json',$data,$cookie_jar);
  debug(1,'####POST_REQUEST='.$apiurl.'?action=query&meta=tokens&type=login&format=json'."\n\n");
  $res1 = process_post($apiurl.'?action=query&meta=tokens&type=login&format=json',array(),$cookie_jar);

  debug(1,'####'.$res1.'####');

  $res1d = json_decode($res1);

  debug(1,print_r($res1d,true));

  // Step 2 : get user session
  $data = array(
    'lgtoken' => $res1d->query->tokens->logintoken,
    'lgname' => $user,
    'lgpassword' => $password,
//    'lgtoken' => $res1d->login->token,
  );

  debug(1,'####POST_REQUEST='.$apiurl.'?action=login&format=json'.'####DATA='.print_r($data,true)."####\n\n");
  $res2 = process_post($apiurl.'?action=login&format=json',$data,$cookie_jar);

  debug(1,'####'.$res2.'####');
global $global_cookies;
echo '###COOKIES='.print_r($global_cookies,true).'###';
  $login = json_decode($res2)->login;

  // remove temporary cookie file
  if (!isset($wikic_cookie_jar))
  {
    unlink($cookie_jar);
  }

  if (isset($login->result) && $login->result == 'Success')
  {
    if ($setcookies)
    {
      setcookie('magistere_wikiUserID',$login->lguserid,time()+86400,'/',$CFG->wikiCookieDomain,true,true);
      setcookie('magistere_wikiUserName',$login->lgusername,time()+86400,'/',$CFG->wikiCookieDomain,true,true);
if (isset($global_cookies['magistere_wiki_session'])) {
      setcookie('magistere_wiki_session',$global_cookies['magistere_wiki_session'],time()+84600,'/',$CFG->wikiCookieDomain,true,true);
}
if (isset($global_cookies['magistere_wiki_Token'])) {
      setcookie('magistere_wikiToken',$global_cookies['magistere_wiki_Token'],time()+86400,'/',$CFG->wikiCookieDomain,true,true);
}
//if (isset($global_cookies['']))
//      setcookie($lofix.'Token',$login->token,0,'/',$CFG->wikiCookieDomain,true,true);

//      setcookie($login->cookieprefix.'UserID',$login->lguserid,time()+3600,'/','magistere.education.fr',true,true);
//      setcookie($login->cookieprefix.'UserName',$login->lgusername,time()+3600,'/','magistere.education.fr',true,true);
//      setcookie($login->cookieprefix.'_session',$login->sessionid,0,'/','magistere.education.fr',true,true);

    }
    return true;
  }
  else
  {
    return $login->result;
  }
}


function wiki_addgroup($apiurl,$user,$groups)
{
  global $wikic_cookie_jar;
  $cookie_jar = '';
  if (isset($wikic_cookie_jar))
  {
    $cookie_jar = $wikic_cookie_jar;
  }else{
    $cookie_jar = tempnam($CFG->tempdir,'wikic');
  }
  // Step 1 : get token
  $data = array(
//    'ususer' => $user,
//    'ustoken' => 'userrights',
  );

  debug(1,'####POST_REQUEST='.$apiurl.'?action=query&meta=tokens&type=userrights&format=json'.'####DATA='.print_r($data,true)."####\n\n");
  $res1 = process_post($apiurl.'?action=query&meta=tokens&type=userrights&format=json',$data,$cookie_jar);

  debug(1,'####'.$res1.'####');

  $res1d = json_decode($res1);

  debug(1,print_r($res1d,true));

  // Step 2 : get user session
  $data = array(
    'user' => $user,
    'add' => $groups,
    'token' => $res1d->query->tokens->userrightstoken,
  );
  debug(1,'####POST_REQUEST='.$apiurl.'?action=userrights&format=json'.'####DATA='.print_r($data,true)."####\n\n");
  $res2 = process_post($apiurl.'?action=userrights&format=json',$data,$cookie_jar);

  debug(1,'####'.$res2.'####');

  $res2d = json_decode($res2);

  // remove temporary cookie file
  if (!isset($wikic_cookie_jar))
  {
    unlink($cookie_jar);
  }

  if (isset($res2d->userrights->user))
  {
    return true;
  }
  else
  {
    return $res2d->error;
  }
}


function wiki_createaccount($apiurl,$user,$password,$email,$realname,$reason,$language,$groups=false)
{
  global $wikic_cookie_jar;
  $cookie_jar = '';
  if (isset($wikic_cookie_jar))
  {
    $cookie_jar = $wikic_cookie_jar;
  }else{
    $cookie_jar = tempnam($CFG->tempdir,'wikic');
  }
  // Step 1 : get token
  $data = array(
    'name' => $user,
    'password' => $password,
    'email' => $email,
    'realname' => $realname,
    'reason' => $reason,
    'language' => $language,
  );

//  $res1 = process_post($apiurl.'?action=createaccount&format=json',$data,$cookie_jar);

  debug(1,'####POST_REQUEST='.$apiurl.'?action=query&meta=tokens&type=createaccount&format=json'."\n\n");
  $res1 = process_post($apiurl.'?action=query&meta=tokens&type=createaccount&format=json',array(),$cookie_jar);


  debug(1,'####'.$res1.'####');

  $res1d = json_decode($res1);

  debug(1,print_r($res1d,true));

  // Step 2 : get user session
  $data = array(
    'username' => $user,
    'password' => $password,
    'retype' => $password,
    'email' => $email,
    'realname' => $realname,
    'reason' => $reason,
//    'language' => $language,
    'createreturnurl' => 'https://pp-wiki-magistere.foad.hp.in.phm.education.gouv.fr',
    'createtoken' => $res1d->query->tokens->createaccounttoken,
  );

  $res2 = process_post($apiurl.'?action=createaccount&format=json',$data,$cookie_jar);

  debug(1,'####'.$res2.'####');

  $res2d = json_decode($res2);

  // remove temporary cookie file
  if (!isset($wikic_cookie_jar))
  {
    unlink($cookie_jar);
  }

  if (isset($res2d->createaccount->status) && $res2d->createaccount->status == 'PASS')
  {
    if ( $groups !== false )
    {
      return wiki_addgroup($apiurl,$user,$groups);
    }
    return true;
  }
  else
  {
    return $res2d->error;
  }
}




//print_r($USER);
//die;

//$USER_email = $USER->email;
//$USER_email = '';

if ($USER->email == '')
{
  throw new Exception('Aucune adresse mail n\'est ratachée à votre compte!<br/>La connexion au wiki est impossible!<br/>Veuillez contacter votre administrateur.', $USER->id);
  die;
}

//$user = $USER->username;
//$password = '';

$user = strtolower(str_replace('.fr','',str_replace('@','_',$USER->email)));
$password = hash('sha256', $CFG->wikiUsersPassSalt.strtolower($USER->email).$CFG->wikiUsersPassSalt);
$email = strtolower($USER->email);
$realname = ucfirst(strtolower($USER->firstname)).' '.ucfirst(strtolower($USER->lastname));
$reason = 'Auto-inscription by M@gistere';

// try wiki connection
$res = wiki_login($apiurl,$user,$password,true);
if ($res === true)
{
  // The login succeed, the user is already logged in.
  redirect($url);
}
  // if the error his different than NotExists, the script stop
else if ($res != 'Failed')
{
    echo "Error0: system unavailable";
    debug(2,print_r($res,true));
    die;
}


// The user does not exist, we have to create it

// Bot connect
$res = wiki_login($apiurl,$CFG->wikiApiUser,$CFG->wikiApiPass);

if ($res !== true)
{
  echo "Error1: system unavailable";
  debug(2,print_r($res,true));
  die();
}

$res = wiki_createaccount($apiurl,$user,$password,$email,$realname,$reason,'fr');

if ($res !== true)
{
  echo "Error2: system unavailable";
  debug(2,print_r($res,true));
  die();
}

$res = wiki_addgroup($apiurl,$user,'magistere-user');

if ($res !== true)
{
  echo "Error3: system unavailable";
  debug(2,print_r($res,true));
  die();
}

// try wiki connection
wiki_login($apiurl,$user,$password,true);
if ($res !== true)
{
  echo "Error4: system unavailable";
  debug(2,print_r($res,true));
  die();
}

//wiki_login($apiurl,$user,$password);

//wiki_createaccount($apiurl,$user,$password,'test@dummy.lan','test_user'.time(),'test','fr');

unlink($wikic_cookie_jar);

redirect($url);
