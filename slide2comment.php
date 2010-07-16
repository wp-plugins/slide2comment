<?php
/*
Plugin Name: Slide2Comment - Anti-Spam in a sexy way
Plugin URI: http://slide2comment.longhoang.de
Description: Adds a sexy iPhone lock slider to block spambots. If the slider hasn't been unlocked, the comment will be mark as spam.
Version: 1.7.1
Author: Long Hoang
Author URI: http://longhoang.de
License: http://creativecommons.org/licenses/by-nc-sa/3.0/de/

	My aim was it to build an anti-spam captcha which just look awesome and people have fun to use it. I found an 	iPhone-like slider at Aboone which is just what I've searched for. I implement a Wordpress captcha, which mark all comments as spam, if you haven't use the slider before. Hope you enjoy it!

	The sliders are from:
	http://www.aboone.com/javascript-iphone-lock-slider-with-jquery
*/

/**** Functions ****/

//Extend the function plugins_url for older versions
/*	if ( version_compare( $wp_version, '2.8dev', '<' ) ) {
		function plugins_url($path = '', $plugin = '') {
		  if ( function_exists('is_ssl') )
			$scheme = ( is_ssl() ? 'https' : 'http' );
		  else
			$scheme = 'http';
		  $url = WP_PLUGIN_URL;
		  if ( 0 === strpos($url, 'http') ) {
			if ( function_exists('is_ssl') && is_ssl() )
			  $url = str_replace( 'http://', "{$scheme}://", $url );
		  }

		  if ( !empty($plugin) && is_string($plugin) )
		  {
			$folder = dirname(plugin_basename($plugin));
			if ('.' != $folder)
			  $url .= '/' . ltrim($folder, '/');
		  }

		  if ( !empty($path) && is_string($path) && strpos($path, '..') === false )
			$url .= '/' . ltrim($path, '/');

		  return apply_filters('plugins_url', $url, $path, $plugin);
		}
	}*/

//Notify Mail
	function email_notify($comment) {     
        $email = get_bloginfo('admin_email');
		$blog = get_bloginfo('name');
		$body = @$comment['comment_content'];
		if (empty($email) || empty($blog) || empty($body)) {
			return;
		}
		wp_mail($email,
			sprintf('[%s] %s by Comment2Slide',$blog,__('Comment marked as spam')),
			sprintf("%s\n\n%s: %s",$body,__('Spam list'),admin_url('edit-comments.php?comment_status=spam'))
		);
	}

/**** Functions End ****/

