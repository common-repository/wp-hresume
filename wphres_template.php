<?php
class wphres_template
{
	private $hresume;
	
	public function __construct()
	{

	}
	
	function format_date($date)
	{
		return $date;
		//return date('F y',strtotime($date));
	}
	
	function display($hresume)
	{
		$this->hresume = $hresume;
		include 'layout.inc.php';
	}
	
	public function experience()
	{
		return $this->hresume->experience;
	}
	
	public function education()
	{
		if(is_array($this->hresume->education) && array_key_exists('0', $this->hresume->education))
		{
			return $this->hresume->education;
		}
		
		//there's only one education entry so we have to fake it.
		return array($this->hresume->education);
	}	
	
	public function summary()
	{
		//linkedIn uses a summary attribute
		if($this->hresume->summary)
		{
			if($this->hresume->summary && is_array($this->hresume->summary) && array_key_exists('0', $this->hresume->summary))
			{
				return $this->hresume->summary['0'];
			}
		}

		//stackoverflow uses something else...
	}
	
	public function local()
	{
		//linkedIn
		if($this->hresume->adr)
		{
			if($this->hresume->adr->locality)
			{
				$local = $this->hresume->adr->locality;
				if($this->hresume->adr->region)
				{
					$local = $local.', '.$this->hresume->adr->region;
				}
				
				return $local;
			}
		}

		//stackoverflow
		if(is_string($this->hresume->adr))
		{
			return $this->hresume->adr;
		}
		
	}
	
	public function name()
	{
		if($this->hresume->fn)
		{
			return $this->hresume->fn;
		}
		
		if($this->hresume->bigname)
		{
			return $this->hresume->bigname;
		}
		
		if($this->hresume->n)
		{
			$name = '';
			foreach($this->hresume->n AS $key => $value)
			{
				if('given-name' == $key)
				{
					$name = $value.' ';
				}
				
				if('family-name' == $key)
				{
					$name = $name.$value;
				}
			}
			
			return $name;
		}		
	}
}