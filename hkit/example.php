<?php
	include('hkit.class.php');
	
	$url = 'http://careers.stackoverflow.com/ericlamb';
	if($_GET['t'] == '2')
	{
		$url = 'http://www.linkedin.com/in/mithra62';
	}
	$h	= new hKit;
	
	// Config options (see top of class file)
	$h->tidy_mode	= 'proxy'; // 'proxy', 'exec', 'php' or 'none'
	
	// Get by URL
	$result	= $h->getByURL('hresume', $url);

	// Get by String
	//$result	= $h->getByString('hcard', '<div class="vcard"><p class="fn">Drew McLellan</p></div>');

	print '<pre>'.print_r($result, 1).'</pre>';
?>