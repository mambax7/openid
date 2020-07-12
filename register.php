<?php
require_once('occommon.php');
$response=@$_SESSION['openid_response'];
$xoopsOption['template_main'] = 'openid_generic.html';
if (@$response->status != Auth_OpenID_SUCCESS) {
	$msg= "Go Away!";
	$xoopsTpl->assign('msg',$msg);
	include_once(XOOPS_ROOT_PATH.'/footer.php');
	exit;
} else {
    $displayId = $response->getDisplayIdentifier();
    $cid = $response->identity_url;
}

$err='';
$msg='';

$sreg=$_SESSION['openid_sreg'];
include_once(XOOPS_ROOT_PATH.'/header.php');

$tzoffset=array('Tokyo'=>'+9','London'=>'0');

$uname = alphaonly($_POST['uname']);

if (strlen($uname)<3 ){ // Username too short. 
	$msg  ='<h3 class="centerLblockTitle">スクリーン名のエラー</h3>';
	$msg .="<p>" . $uname . " は、短すぎます。<br />別のスクリーン名をお選びください。</p>\n";
	$msg .= '<form method="POST" action="register.php" />';
	$msg .= 'スクリーン名：<input type="text" name="uname" />';
	$msg .= '<input type="submit" value="登録"></form>';
	$xoopsTpl->assign('msg',$msg);
	include_once(XOOPS_ROOT_PATH.'/footer.php');
}

$criteria = new CriteriaCompo(new Criteria('uname', $uname ));
$user_handler =& xoops_gethandler('user');
$users =& $user_handler->getObjects($criteria, false);

if( empty( $users ) || count( $users ) != 1) { 
	// print "<p>user does not exists, so create it. </p>";
	// createuser($openid);
	$username = quote_smart($uname);
	$email = quote_smart($sreg['email']);
	$name = quote_smart($sreg['fullname']);
	$tz = quote_smart($tzoffset[$sreg['timezone']]);
	$country = quote_smart($sreg['country']);
	$timenow = quote_smart(time());
	$dispId4sql = quote_smart($displayId);
	$cid4sql = quote_smart($cid);

	$t_user = $xoopsDB->prefix("users");
	$t_groups_users_link = $xoopsDB->prefix("groups_users_link");
	$query = "INSERT into $t_user 
                SET 
                uname=$username, 
                email=$email, 
                name=$name,
                pass='*', 
                user_regdate=$timenow , 
                timezone_offset=$tz, 
                user_from=$country
                " ;
    //echo $query; exit;
	$xoopsDB->queryF($query);
	$users =& $user_handler->getObjects($criteria, false);
	$user = $users[0] ;
	
	// Now, add the user to the group. 
	$uid = $user->getVar('uid');
	$query2 = "INSERT INTO $t_groups_users_link 
	(`linkid`,`groupid`,`uid`) 
	VALUES (NULL, '2', '$uid');"   ;
	$xoopsDB->queryF($query2);

	unset( $users ) ;
	
	// Then, add the openid_localid
	$query="INSERT into ". $xoopsDB->prefix('openid_localid') . 
			"   SET 
			`localid`=$username, 
			`openid`=$cid4sql, 
			`displayid`=$dispId4sql, 
			`created`=NOW()";
	//echo "\n<br />" . $query;
	$res=$xoopsDB->queryF($query);
	$err .= $xoopsDB->error();
	
	$msg = $msg . '<br />openid_localid 登録終了';
	
	// Login with this user. 
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
		unset($_SESSION['openid_response']);
		unset($_SESSION['openid_sreg']);
		print_r($_SESSION['openid_response']);
    header("Location: " . $_SESSION['frompage']);
    unset($_SESSION['frompage']);
	}
} else { // Username Collided. 
	$msg="<br />" . $uname . " は、既に他の方が使われています。<br />別のスクリーン名をお選びください。<br />\n";
	$msg .= '<form method="POST" action="register.php" />';
	$msg .= 'スクリーン名：<input type="text" name="uname" />';
	$msg .= '<input type="submit" value="登録"></form>';
	$xoopsTpl->assign('msg',$msg);
	include_once(XOOPS_ROOT_PATH.'/footer.php');
}


?>
