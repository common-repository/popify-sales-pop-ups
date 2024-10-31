<?php

/**
 * @package: popify-plugin
 */

/**
 * Plugin Name: Popify Sales Pop Ups
 * Description: Popify is the best free tool for Social Proof, Recent Sales Popup, Customer Reviews Pop up, Recent Orders and Abandoned cart recovery.
 * Version: 1.0.5
 * Author: Popify
 * Author URI: https://popify.app
 * License: GPLv3 or later
 * Text Domain: popify-plugin
 */

if (!defined('ABSPATH')) {
  die;
}

define("POPIFY_API_URL", "https://app.popify.app");
define('POPIFY_VERSION', '1.0.4');
define('POPIFY_PATH', dirname(__FILE__));
define('POPIFY_FOLDER', basename(POPIFY_PATH));
define('POPIFY_URL', plugins_url() . '/' . POPIFY_FOLDER);
define('POPIFY_API_KEY', get_option('popify_api_key'));
define("POPIFY_DEVELOPMENT", (stripos(POPIFY_API_URL, "dev.popify") !== false ? "dev" : ""));
define("POPIFY_DEBUG", false);

register_activation_hook(__FILE__, 'popify_activation_hook');
register_deactivation_hook(__FILE__, 'popify_deactivation_hook');
register_uninstall_hook(__FILE__, 'popify_uninstall_hook');
add_action('admin_enqueue_scripts', 'popify_add_admin_css_js');
add_action('admin_menu', 'popify_admin_menu');
add_action('wp_head', 'popify_script');

function popify_activation_hook()
{
	$data = array(
		'store' => get_site_url(),
    'email' => get_option('admin_email'),
		'event' => 'install'
	);

	$response = popify_send_request('/Woocommerce/state', $data);

	if ($response)
	{
		if ($response['success'] > 0)
	 	{
	 		

	 		if (!get_option('popify_api_key'))
	 		{
	 			add_option('popify_api_key',$response['api_key']);

        
        
        if (class_exists("WC_Auth"))
        {
          class Popify_AuthCustom extends WC_Auth 
          {
            public function getKeys($app_name, $user_id, $scope)
            {
              return parent::create_keys($app_name, $user_id, $scope);
            }
          }

          $auth = new Popify_AuthCustom();
          $keys = $auth->getKeys($response['app_name'], $response['user_id'], $response['scope']);
          $data = array(
            'store' => get_site_url(),
            'keys' => $keys,
            'user_id' => $response['user_id'],
            'event' => 'update_keys'
          );
          $keys_response = popify_send_request('/Woocommerce/state', $data);

          if ($keys_response && $keys_response['success'] == 0)
          {
            add_option('popify_error', 'yes');
            add_option('popify_error_message', $keys_response['message']);
          }
        }

        
	 		}
	 		else 
	 		{	 			
        update_option('popify_api_key', $response['api_key']);
	 		}
		}
		else
		{
			
      
      if (!get_option('popify_error'))
      {
        add_option('popify_error', 'yes');
        add_option('popify_error_message', 'Error activation plugin!');
      }
		}
	}
	else
	{
		

    if (!get_option('popify_error'))
    {
      add_option('popify_error', 'yes');
      add_option('popify_error_message', 'Error activation plugin!');
    }
	}
}

function popify_deactivation_hook()
{
  if(!current_user_can('activate_plugins')) 
  {
    return;
  }
  $data = array(
    'store' => get_site_url(),
    'event' => 'deactivated',
  );
  return popify_send_request('/Woocommerce/state', $data);
}

function popify_uninstall_hook() 
{
  if(!current_user_can('activate_plugins')) 
  {
    return;
  }

  delete_option('popify_api_key');

  if (get_option('popify_error'))
  {
    delete_option('popify_error');
  }

  if (get_option('popify_error_message'))
  {
    delete_option('popify_error_message');
  }

  popify_clear_all_caches();

  $data = array(
  	'store' => get_site_url(),
    'event' => 'uninstall',
  );
  return popify_send_request('/Woocommerce/state', $data);
}

