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
             public $loader;
	
	
	         const PROPERTIES_MAPPING = [
		'context' =>[
			'set'=> 'setContext',	  
			'get'=> 'getContext',			
		],
		'containers' => [
			'set'=> 'setContainers',	  
			'get'=> 'getContainers',			
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
			   
			   
			    $this->load();
			   
	         return $this;	
		   }
	 
	       
	public function load(){ 
			//$payload =$this->stored;
	       // $payload = [
			//     'stored.php'=>var_export($this->stored, true),
			//	
			//];
	//	print_r( $this->stored['context']);
		
		
		    $containers = $this->stored['containers'];
		  //  $context = var_export($this->stored['context']->flatten(), true);
		  //  $jsonCotextContainer = $this->stored['context']->toJson();
		    $_prefix = $this->stored['_prefix'];
		    $_suffix = $this->stored['_suffix'];
			$this->loader=function(\frdl\ContextContainer $instance)  use($_prefix, $_suffix, $containers){
		          $instance->setContainers($containers);
		          $instance->pfx($_prefix);
		          $instance->sfx($_suffix);
	               return [$_prefix, $_suffix, $containers];
			};		
		
		
		
	// 	$wrapper = new SerializableClosure($loader);
    //     $serialized = \Opis\Closure\serialize($wrapper);
	// 	return $serialized;
		
	//   return \Opis\Closure\serialize($loader);	
		
		
	//	return \Opis\Closure\serialize($loader);
		return $this;
	}
	       
        public function __invoke(\frdl\ContextContainer $instance){    				   			
			
			
				if(null===$this->stored || 0===count($this->stored) ){
				   $this->store($instance);
			   }elseif(null!==$this->stored || 0<count($this->stored)){
				  $this->restore($instance);					
				}		
			
			
	     return $this;
	   }
	
		   public function restore(ContextContainer $instance){			   
			   
			   foreach(self::PROPERTIES_MAPPING as $prop => $methods){		    
				   call_user_func_array([$instance, $methods['set']], [$this->stored[$prop]]);	   
			   }	
			   
			   
	         return $this;				           
		   }	
		   
 
	  
}
