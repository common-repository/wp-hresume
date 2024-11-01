<?php
/*
Plugin Name: WP-hResume
Plugin URI: http://blog.ericlamb.net/projects/wp-hresume
Description: Basically a fork of LinkedIn hResume, WP-hResume grabs the Microformated hResume block from any publicly available, hresume formatted, web page allowing you to add it to any page and apply your own styles to it.
Author: Eric Lamb
Author URI: http://blog.ericlamb.net/
Version: 1.0
*/

class wphres
{
	private $atts;
	private $tpl;
	
	public function __construct()
	{
		include 'wphres_template.php';
		$this->tpl = new wphres_template();
	}
	
	public function shortcode($atts) 
	{
		$this->atts = $atts;
		shortcode_atts(array(
			'url' => FALSE,
			'caching' => FALSE,
		), $this->atts);

		$this->atts['caching'] = $this->is_caching($this->atts['caching']);	
		return $this->get_hresume($this->atts['url'], $this->atts['caching']);
	}
	
	private function is_caching($value) 
	{
		return (in_array($value, explode(',', 'on,true,1')));
	}
	
	private function get_page($url) 
	{	
		include_once('hkit/hkit.class.php');
		$h	= new hKit;
		$h->tidy_mode = 'proxy'; // 'proxy', 'exec', 'php' or 'none'
		$data = $h->getByURL('hresume', $url);
		return $data;
	}
	
	private function error_check($content, $url) 
	{
		if(!is_array($content) && !array_key_exists('0', $content))
		{
			wp_die('<p>Could not get resume...</p>');
		}	
	}
	

	private function get_hresume($url, $caching = false) 
	{
		$hresume = FALSE;
		if ($caching) {
			$cache = get_option('wphres_cache');
			if ($cache !== false) {
				list($cache_url, $expiry, $data) = $cache;
				if ($url == $cache_url && $expiry > time()) {
					$hresume = $data;
				}
			}
		}
	
		if (!$hresume) {
			$hresume = $this->get_page($url);
			$this->error_check($hresume, $url);	
			$hresume = json_encode($hresume);
			if ($caching) {
				update_option('wphres_cache', array($url, time()+21600, $hresume));
			}
		}

		$hresume = json_decode($hresume);
		$this->tpl->display($hresume['0']);
	}	
}

$wphres = new wphres;
//add_filter('the_content', array($wphres, 'callback'), 50);
add_shortcode('wphres', array($wphres, 'shortcode'));
?>
