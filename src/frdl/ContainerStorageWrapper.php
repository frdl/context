<?php
namespace frdl;


use Psr\Container\ContainerInterface;
use Acclimate\Container\CompositeContainer;

use Opis\Closure\SerializableClosure;

use frdl\ContextContainer;

use function Opis\Closure\{serialize as packToString, unserialize as loadFromString};


class ContainerStorageWrapper
 {

		     public $stored = [];
             public $load;
	
	
	         const PROPERTIES_MAPPING = [
	 /*	
	   'context' =>[
			'set'=> 'setContext',	  
			'get'=> 'getContext',			
		],
				
		'containers' => [
			'set'=> 'setContainers',	  
			'get'=> 'getContainers',			
		],
		*/
		 
		'data' => [
			'set'=> 'link',	  
			'get'=> 'flatten',			
		],			 
				 
		'_prefix' => [
			'set'=> 'pfx',	  
			'get'=> 'getPfx',			
		],
		'_suffix' => [
			'set'=> 'sfx',	  
			'get'=> 'getSfx',			
		], 				 
				
			 ];
	
		   public function store(\frdl\ContextContainer $instance){	     
			
			   
			   foreach(self::PROPERTIES_MAPPING as $prop => $methods){					   
				   $this->stored[$prop] =call_user_func_array([$instance, $methods['get']], []);					
			   }	
			   
			   
			 return $this->loader($instance,  $this->stored);
		   }
	 
	       
	public function loader(\frdl\ContextContainer $instance, $_stored){ 

		
		/*
			$this->load=function(\frdl\ContextContainer &$instance, ContainerStorageWrapper $wrapper){
				
		     //     $instance->setContainers($containers);
		          $instance->pfx($wrapper->stored['_prefix']);
		          $instance->sfx($wrapper->stored['_suffix']);
				
				foreach(\frdl\ContainerStorageWrapper::PROPERTIES_MAPPING as $prop => $methods){		    
				   call_user_func_array([$instance, $methods['set']], [$wrapper->stored[$prop]]);	   
			   }		
				
				
	              return $instance;
			};		
		*/
		
		

		return ['stored'=>$_stored];
	}
	       
	
	public static function load(\frdl\ContextContainer $instance, $stored = null){
	
		foreach(\frdl\ContainerStorageWrapper::PROPERTIES_MAPPING as $prop => $methods){		    
				   call_user_func_array([$instance, $methods['set']], [$stored[$prop]]);	 			
		}					
				
	    return $instance;
	}
	
	
	
        public function __invoke(\frdl\ContextContainer $instance, callable $loader = null){   
					
				if(null === $loader){
				   return $this->store($instance);
			   //}elseif(null!==$this->stored || 0<count($this->stored)){
				}elseif(is_callable($loader)){					
				  return $this->restore($instance, $loader);					
				}		
	   }
	
	   public function restore(\frdlContextContainer $instance, callable $loader){			   
	        $loader($instance);
			   
	        return $instance;				           
	  }	
		   
 
	  
}
