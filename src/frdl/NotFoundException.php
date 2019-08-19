<?php

namespace frdl;


class NotFoundException extends \ErrorException
{
	/*
	function __construct($id){
	   $args = func_get_args();	
	   parent::__construct('The identifier `'.$id.'` was not found. '. $this->getFile().' '.$this->getLine(),
						  0, \E_ERROR, $args[3], $args[4], $this);
	}
	*/
}
