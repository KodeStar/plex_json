<?php
/**
 * Plex_json
 *
 * Parse the plex downloads page and create a JSON feed which is cached 
 * for 2 hours.
 *
 * @package     Plex_json
 * @author      Kode
 * @link        https://fanart.tv/webservice/plex/plex.php
 * @since       Version 1.0
 */

 // --------------------------------------------------------------------

$plex = new Plex_json();
$plexpass = ( $_GET['v'] === 'plexpass') ? true : false;
echo $plex->get_latest($plexpass);


class Plex_json {
	
	public $username = "";
	public $password = "";
	
	
    /**
     * get JSON feed for Plex downloads
     *
     * @access	public
     * @param	boolean		$plexpass whether to get the plexpass version or not
     * @return JSON returns JSON feed
     */	
	public function get_latest( $plexpass=false ) 
	{
		$cache = ( $plexpass === true ) ? 'latestplexpass.json': 'latest.json';
		if( file_exists( $cache ) ) {
			$json = file_get_contents( $cache );
			$data = json_decode( $json );
			if( $data->last_checked < time()-7200 ) {// over 2 hours ago
				$json = $this->_get_latest( $plexpass );
				file_put_contents( $cache, $json );
			} 
		} else {
			$json = $this->_get_latest( $plexpass );
			file_put_contents( $cache, $json );
		}
		
		header("HTTP/1.1 200 OK");
		header("Content-Type: application/json; charset=utf-8");
		echo $json;
	}
	
    /**
     * Parse the plex downloads page to create a JSON feed
     *
     * @access	protected
     * @param	boolean		$plexpass whether to get the plexpass version or not
     * @return JSON returns JSON feed
     */	
	protected function _get_latest( $plexpass=false ) 
	{
		
		$page_data = $this->query_plex( $plexpass );
		include_once('simple_html_dom.php');
		$html = str_get_html( $page_data );
		$version = $html->find('#pms-desktop .tab-content .sm');
		$version = $version[0]->innertext;
		$version = explode("Version ", $version);
		$version = explode(" ", $version[1]);
		$version = trim( $version[0] );
		$array = array('last_checked' => time(), 'version' => $version);
		foreach($html->find('#pms-desktop .tab-content') as $e) {
			$output = array();
			$details = $e->innertext;
			$title = $e->find('.title');
			$title = str_replace(array(" ", "."), "_", strtolower($title[0]->innertext));
			foreach( $e->find('.pop-btn') as $dl ) {
				$links = $dl->find('a');
				foreach( $links as $link ) {
					//var_dump($link->attr);
					$attr = "data-event-label";
					$type = (string)$link->$attr;
					$type = str_replace(array(" ", "."), "_", strtolower($type));
					$href = (string)$link->href;
					$array['downloads'][$title][$type] = $href;
				}
			}
		}
		foreach($html->find('#pms-nas .tab-content') as $e){
			$output = array();
			$details = $e->innertext;
			
			$title = $e->find('.title');
			$title = str_replace(" ", "_", strtolower($title[0]->innertext));
			foreach( $e->find('.pop-btn') as $dl ) {
				$links = $dl->find('a');
				foreach( $links as $link ) {
					
					$type = (string)$link->innertext;
					$type = str_replace(array(" ", "."), "_", strtolower($type));
					$href = (string)$link->href;
					$array['downloads'][$title][$type] = $href;
				}
			}
		}
		return json_encode( $array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}
	
    /**
     * Makes the query to Plex and logs in if trying to get the plexpass version
     *
     * @access	protected
     * @param	boolean		$plexpass whether to get the plexpass version or not
     * @return HTML
     */		
	protected function query_plex( $plexpass ) 
	{
		
		if( $plexpass === true ) {
			$username = $this->username;
			$password = $this->password;
			$url = "https://plex.tv/users/sign_in";
			$downloads = 'https://plex.tv/downloads?channel=plexpass';
			$cookie = "cookie.txt";
			$postdata = "user[login]=" . $username . "&user[password]=" . $password;
			$ch = curl_init();
			curl_setopt ($ch, CURLOPT_URL, $url);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
			curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie); // You probably want to change the path of this to one outside your web root
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookie);
			curl_setopt ($ch, CURLOPT_REFERER, $url );
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $postdata);
			curl_setopt ($ch, CURLOPT_POST, 1);
			$result = curl_exec ($ch);
			curl_setopt($ch, CURLOPT_URL, $downloads);
			curl_setopt($ch, CURLOPT_POST, 0);
			$data = curl_exec($ch);
		
		
		} else {
			$downloads = 'https://plex.tv/downloads?channel=plexpass';
			$ch = curl_init();
			curl_setopt ($ch, CURLOPT_URL, $url);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
			curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($ch, CURLOPT_REFERER, $url );
			curl_setopt($ch, CURLOPT_URL, $downloads);
			curl_setopt($ch, CURLOPT_POST, 0);
			$data = curl_exec($ch);
		}
		return $data;
	}

}