/**** Hooks ****/

	function addCaptcha() {
		?>
		<div id="sexyslider"></div>
		<?
	}
	add_filter('comments_template', 'addCaptcha');

	function addCSS() {
		$path = plugins_url('img/', __FILE__ );
		?>
			<style type="text/css"> 
			<? if(get_option('s2c_css') == '') { ?>
				.track-center{
					background-image: url(<?=$path ?>track.png);
					height: 45px;
					margin: 0px 10px;
				}

				.track-left{
					width: 10px;
					height: 45px;
					float: left;
					background-image: url(<?=$path ?>trackleft.png);
				}

				.track-right{
					width: 10px;
					height: 45px;
					float: right;
					background-image: url(<?=$path ?>trackright.png);
				}

				.track-message{
					color:white;
					font-family:Arial,Helvetica,sans-serif;
					font-size:24px;
					padding:9px 18px;
					text-align:right;
				}

				.handle{
					background-image: url(<?=$path ?>handles.png);
					bottom:45px;
					cursor:pointer;
					height:39px;
					margin:3px 4px;
					position:relative;
					width:76px;
				}
			<? } else echo get_option('s2c_css'); ?>
			</style>
		<?
	}
	add_action('wp_print_styles', 'addCSS');

	function addJS(){
		$path =  plugins_url('', __FILE__ );
		?>
			<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js'></script>
			<script type='text/javascript' src='<?=$path ?>/slider.js'></script>
			<script type="text/javascript"> 
				$(document).ready(function(){
					$('#commentform').css('display', 'none');
					$('#commentform').after($('#sexyslider')); 
				
				  var sslider = new Slider("sexyslider",{
					  message: "Slide 2 Comment",
					  color: "<? if (get_option('s2c_color') != '') echo get_option('s2c_color'); else echo "green"; ?>",
					<? if(get_option('s2c_noClick') == 1) { ?>
					  mode: "noclick",
					<? } ?>
					  handler: function(){
						$('#commentform').css('display', 'block');
						$('#sexyslider').css('display', 'none');
						$('#commentform').append('<input type="hidden" name="add" value="<?=md5(get_bloginfo('url')) ?>">');
					  }
				  });
				  sslider.init();
				});
			</script>
		<?
	}
	add_action('wp_enqueue_scripts', 'addJS');
    
    function addMarker($comment){
        if($_POST['add'] != md5(get_bloginfo('url')) )
            $comment['comment_content'] = "=]MARKED AS SPAM BY SLIDE2COMMENT[=\n".$comment['comment_content'];
        return $comment;
    }
    add_filter('preprocess_comment', 'addMarker');
    
	function comment_approvement($id){
		global $commentdata;
		if($commentdata['comment_content'] == '')return true;
		if($_POST['add'] != md5(get_bloginfo('url')) ){
            
            if(get_option("s2c_eMails") != "no"){
		  	   email_notify($GLOBALS['commentdata']);
            }		
			wp_set_comment_status($id, 'spam');
		}
        
        return $commentdata;
	}
	add_action('comment_post', 'comment_approvement');
/**** Hooks End ****/

/**** Admin Menu ****/

function addAdminMenu() {

	// Add a new submenu under Options:
    add_options_page(
		'Slide2Comment - Antispam a sexy way', 
		'Slide2Comment', 
		'administrator', 
		's2c_admin_handle', 
		's2c_admin_page'
	);

	// register setting options
	function register_mysettings() {
		register_setting( 's2c-appearances-group', 's2c_color' );
		register_setting( 's2c-settings-group', 's2c_noClick' );
		//register_setting( 's2c-appearances-group', 's2c_css' );
	}

	// displays the page content for the Test Options submenu
	function s2c_admin_page() {

		// See if the user has posted us some information
		// If they did, this hidden field will be set to 'Y'
		if( $_POST[ 'fucking_add_settings' ] == 'Y' ) { 
			?>
			<div class="updated"><p><strong>Options saved.</strong></p></div>
			<?
		}

		if( $_POST[ 'fucki_add_css' ] == 'X' ) {		
			?>
			<div class="updated"><p><strong>Slider updated!</strong></p></div>
			<?
		}

		$path = plugins_url('img/', __FILE__ );
		?>
		<div class="wrap" style="width: 600px;">
			<h2>Admin Page</h2>

			<h3>Edit Slider Appearance</h3>
			Preview:<br />
				<div id="sexyslider"></div>
			<form name="form1" method="post" action="">
			 <?php settings_fields( 's2c-appearances-group' ); ?>
			
<textarea id="S2C_Style" name="css" cols="73" rows="30">
<? if(get_option('s2c_css') == '') { ?>
.track-center{
	background-image: url(<?=$path ?>track.png);
	height: 45px;
	margin: 0px 10px;
}

.track-left{
	width: 10px;
	height: 45px;
	float: left;
	background-image: url(<?=$path ?>trackleft.png);
}

.track-right{
	width: 10px;
	height: 45px;
	float: right;
	background-image: url(<?=$path ?>trackright.png);
}

.track-message{
	color:white;
	font-family:Arial,Helvetica,sans-serif;
	font-size:24px;
	padding:9px 18px;
	text-align:right;
}

.handle{
	background-image: url(<?=$path ?>handles.png);
	bottom:45px;
	cursor:pointer;
	height:39px;
	margin:3px 4px;
	position:relative;
	width:76px;
}
<? } else echo get_option('s2c_css'); ?>
</textarea><br />

            Color of the sliding element: 
            <select name="color">
                <option value="gray">Gray</option>
                <option value="green">Green</option>
                <option value="red">Red</option>
            </select>
            <input type="hidden" name="<?php echo 'fucki_add_css'; ?>" value="X" />
			<input type="submit" name="Submit" value="Submit" />
			</form>
			<hr />
			<h3>Slider Options</h3>
			<form name="form1" method="post" action="">
			 <?php settings_fields( 's2c-settings-group' ); ?>
			<input type="hidden" name="<?php echo 'fucking_add_settings'; ?>" value="Y" />

			<p>
			Move Slider without clicking: 
			<select name="noClick">
				<option value="0"<?=((get_option('s2c_noClick') == '0')?" selected=\"selected\"":"") ?>>No</option>
				<option value="1"<?=((get_option('s2c_noClick') == '1')?" selected=\"selected\"":"") ?>>Yes</option>
			</select><br />
            
            Receiving Emails?
            <select name="eMails">
				<option value="yes"<?=((get_option('s2c_eMails') == 'yes')?" selected=\"selected\"":"") ?>>Yes</option>
				<option value="no"<?=((get_option('s2c_eMails') == 'no')?" selected=\"selected\"":"") ?>>No</option>
			</select><br />

			<input type="submit" name="Submit" value="Submit" />
			</p>
			</form>
		</div>
		<?
	}
	
}
add_action('admin_menu', 'addAdminMenu');

