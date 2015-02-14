<?php
/*
Plugin Name: Vantage Point
Plugin URI: http://www.getvantagepoint.com/vantage-point-wordpress-and-woocomerce-plugin/
Description: Friendly fraud protection using online video recordings with user metadata.
Version: Version: 1.0.1
Author: Vantage Point
Author URI: http://www.getvantagepoint.com
License: GPL2
 
    Copyright 2015 Vantage Point
 
    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License,
    version 2, as published by the Free Software Foundation.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA
    02110-1301  USA
*/
ob_start();

if ( is_admin() ){ 
	add_action( 'admin_menu', 'VantagePoint_menu' );
	add_action( 'admin_init', 'VantagePoint_register' );
	register_activation_hook( __FILE__,  'VantagePoint_activate' );
}

// Adding menu option in the admin control panel of wordpress.
function VantagePoint_menu() {
	add_menu_page( 'Vantage Point', 'Vantage Point', 'manage_options', 'vantagepoint', 'VantagePoint_options' , plugins_url('/assets/images/icon.png' ,  __FILE__) );
}

// registering sessings.
function VantagePoint_register() { 
	register_setting( 'vantage_script', 'vantage_header' );
	register_setting( 'vantage_script', 'vantage_footer' );  
	register_deactivation_hook( __FILE__, 'VantagePoint_deactivate' );
}

add_action( 'wp_footer', 'VantagePoint_insert_script' );

//insert recording script into footer.
function VantagePoint_insert_script () {
	global $wpdb;

	$table_name = $wpdb->prefix . 'vantagepoint';
	$result     = $wpdb->get_row("SELECT * from  $table_name limit 1");
	$vantage_id = $result->vantage_id;
	$vantage_seal = $result->vantage_seal;             
	$vantage_seal_image = '<div style="max-width:128px;	max-height:63px; margin:30px auto;" ><a href="#" onClick="window.open('."'".'https://s1.getvantagepoint.com/verified/verified.html?id='.$vantage_id."'".', '."'".'newwindow'."'".', '."'".'width=620, height=430,scrollbars=yes'."'".'); return false;" rel="nofollow" title="print"><img src="'. plugins_url("/assets/images/vp_seal" .$vantage_seal. ".png" ,  __FILE__) .'" border="0"></a></div>';
	
	if ($vantage_seal==0) { $vantage_seal_image=''; }

	echo ''.$vantage_seal_image.'
	<input type="hidden" name="vantage_tracking" id="tracking_id" value="" />
		<script type="text/javascript">
		var _vantage = _vantage || [];
		var WebsiteID = ' .  $vantage_id . ';
		var pluginUrl = "' . plugins_url() . '";
		pluginUrl += "/VantagePoint/assets/js/browsers.js";
		var ga = document.createElement("script"); ga.type = "text/javascript"; ga.async=true;
		ga.src = pluginUrl;
		var s = document.getElementsByTagName("script")[0]; 
		s.parentNode.insertBefore(ga, s);
	</script>' . "";
} 
	
// create vantage point table on activation.	
function VantagePoint_activate(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'vantagepoint';

    $sql = "CREATE TABLE $table_name (
      vantage_id int(11) NOT NULL AUTO_INCREMENT,
      vantage_email varchar(255) DEFAULT NULL,	  
      vantage_pwd varchar(50) DEFAULT NULL,
      vantage_tracking_id varchar(255) DEFAULT NULL,
      vantage_url varchar(255) DEFAULT NULL,
	  vantage_api_key varchar(25) DEFAULT NULL,
	  vantage_sec_key varchar(25) DEFAULT NULL,	  
      vantage_seal tinyint(1) DEFAULT 0,
	  vantage_status tinyint(1) DEFAULT 0,
      UNIQUE KEY vantage_id (vantage_id)
    );";
	
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// remove vantage point table when user deactivate the plugin

