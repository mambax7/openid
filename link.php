<?php
//require('header.php');
include_once "occommon.php";
$xoopsOption['template_main'] = 'openid_generic.html';

$err='';
$msg='';

$response=$_SESSION['openid_response'];
if (@$response->status != Auth_OpenID_SUCCESS) {
	$msg= "Go Away!";
	$xoopsTpl->assign('msg',$msg);
	include_once(XOOPS_ROOT_PATH.'/footer.php');
	exit;
} 



include_once(XOOPS_ROOT_PATH.'/header.php');

$uname = alphaonly($_POST['uname']);
$pass = alphaonly($_POST['pass']);

$uname4sql = quote_smart($uname);
$pass4sql = quote_smart($pass);

$query='SELECT `uid`, `uname`, `pass` from ' . $xoopsDB->prefix('users') . 
	" WHERE `uname`=$uname4sql";
$res=$xoopsDB->query($query,1);
//echo "<pre>\n";
$err .= $xoopsDB->error();
$xusers=$xoopsDB->fetchArray($res);
//print_r($xusers);
$xuser=$xusers; unset($xusers);
//echo "</pre>";
if($xuser['pass'] != md5($pass)){
	$err .= 'username or password invalid.';
	$xoopsOption['template_main'] = 'openid_new_user.html';
	include_once(XOOPS_ROOT_PATH.'/footer.php');
	exit;
} else if ($response->status == Auth_OpenID_SUCCESS) {
	//$msg = 'Auth_OpenID_SUCCESS';
	//echo "uname/password OK. Auth_OpenID_SUCCESS";
    // This means the authentication succeeded.
    $displayId = $response->getDisplayIdentifier();
    $cid = $response->identity_url;
    
    $dispId4sql = quote_smart($displayId);
    $cid4sql = quote_smart($cid);

	$query="INSERT into ". $xoopsDB->prefix('openid_localid') . 
			" SET 
			`localid`=$uname4sql, 
			`openid`=$cid4sql, 
			`displayid`=$dispId4sql, 
			`created`=NOW()";
	//echo "\n<br />" . $query;
	$res=$xoopsDB->queryF($query);
	$err .= $xoopsDB->error();
	
	$msg = $msg . '<br />openid_localid ÅÐÏ¿½ªÎ»';
	
	$criteria = new CriteriaCompo(new Criteria('uname', $uname ));
	$user_handler =& xoops_gethandler('user');
	$users =& $user_handler->getObjects($criteria, false);
	$user = $users[0] ;
	unset( $users ) ;
	$xoopsUser = $user;
	if (false != $user && $user->getVar('level') > 0) {
		$member_handler =& xoops_gethandler('member');
		$user->setVar('last_login', time());
		if (!$member_handler->insertUser($user)) {
		}
		$_SESSION['xoopsUserId'] = $user->getVar('uid');
		$_SESSION['xoopsUserGroups'] = $user->getGroups();
		$user_theme = $user->getVar('theme');
		if (in_array($user_theme, $xoopsConfig['theme_set_allowed'])) {
			$_SESSION['xoopsUserTheme'] = $user_theme;
		}
	}
	unset($_SESSION['openid_response']);
	unset($_SESSION['openid_sreg']);
	header("Location: " . $_SESSION['frompage']);
	unset($_SESSION['frompage']);
} else {
	echo "Sorry, Unknown Error Encountered. ";
}


$xoopsTpl->assign('msg', $msg);
$xoopsTpl->assign('err', $err);

include_once(XOOPS_ROOT_PATH.'/footer.php');

?>