function save_option(){
	if( $_POST[ 'fucki_add_css' ] == 'X' ){
		    update_option( 's2c_css', $_POST[ 'css' ] );
            update_option( 's2c_color', $_POST[ 'color' ] );
    }

	if( $_POST[ 'fucking_add_settings' ] == 'Y' ){
		    update_option( 's2c_noClick', $_POST[ 'noClick' ] );
		    update_option( 's2c_eMails', $_POST[ 'eMails' ] );
    }

}
add_action('admin_init', 'save_option');

function addAdminMenuCSS() {
	$path = plugins_url('img/', __FILE__ );
	?>
	<style type="text/css">
	<? if(get_option('s2c_css') == '') { ?>
		.track-center{
			background-image: url(<?=$path ?>track.png);
			height: 45px;
			margin: 0px 10px;
		}

		.track-left{
			width: 10px;
			height: 45px;
			float: left;
			background-image: url(<?=$path ?>trackleft.png);
		}

		.track-right{
			width: 10px;
			height: 45px;
			float: right;
			background-image: url(<?=$path ?>trackright.png);
		}

		.track-message{
			color:white;
			font-family:Arial,Helvetica,sans-serif;
			font-size:24px;
			padding:9px 18px;
			text-align:right;
		}

		.handle{
			background-image: url(<?=$path ?>handles.png);
			bottom:45px;
			cursor:pointer;
			height:39px;
			margin:3px 4px;
			position:relative;
			width:76px;
		}
	<? } else echo get_option('s2c_css'); ?>
		</style>
	<?
}
add_action('admin_print_styles', 'addAdminMenuCSS');

function addAdminMenuJS(){
		$path =  plugins_url('', __FILE__ );
		?>
			<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js'></script>
			<script type='text/javascript' src='<?=$path ?>/slider.js'></script>
			<script type="text/javascript"> 
				$(function(){
				
				  var sslider = new Slider("sexyslider",{
					  message: "Slide 2 Comment",
					  color: "<? if (get_option('s2c_color') != '') echo get_option('s2c_color'); else echo "green"; ?>",
					<? if(get_option('s2c_noClick') == 1) { ?>
					  mode: "noclick",
					<? } ?>
					  handler: function(){

					  }
				  });
				  sslider.init();
				});
			</script>
		<?
	}
add_action('admin_enqueue_scripts', 'addAdminMenuJS');
/**** Admin Menu End ****/
?>