function VantagePoint_deactivate() {
	global $wpdb;
	$table_name = $wpdb->prefix . "vantagepoint";
	$result     = $wpdb->get_row("SELECT * from  $table_name limit 1");
	$vantage_id = $result->vantage_id;
	$api_key    = $result->vantage_api_key;
	$sec_key    = $result->vantage_sec_key;	

	$sql = "DROP TABLE IF EXISTS $table_name;"; // delete the table from wp_database
	$wpdb->query($sql);
	
	$deactivate=wp_remote_get('http://s1.getvantagepoint.com/app/framework/deactivate.php?website_id=' . $vantage_id."&api_key=".$api_key."&sec_key=".$sec_key);  // send the deactivation email to the user.
}

function encrypt_decrypt($action, $string) {
	
    $output = false;

    $encrypt_method = "AES-256-CBC";    // encryption method
	$secret_key = $GLOBALS['api_key'];  // API Key
	$secret_iv  = $GLOBALS['sec_key']; // Secret Key

    // hash
    $key = hash('sha256', $secret_key);
    
    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);

    if( $action == 'encrypt' ) {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    }
    else if( $action == 'decrypt' ){
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }

    return $output;
}

	//  this function shows the signup form or login to the vantagepoint dashboard with the encrypted user name or password
	function VantagePoint_options() {

	global $wpdb;
	$vantage_id = 0;
	$version = "1.0.1";
	$table_name = $wpdb->prefix . 'vantagepoint';
		
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'")==$table_name){ 
		$result     = $wpdb->get_row("SELECT * from  $table_name limit 1");
		$vantage_id = $result->vantage_id;
		$user_email = $result->vantage_email;
		$password   = $result->vantage_pwd;
		$api_key    = $result->vantage_api_key;
		$sec_key    = $result->vantage_sec_key;
	}	   
		
	if ($vantage_id!=0){
		$session_data = wp_remote_get('http://www.getvantagepoint.com/wp_dashboard/visitors101.php?usr=' . $user_email . '&pwd='.$password."&api_key=".$api_key."&sec_key=".$sec_key."&version=".$version);  // send request to get the sessions data
		wp_enqueue_style( 'mystyle', plugins_url('/assets/css/style.css' ,  __FILE__) );
		wp_enqueue_script( 'myscript', plugins_url('/assets/js/script.js',__FILE__));
		print($session_data['body']);
		
		
		$this_page = $_SERVER['REQUEST_URI'];
	 
		$page = $_POST['page'];
		
		
		if ($page==4){

    $embed = "embed";
	$website_id  = $_POST['website_id'];
	
	if ($_SERVER['HTTPS'] == 'on') { $scheme="https"; } else { $scheme="http"; }
	
	$website = $scheme."://".$_SERVER['HTTP_HOST'];
	$radio    = $_POST['radio'];
	$url     = "http://s1.getvantagepoint.com/app/framework/wp_protected.php";
	$response = wp_remote_post(
            $url,
            array(
                'body' => array(
					'embed'    => $embed,
                    'radio'    => $radio,
					'website_id'    => $website_id,
                    'website'  => $website
                )
            )
        );

	$response=$response['body']; 

	
	if ($response!=0) {
	$response = explode ("|", $response);
	$website_id = $response[0];	
	$radio = $response[1];	
$tablename = $wpdb->prefix . "vantagepoint";	

$rows_affected = $wpdb->query(
                              $wpdb->prepare("
                                             UPDATE {$tablename}
                                             SET  vantage_seal = %s
                                             WHERE vantage_id = $website_id;",
                                             $radio
                                             )
                              );
			
		
	header('Location: #');

		
	} else {
		
	header('Location: #');
	
	}
	
	
}

if ($page==5){
    $embed = "skip";
	$website_id  = $_POST['website_id'];
	$url     = "http://s1.getvantagepoint.com/app/framework/wp_protected.php";
	$response = wp_remote_post(
            $url,
            array(
                'body' => array(
					'embed'    => $embed,
					'website_id'    => $website_id
                )
            )
        );

 
	$response=$response['body']; 
	
	if ($response!=0) {
		
	header('Location: #');

		
	}
	
	
}


if ($page==6){
    $embed = "remove";
	$website_id  = $_POST['website_id'];
	$url     = "http://s1.getvantagepoint.com/app/framework/wp_protected.php";
	$response = wp_remote_post(
            $url,
            array(
                'body' => array(
					'embed'    => $embed,
					'website_id'    => $website_id
                )
            )
        );

 
	$response=$response['body']; 
	
	if ($response!=0) {

$website_id = $response;	
$tablename = $wpdb->prefix . "vantagepoint";	

$rows_affected = $wpdb->query(
                              $wpdb->prepare("
                                             UPDATE {$tablename}
                                             SET  vantage_seal = %s
                                             WHERE vantage_id = $website_id;",
                                             0
                                             )
                              );
		
	header('Location: #');

		
	}
	
	
}

		
		
		
	} else {
		wp_enqueue_style( 'mystyle', plugins_url('/assets/css/style.css' ,  __FILE__) );
	 
		$this_page = $_SERVER['REQUEST_URI'];
	 
		$page = $_POST['page'];
	 
		 echo '<div class="div_content">
        <br/>    
        <table width="950" border="0" cellspacing="0" align="center" cellpadding="0">
          <tr>
            <td width="300"><img src='. plugins_url("/assets/images/logo.png" ,  __FILE__) . ' width="138" height="141"></td>
            <td width="358">&nbsp;</td>
            <td width="292" class="title_1" valign="top">Friendly Fraud Protection</td>
          </tr>
        </table>';

		echo '<div class="content_area">
				<span class="title_2">Welcome to Vantage Point Setup</span>
		        <br /> <br />
				<span class="txt_1"> 
	           	Vantage Point helps you combat friendly fraud by creating online video recordings of customers placing the orders on your  website, every click, scroll, key press, and tap is recorded along with device fingerprint, time and date and IP location data making it a complete and unique recording. 
            
            <br/><br/>Vantage Point is a hosted service meaning that parts of the service such as recordings are stored on vantage point servers.  We  actually do the heavy lifting on making the recording and saving the files on our servers. 
            
			<br/><br/><br/>';


		echo '<div class="form_div" id="SignupForm">';

		wp_enqueue_script( 'myscript', plugins_url('/assets/js/script.js',__FILE__));
		
		if ( $page == NULL ) {	

			
			
			echo '
			<div id="tbl_form">
		<table width="540" border="0" cellspacing="0" align="center" cellpadding="0">
		  <tr>
			<td width="154">&nbsp;</td>
			<td width="386">&nbsp;</td>
		  </tr>
		  
		  <tr>
			<td colspan="2" class="title_4">			
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="left"><input name="" value="Create Free Account" type="button" class="create" onclick="new_user();" /></td>
    <td align="right"><input name="" value="Existing User" type="button" class="existing" onclick="existing_user();" /></td>
  </tr>
</table>
			</td>
		  </tr>
		  <tr>
		    <td colspan="2" height="10"></td>
  		  </tr>
          
		  <tr>
			<td colspan="2">
            <!--show hide div start-->
            <div id="create_form">
            <form name="wp_signup" action="' . $this_page. '" method="post" onsubmit="return createVantageAccount();">   
			<input type="hidden" name="page" value="1" />
		    <input type="hidden" name="code" id="code" value="0">	
         <table width="100%" border="0" cellspacing="0" align="center" cellpadding="0">   	
		  <tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		  </tr>
		  
		  <tr>
			<td class="txt_3">Full Name</td>
			<td><input name="full_name" id="name" type="text" class="txt_field" /></td>
		  </tr>
		
		  <tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		  </tr>
		  
		  <tr>
			<td class="txt_3">Email Address</td>
			<td><input name="email" type="text" class="txt_field" id="company_name"/></td>
		  </tr>
		
		  <tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		  </tr>
			
		  <tr>
			<td class="txt_3">Password</td>
			<td><input name="password1" type="password" id="pass1" class="txt_field" /></td>
		  </tr>
		  
		  <tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		  </tr>
		
		  <tr>
			<td class="txt_3">Re-type Password</td>
			<td><input name="password2" type="password" id="pass2" class="txt_field" /></td>
		  </tr>
		  
		  <tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		  </tr>
		  
		  <tr>
			<td class="txt_3">Record this Domain </td>
			<td><input name="website_url" type="text" class="txt_field" id="website_url" value="'. $_SERVER['HTTP_HOST'] . '" disabled="disabled" />
			<input name="web_url" type="hidden" id="web_url" value="'. $_SERVER['HTTP_HOST'] . '"  /></td>
		  </tr>
		  
		  	
					
		  <tr height="10">
		    <td></td>
		    <td></td>
		    </tr>
		  <tr>
		    <td colspan="2"><div id="validate" style="color:#FF0000;"></div></td>
		    </tr>
		  <tr height="10">
			<td></td>
			<td></td>
		  </tr>
		  
		  <tr>
			<td>&nbsp;</td>
			<td align="right"><input name="" value="OK, Create My Account" type="submit" class="form_btn" /></td>
		  </tr>
          </table>
		 </form>
         </div>  <!--id = create_form -->
		 
		 
		 <div id="existing_form" style="display:none;" >
         <form name="wp_signup2" action="' . $this_page. '" method="post" onsubmit="return login_existing();">   
			<input type="hidden" name="page" value="2" />
		    <input type="hidden" name="code" id="code" value="0">  
         <table width="100%" border="0" cellspacing="0" align="center" cellpadding="0">   	
		  <tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		  </tr>
		  
		  <tr>
		    <td class="txt_3">Email Address</td>
		    <td align="right"><input name="email" type="text" class="txt_field" id="company_name1"/></td>
		    </tr>
		
		  <tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		  </tr>
			
		  <tr>
			<td class="txt_3">Password</td>
			<td align="right"><input name="password1" type="password" id="pass3" class="txt_field" /></td>
		  </tr>
		  
		  <tr height="10">
		    <td></td>
		    <td></td>
		    </tr>
		  <tr>
		    <td colspan="2"><div id="validate2" style="color:#FF0000;"></div></td>
		    </tr>
		  <tr height="10">
		    <td></td>
		    <td></td>
		    </tr>
		  
		  <tr>
		    <td><a onclick="forgot();" style="cursor:pointer">Forgot Password</a></td>
		    <td align="right"><input name="" value="Login" type="submit" class="form_btn" /></td>
		    </tr>
          </table>
		 </form>
         </div>  <!--id = existing_form-->
		 
		 <div id="forgot_form" style="display:none;">
         <form name="wp_signup3" action="' . $this_page. '" method="post" onsubmit="return forgot_page();"> 
		 	<input type="hidden" name="page" value="3" />
		    <input type="hidden" name="code" id="code" value="0">
         <table width="100%" border="0" cellspacing="0" align="center" cellpadding="0">   	
		  <tr>
			<td colspan="2">&nbsp;</td>
			</tr>
		  <tr>
			<td colspan="2">Not to worry please tell us which email address you used when you created the account and we will send your login credentials.</td>
			</tr>
		  
		  <tr>
		    <td colspan="2" height="15"></td>
		    </tr>
		  <tr>

		    <td class="txt_3">&nbsp;</td>
		    <td>&nbsp;</td>
		    </tr>
		  <tr>
		    <td class="txt_3">Email Address</td>
		    <td align="right"><input name="email" type="text" class="txt_field" id="company_name"/></td>
		    </tr>
		
		  <tr height="10">
		    <td></td>
		    <td></td>
		    </tr>
		  <tr>
		    <td colspan="2"><div id="validate3" style="color:#FF0000;"></div></td>
		    </tr>
		  <tr height="10">
		    <td></td>
		    <td></td>
		    </tr>	  
		  <tr>
		    <td>&nbsp;</td>
		    <td align="right"><input name="" value="Send" type="submit" class="form_btn" /></td>
		    </tr>
          </table>
		 </form>
         </div>  <!--id = forgot_form-->
		 
		 
            <!--show hide div end-->
            </td>
		  </tr>
		
		  <tr>
			<td colspan="2"></td>
		  </tr>
		  
		</table>  
		</div> <!--id="tbl_form"-->       
		    ';
	}
if ($page==1){
	$name    = $_POST['full_name'];
	$email   = $_POST['email'];
	$pwd1    = $_POST['password1'];
	$website = $_POST['web_url'];
	
	$url     = "http://s1.getvantagepoint.com/app/framework/wp_signup.php";
	$response = wp_remote_post(
            $url,
            array(
                'body' => array(
                    'name'     => $name,
                    'email'    => $email,
                    'pwd'      => $pwd1,
                    'website'  => $website
                )
            )
        );


	$response=$response['body'];
	$response = explode ("|", $response);
	$website_id = $response[0];
	$api_key    = $response[1];	
	$sec_key    = $response[2];	
	
	$GLOBALS['api_key'] = $api_key;
	$GLOBALS['sec_key'] = $sec_key;
	
	$tablename = $wpdb->prefix . "vantagepoint";
	$wpdb->insert($tablename, 
	array( 
		'vantage_id'          => $website_id, 
		'vantage_email'       => encrypt_decrypt('encrypt',$email), 
		'vantage_pwd'         => encrypt_decrypt('encrypt',$pwd1), 				
		'vantage_sec_key'     => $sec_key,
		'vantage_api_key'     => $api_key,
		'vantage_url'         => $website,
		'vantage_status'      => 2
	), 
	array( 
		'%d', 
		'%s', 
		'%s', 
		'%s', 
		'%s', 				
		'%s', 								
		'%d'
	) 
);

	echo '<div class="form_div" id="FinishedSignup"> 
	<input type="hidden" name="gvp_response" id="gvp_response" />
	<div class="txt_sub">We have successfully created<br/>your account. You will receive<br/>an email confirmation shortly.</div>
	<center><input name="" value="Click here to finish setup" type="button" class="form_btn2" onclick="load_dashboard();" /></center>';
	echo '</div>';	
}



if ($page==2){
	$email   = $_POST['email'];
	$pwd1    = $_POST['password1'];
	$website = $_SERVER['HTTP_HOST'];
	
	$url     = "http://s1.getvantagepoint.com/app/framework/wp_existing.php";
	$response = wp_remote_post(
            $url,
            array(
                'body' => array(
                    'email'    => $email,
                    'pwd'      => $pwd1,
                    'website'  => $website
                )
            )
        );

 
	$response=$response['body']; 
	$response = explode ("|", $response);
	if ($response[0] != 0) {
	$website_id = $response[0];
	$api_key    = $response[1];	
	$sec_key    = $response[2];	
	
	$GLOBALS['api_key'] = $api_key;
	$GLOBALS['sec_key'] = $sec_key;
	
	$tablename = $wpdb->prefix . "vantagepoint";
	$wpdb->insert($tablename, 
	array( 
		'vantage_id'          => $website_id, 
		'vantage_email'       => encrypt_decrypt('encrypt',$email), 
		'vantage_pwd'         => encrypt_decrypt('encrypt',$pwd1), 				
		'vantage_sec_key'     => $sec_key,
		'vantage_api_key'     => $api_key,
		'vantage_url'         => $website,
		'vantage_status'      => 2
	), 
	array( 
		'%d', 
		'%s', 
		'%s', 
		'%s', 
		'%s', 				
		'%s', 								
		'%d'
	) 
);

	echo '<div class="form_div" id="FinishedSignup"> 
	<input type="hidden" name="gvp_response" id="gvp_response" />
	<div class="txt_sub2">logged in successfully</div>
	<center><input name="" value="Click here" type="button" class="form_btn2" onclick="load_dashboard();" /></center>';
	echo '</div>';	
	
	} else {
		
		
		echo '<div class="form_div" id="FinishedSignup"> 
	<input type="hidden" name="gvp_response" id="gvp_response" />
	<div class="txt_sub2">Email or Password is not correct</div>
	<center><a href="'.$this_page.'"><input name="" value="Try Again" type="button" class="form_btn2" /></a></center>';
	echo '</div>';	
	
	}

}





if ($page==3){

	$email   = $_POST['email'];
	$website = $_SERVER['HTTP_HOST'];
	$url     = "http://s1.getvantagepoint.com/app/framework/wp_forgot_password.php";
	$response = wp_remote_post(
            $url,
            array(
                'body' => array(
                    'email'    => $email,
                    'website'  => $website
                )
            )
        );

 
	$response=$response['body']; 
	//$response = explode ("|", $response);
	
	if ($response!=0) {
	echo '<div class="form_div" id="FinishedSignup"> 
	<input type="hidden" name="gvp_response" id="gvp_response" />
	<div class="txt_sub2">OK, email has been sent to '.$email.' please checks in a few moments, also check in the junk folders just in case. </div>
	<center><a href="'.$this_page.'"><input name="" value="Click here" type="button" class="form_btn2" /></a></center>';
	echo '</div>';
		
	} else {
		
	echo '<div class="form_div" id="FinishedSignup"> 
	<input type="hidden" name="gvp_response" id="gvp_response" />
	<div class="txt_sub2">Sorry, the email address '.$email.' is not in our database, 
<br/><br/>
If you cannot remember the email address you can overwrite the email address and password by creating a New Free Account. <br/>
All  recordings are linked to the your website domain not the individual users. </div>
	<center><a href="'.$this_page.'"><input name="" value="Click here" type="button" class="form_btn2" /></a></center>';
	echo '</div>';
	
	}
	
	
}

		
		
echo '</div>
<div style="width:300px; margin:0px 0px 0px 20px; height:auto; float:left;">

<br/><span class="title_3">The Free package has all these great features</span><br/><br/>

<span class="txt_2">
	<span><img src="'. plugins_url("/assets/images/bullet.png" ,  __FILE__) .'" width="15" height="21"></span> Rolling two weeks of recordings* <br/><br/>
	<span><img src="'. plugins_url("/assets/images/bullet.png" ,  __FILE__) .'" width="15" height="21"></span> 25,000 recordings per month <br/><br/>
	<span><img src="'. plugins_url("/assets/images/bullet.png" ,  __FILE__) .'" width="15" height="21"></span> Unlimited sharing  <br/><br/>
	<span><img src="'. plugins_url("/assets/images/bullet.png" ,  __FILE__) .'" width="15" height="21"></span> Unlimited viewing <br/><br/>
	<span><img src="'. plugins_url("/assets/images/bullet.png" ,  __FILE__) .'" width="15" height="21"></span> Free forever <br/><br/><br/><br/>

	<div class="txt_str">
        * Rolling two weeks of recordings means that you <br/>can view and share any recordings that have been <br/>made in the last two week window. The older <br/>recordings may be accessed via a small upgrade.
	</div>
    
</span>

</div>          

<div style="clear:both;"></div> 

<br/><br/><br/>

<span class="title_3">Other Information:</span>
    
<span class="txt_1"> <br/> 

	<ul>
		<li>This plugin has been developed to work with Wordpress version 3.8 and above.<br/><br/></li>
        <li>This plugin and service is compatible with Woocommerce version 2.0.10 and above.<br/><br/></li>
        <li>The plugin will install a small snippet script into the footer of your website pages. This is the vantage point recording script and will record all the pages of your website.<br/><br/></li>
        <li>There is no impact on your website performance by using this plugin.  As this is a hosted service we do all the processing away from your webserver.</li>
	</ul>
</span>
        </div>  <!--content_area-->
    	<br/><br/>
    </div>  <!--div_content-->
<br/><br/>
</div>        
';
} 
	}
?>