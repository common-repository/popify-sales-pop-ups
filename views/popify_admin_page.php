<?php

// Prevent direct file access
if (!defined('ABSPATH')) {
  exit;
}

if (!current_user_can('manage_options')) {
  wp_die(__('You do not have sufficient permissions to access this page.'));
}

$api_key = get_option('popify_api_key');
$error = get_option('popify_error');
$error_message = get_option('popify_error_message');

?>

<div class="popify-page"> 
	<div class="popify-logo">
		<a href="https://popify.app" target="_blank"> <img style="max-width:none; width:345px; border:0; text-decoration:none; outline:none" src="<?php echo esc_url(POPIFY_URL);?>/assets/images/popifyLogo.png" /></a> 
	</div>
	<?php

	if (!$api_key || ($api_key && strlen($api_key) < 1) || $api_key == null)
	{
		?>
		<div class="popify-alert">Invalid API key!</div>
		<?php
	}
	else
	{
		$page_target = "_blank";

		if (isset($_GET['autologin']) && $_GET['autologin'])
		{
			$page_target = "_self";
		}

		$button_prop = 'onclick="window.open(\''.POPIFY_API_URL.'/Woocommerce/loginByToken/'.$api_key.'\', \''.$page_target.'\');"';

		if ($error && $error == "yes")
		{
			$button_prop = 'disabled';

			?>
			<div class="popify-alert"><?php echo esc_html($error_message);?></div>
			<div class="popify-clearfix"></div><br />
			<?php
		}

		?>
		<div class="popify-login-url" style="margin-top:-40px; line-height: 30px;">
		<h3>You're All Set!</h3>
		Popify is installed on your website <br>		
			Click on the button below to login to your dashboard
			<div class="popify-clearfix"></div>
			<!--<button id="popifyLoginBtn" class="popify-btn-login-me" style="text-transform: none; font-size: 25px;" <?php echo esc_attr($button_prop);?>>Go To Dashboard</button>-->
			<button id="popifyLoginBtn" class="popify-btn-login-me" style="text-transform: none; font-size: 25px;" <?php echo $button_prop;?>>Go To Dashboard</button>
			
			<div class="popify-clearfix"></div>
		</div>
		<div class="popify-clearfix"></div>
		<?php
	}
	?>
	<div class="popify-clearfix"></div>
</div>

<style>
.notice, div.error, div.updated {
    display: none !important;
}
.popify-page
{
    box-shadow: -7px 5px 16px 0px rgb(4 4 4 / 35%) !important;
    margin: 51px auto 20px;
}
</style>
