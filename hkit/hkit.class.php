<?php

	/* 
	
	hKit Library for PHP5 - a generic library for parsing Microformats
	Copyright (C) 2006  Drew McLellan

	This library is free software; you can redistribute it and/or
	modify it under the terms of the GNU Lesser General Public
	License as published by the Free Software Foundation; either
	version 2.1 of the License, or (at your option) any later version.

	This library is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
	Lesser General Public License for more details.

	You should have received a copy of the GNU Lesser General Public
	License along with this library; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
	
	Author	
		Drew McLellan - http://allinthehead.com/
		
	Contributors:
		Scott Reynen - http://www.randomchaos.com/
		
	Version 0.5, 22-Jul-2006
		fixed by-ref issue cropping up in PHP 5.0.5
		fixed a bug with a@title
		added support for new fn=n optimisation
		added support for new a.include include-pattern
	Version 0.4, 23-Jun-2006
		prevented nested includes from causing infinite loops
		returns false if URL can't be fetched
		added pre-flight check for base support level
		added deduping of once-only classnames
		prevented accumulation of multiple 'value' values
		tuned whitespace handling and treatment of DEL elements
	Version 0.3, 21-Jun-2006
		added post-processor callback method into profiles
		fixed minor problems raised by hcard testsuite
		added support for include-pattern
		added support for td@headers pattern
		added implied-n optimization into default hcard profile
	Version 0.2, 20-Jun-2006
		added class callback mechanism
		added resolvePath & resolveEmail
		added basic BASE support
	Version 0.1.1, 19-Jun-2006 (different timezone, no time machine)
		added external Tidy option
	Version 0.1, 20-Jun-2006
		initial release
		
	
	
	
	*/

	class hKit
	{
		
		public $tidy_mode	= 'proxy'; // 'proxy', 'exec', 'php' or 'none'
		public $tidy_proxy	= 'http://cgi.w3.org/cgi-bin/tidy?forceXML=on&docAddr='; // required only for tidy_mode=proxy
		public $tmp_dir		= '/path/to/writable/dir/'; // required only for tidy_mode=exec
		
		private $root_class = '';
		private $classes	= '';
		private $singles	= '';
		private $required	= '';
		private $att_map	= '';
		private $callbacks	= '';
		private $processor 	= '';
		
		private $url		= '';
		private $base 		= '';
		private $doc		= '';
		
		
		public function hKit()
		{
			// pre-flight checks
			$pass 		= true; 
			$required	= array('dom_import_simplexml', 'file_get_contents', 'simplexml_load_string');
			$missing	= array();
			
			foreach ($required as $f){
				if (!function_exists($f)){
					$pass		= false;
					$missing[] 	= $f . '()';
				}
			}
			
			if (!$pass)
				die('hKit error: these required functions are not available: <strong>' . implode(', ', $missing) . '</strong>');
			
		}
		

		public function getByURL($profile='', $url='')
		{
			
			if ($profile=='' || $url == '') return false;
			
			$this->loadProfile($profile);
			
			$source		= $this->loadURL($url);
			
			if ($source){
				$tidy_xhtml	= $this->tidyThis($source);

				$fragment	= false;
			
				if (strrchr($url, '#'))
				$fragment	= array_pop(explode('#', $url));
			
				$doc		= $this->loadDoc($tidy_xhtml, $fragment);
				$s			= $this->processNodes($doc, $this->classes);
				$s			= $this->postProcess($profile, $s);
			
				return $s;
			}else{
				return false;
			}
		}
		
		public function getByString($profile='', $input_xml='')
		{
			if ($profile=='' || $input_xml == '') return false;
			
			$this->loadProfile($profile);

			$doc	= $this->loadDoc($input_xml);
			$s		= $this->processNodes($doc, $this->classes);
			$s		= $this->postProcess($profile, $s);
			
			return $s;
			
		}
		
		private function processNodes($items, $classes, $allow_includes=true){

			$out	= array();

			foreach($items as $item){
				$data	= array();

				for ($i=0; $i<sizeof($classes); $i++){
					
					if (!is_array($classes[$i])){

						$xpath			= ".//*[contains(concat(' ',normalize-space(@class),' '),' " . $classes[$i] . " ')]";
						$results		= $item->xpath($xpath);
						
						if ($results){

							foreach ($results as $result){ 
								if (isset($classes[$i+1]) && is_array($classes[$i+1])){
									
									$nodes				= $this->processNodes($results, $classes[$i+1]);
									if (sizeof($nodes) > 0){
										$nodes = array_merge(array('text'=>$this->getNodeValue($result, $classes[$i])), $nodes);
										$data[$classes[$i]]	= $nodes;
									}else{
										$data[$classes[$i]]	= $this->getNodeValue($result, $classes[$i]);
									}
									
								}else{								
									if (isset($data[$classes[$i]])){
										if (is_array($data[$classes[$i]])){
											// is already an array - append
											$data[$classes[$i]][]	= $this->getNodeValue($result, $classes[$i]);

										}else{
											// make it an array
											if ($classes[$i] == 'value'){ // unless it's the 'value' of a type/value pattern
												$data[$classes[$i]] .= $this->getNodeValue($result, $classes[$i]);
											}else{
												$old_val			= $data[$classes[$i]];
												$data[$classes[$i]]	= array($old_val, $this->getNodeValue($result, $classes[$i]));
												$old_val			= false;
											}
										}
									}else{										
										// set as normal value
										$data[$classes[$i]]	= $this->getNodeValue($result, $classes[$i]);

									}
								}
							
								// td@headers pattern
								if (strtoupper(dom_import_simplexml($result)->tagName)== "TD" && $result['headers']){
									$include_ids	= explode(' ', $result['headers']);
									$doc			= $this->doc;
									foreach ($include_ids as $id){
										$xpath			= "//*[@id='$id']/..";
										$includes		= $doc->xpath($xpath);
										foreach ($includes as $include){
											$tmp = $this->processNodes($include, $this->classes);
											if (is_array($tmp)) $data = array_merge($data, $tmp);
										}
									}
								}
							}					
						}				
					}
					$result	= false;
				}
				
				// include-pattern
				if ($allow_includes){
					$xpath			= ".//*[contains(concat(' ',normalize-space(@class),' '),' include ')]";
					$results		= $item->xpath($xpath);
				
					if ($results){
						foreach ($results as $result){
							$tagName = strtoupper(dom_import_simplexml($result)->tagName);
							if ((($tagName == "OBJECT" && $result['data']) || ($tagName == "A" && $result['href'])) 
									&& preg_match('/\binclude\b/', $result['class'])){	
								$att		= ($tagName == "OBJECT" ? 'data' : 'href');						
								$id			= str_replace('#', '', $result[$att]);
								$doc		= $this->doc;
								$xpath		= "//*[@id='$id']";
								$includes	= $doc->xpath($xpath);
								foreach ($includes as $include){
									$include	= simplexml_load_string('<root1><root2>'.$include->asXML().'</root2></root1>'); // don't ask.
									$tmp 		= $this->processNodes($include, $this->classes, false);
									if (is_array($tmp)) $data = array_merge($data, $tmp);
								}
							}
						}
					}
				}
				$out[]	= $data;
			}
			
			if (sizeof($out) > 1){
				return $out;
			}else if (isset($data)){
				return $data;
			}else{
				return array();
			}
		}


		private function getNodeValue($node, $className)
		{

			$tag_name	= strtoupper(dom_import_simplexml($node)->tagName);
			$s			= false;
			
			// ignore DEL tags
			if ($tag_name == 'DEL') return $s;
			
			// look up att map values
			if (array_key_exists($className, $this->att_map)){
				
				foreach ($this->att_map[$className] as $map){					
					if (preg_match("/$tag_name\|/", $map)){
						$s	= ''.$node[array_pop($foo = explode('|', $map))];
					}
				}
			}
			
			// if nothing and OBJ, try data.
			if (!$s && $tag_name=='OBJECT' && $node['data'])	$s	= ''.$node['data'];
			
			// if nothing and IMG, try alt.
			if (!$s && $tag_name=='IMG' && $node['alt'])	$s	= ''.$node['alt'];
			
			// if nothing and AREA, try alt.
			if (!$s && $tag_name=='AREA' && $node['alt'])	$s	= ''.$node['alt'];
			
			//if nothing and not A, try title.
			if (!$s && $tag_name!='A' && $node['title'])	$s	= ''.$node['title'];
				
			
			// if nothing found, go with node text
			if(!$s)
			{
				$child_node = $node->xpath('child::node()');
				$s = implode(array_filter($child_node, array(&$this, "filterBlankValues")), ' ');	
			}			

			// callbacks			
			if (array_key_exists($className, $this->callbacks)){
				$s	= preg_replace_callback('/.*/', $this->callbacks[$className], $s, 1);
			}
			
			// trim and remove line breaks
			if ($tag_name != 'PRE'){
				//$s	= trim(preg_replace('/[\r\n\t]+/', '', $s));
				//$s	= trim(preg_replace('/(\s{2})+/', ' ', $s));
			}
			
			return $s;
		}

		private function filterBlankValues($s){
			return preg_match("/\w+/", $s);
		}
		
		
		private function tidyThis($source)
		{
			switch ( $this->tidy_mode )
			{
				case 'exec':
					$tmp_file	= $this->tmp_dir.md5($source).'.txt';
					file_put_contents($tmp_file, $source);
					exec("tidy -utf8 -indent -asxhtml -numeric -bare -quiet $tmp_file", $tidy);
					unlink($tmp_file);
					return implode("\n", $tidy);
				break;
				
				case 'php':
					$tidy 	= tidy_parse_string($source);
					return tidy_clean_repair($tidy);
				break;
						
				default:
					return $source;
				break;
			}
			
		}
		
		
		private function loadProfile($profile)
		{
			require_once("$profile.profile.php");
		}
		
		
		private function loadDoc($input_xml, $fragment=false)
		{
			$xml 		= simplexml_load_string($input_xml);
			
			$this->doc	= $xml;
			
			if ($fragment){
				$doc	= $xml->xpath("//*[@id='$fragment']");
				$xml	= simplexml_load_string($doc[0]->asXML());
				$doc	= null;
			}
			
			// base tag
			if ($xml->head->base['href']) $this->base = $xml->head->base['href'];			

			// xml:base attribute - PITA with SimpleXML
			preg_match('/xml:base="(.*)"/', $xml->asXML(), $matches);
			if (is_array($matches) && sizeof($matches)>1) $this->base = $matches[1];
								
			return 	$xml->xpath("//*[contains(concat(' ',normalize-space(@class),' '),' $this->root_class ')]");
			
		}
		
		
		private function loadURL($url)
		{
			$this->url	= $url;
			
			if ($this->tidy_mode == 'proxy' && $this->tidy_proxy != ''){
				$url	= $this->tidy_proxy . $url;
			}
		
			$file = @file_get_contents($url);
			if($file)
			{
				return str_replace(array('<br>', '<br />'), "\n", $file);
			}
		}
		
		
		private function postProcess($profile, $s)
		{
			$required	= $this->required;
			
			if (is_array($s) && array_key_exists($required[0], $s)){
				$s	= array($s);
			}
			
			$s	= $this->dedupeSingles($s);
			
			if (function_exists('hKit_'.$profile.'_post')){
				$s		= call_user_func('hKit_'.$profile.'_post', $s);
			}
			
			$s	= $this->removeTextVals($s);
			
			return $s;
		}
		
		
		private function resolvePath($filepath)
		{	// ugly code ahoy: needs a serious tidy up
					
			$filepath	= $filepath[0];
			
			$base 	= $this->base;
			$url	= $this->url;
			
			if ($base != '' &&  strpos($base, '://') !== false)
				$url	= $base;
			
			$r		= parse_url($url);
			$domain	= $r['scheme'] . '://' . $r['host'];

			if (!isset($r['path'])) $r['path'] = '/';
			$path	= explode('/', $r['path']);
			$file	= explode('/', $filepath);
			$new	= array('');

			if (strpos($filepath, '://') !== false || strpos($filepath, 'data:') !== false){
				return $filepath;
			}

			if ($file[0] == ''){
				// absolute path
				return ''.$domain . implode('/', $file);
			}else{
				// relative path
				if ($path[sizeof($path)-1] == '') array_pop($path);
				if (strpos($path[sizeof($path)-1], '.') !== false) array_pop($path);

				foreach ($file as $segment){
					if ($segment == '..'){
						array_pop($path);
					}else{
						$new[]	= $segment;
					}
				}
				return ''.$domain . implode('/', $path) . implode('/', $new);
			}	
		}
		
		private function resolveEmail($v)
		{
			$parts	= parse_url($v[0]);
			return ($parts['path']);
		}
		
		
		private function dedupeSingles($s)
		{
			$singles	= $this->singles;
			
			foreach ($s as &$item){
				foreach ($singles as $classname){
					if (array_key_exists($classname, $item) && is_array($item[$classname])){
						if (isset($item[$classname][0])) $item[$classname]	= $item[$classname][0];
					}
				}
			}
			
			return $s;
		}
		
		private function removeTextVals($s)
		{
			foreach ($s as $key => &$val){
				if ($key){
					$k = $key;
				}else{
					$k = '';
				}
				
				if (is_array($val)){
					$val = $this->removeTextVals($val);
				}else{
					if ($k == 'text'){
						$val = '';
					}
				}
			}
			
			return array_filter($s);
		}

	}


?>