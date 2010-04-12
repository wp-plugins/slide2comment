<?php
/*
Plugin Name: Slide2Comment - Anti-Spam in a sexy way
Plugin URI: http://sexyslider.longhoang.de
Description: Adds a sexy iPhone lock slider to block spambots. If the slider hasn't been unlocked, the comment will be mark as spam.
Version: 1.2.3
Author: Long Hoang
Author URI: http://longhoang.de
License: http://creativecommons.org/licenses/by-nc-sa/3.0/de/

	My aim was it to build an anti-spam captcha which just look awesome and people have fun to use it. I found an 	iPhone-like slider at Aboone which is just what I've searched for. I implement a Wordpress captcha, which mark all comments as spam, if you haven't use the slider before. Hope you enjoy it!

	The sliders are from:
	http://www.aboone.com/javascript-iphone-lock-slider-with-jquery
*/

function addCaptcha() {
	?>
	<div id="sexyslider">
	</div>
	<?
}
add_action('comment_form', 'addCaptcha');

function addCSS() {
	$path = get_bloginfo('url').'/wp-content/plugins/slide2comment/img/';
	?>
		<style type="text/css"> 
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
		</style>
	<?
	return $theCSS;
}
add_action('wp_print_styles', 'addCSS');

function addJS(){
	$path = get_bloginfo('url').'/wp-content/plugins/slide2comment';
	?>
		<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js'></script>
		<script type='text/javascript' src='<?=$path ?>/slider.js'></script>
		<script type="text/javascript"> 
			$(function(){
				$("#commentform").css("display", "none");
				$('#commentform').after($('#sexyslider')); 
				
			  var sslider = new Slider("sexyslider",{
				  message: "Slide 2 Comment",
				  color: "green",
				  //mode: "noclick",
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

add_action('wp_print_scripts', 'addJS');

function comment_approvement(){
	global $commentdata;
	if($commentdata['comment_content'] == '')return true;
	if($_POST['add'] != md5(get_bloginfo('url')) ){
		
		$commentdata['comment_content'] = "=]MARKED AS SPAM BY SLIDE2COMMENT[=\n".$commentdata['comment_content'];
		email_notify($GLOBALS['commentdata']);
		
		return 'spam';
	}
	return true;
}
add_action('pre_comment_approved', 'comment_approvement');

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

?>