function popify_script()
{
	if (strlen(POPIFY_API_KEY) > 0)
	{
    $attributes = array(
      'id'    => POPIFY_DEVELOPMENT.'popifyScript',
      'async' => true,
      'src'   => esc_url(POPIFY_API_URL."/api/js/popifyWoo.js?key=".POPIFY_API_KEY),
    );
    wp_print_script_tag($attributes);
	}
}

function popify_add_admin_css_js()
{
	wp_register_style('popify_style', POPIFY_URL.'/assets/css/style.css');
  wp_enqueue_style('popify_style');
  wp_register_script('popify-admin', POPIFY_URL.'/assets/js/script.js', array('jquery'), '1.0.0');
  wp_enqueue_script('popify-admin');
}

function popify_admin_menu()
{
  add_menu_page('Popify Settings', 'Popify', 'manage_options', 'popify', 'popify_admin_menu_page_html', POPIFY_URL.'/assets/images/popify_icon.png');
}

function popify_has_woocommerce() 
{
  return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
}

function popify_admin_menu_page_html()
{
	include_once POPIFY_PATH.'/views/popify_admin_page.php';
}

function popify_send_request($path, $data) 
{
  try 
  {
		$headers = array(
		  'Content-Type' => 'application/json',
		  'x-plugin-version' => POPIFY_VERSION,
		  'x-site-url' => get_site_url(),
		  'x-wp-version' => get_bloginfo('version'),
		);

    if (popify_has_woocommerce()) 
    {
      $headers['x-woo-version'] = WC()->version;
    }

    $url = POPIFY_API_URL.$path;
    $data = array(
      'headers' => $headers,
      'body' => json_encode($data),
    );
    
    $response = wp_remote_post($url, $data);
    
   
   	if (!is_wp_error($response)) 
		{
	  	$decoded_response = json_decode(wp_remote_retrieve_body($response), true);

	  	return $decoded_response;
	  }

	  return 0;
  } 
  catch(Exception $err) 
  {
    if(POPIFY_DEBUG)
    {
      echo $err;
    }
  }
}

function popify_plugin_redirect()
{
  exit(wp_redirect("admin.php?page=Popify"));
}

function popify_clear_all_caches()
{
  try 
  {
    global $wp_fastest_cache;

    if (function_exists('w3tc_flush_all')) 
    {
      w3tc_flush_all();                
    } 

    if (function_exists('wp_cache_clean_cache')) 
    {
      global $file_prefix, $supercachedir;

      if (empty($supercachedir) && function_exists('get_supercache_dir')) 
      {
        $supercachedir = get_supercache_dir();
      }
      wp_cache_clean_cache($file_prefix);
    } 
    
    if (method_exists('WpFastestCache', 'deleteCache') && !empty($wp_fastest_cache)) 
    {
      $wp_fastest_cache->deleteCache();
    } 

    if (function_exists('rocket_clean_domain')) 
    {
      rocket_clean_domain();
      // Preload cache.
      if (function_exists('run_rocket_sitemap_preload')) {
        run_rocket_sitemap_preload();
      }
    } 
    
    if (class_exists("autoptimizeCache") && method_exists("autoptimizeCache", "clearall")) 
    {
      autoptimizeCache::clearall();
    }
    
    if (class_exists("LiteSpeed_Cache_API") && method_exists("autoptimizeCache", "purge_all")) 
    {
      LiteSpeed_Cache_API::purge_all();
    }
    
    if (class_exists('\Hummingbird\Core\Utils')) 
    {
      $modules= \Hummingbird\Core\Utils::get_active_cache_modules();
      foreach ($modules as $module => $name) 
      {
        $mod = \Hummingbird\Core\Utils::get_module( $module );

        if ($mod->is_active()) 
        {
          if ('minify' === $module) 
          {
            $mod->clear_files();
          } 
          else 
          {
            $mod->clear_cache();
          }
        }
      } 
    }
  } 
  catch (Exception $e) 
  {
    return 1;
  }
}


?>