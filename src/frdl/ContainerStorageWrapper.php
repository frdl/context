<?php
namespace frdl;


use Psr\Container\ContainerInterface;
use Acclimate\Container\CompositeContainer;

use Opis\Closure\SerializableClosure;

use frdl\ContextContainer;

use function Opis\Closure\{serialize as packToString, unserialize as loadFromString};


class ContainerStorageWrapper
 {

		   protected $stored = null;
   
		   public function store(ContextContainer &$instance = null){	     
			 $this->stored = [];  
			   
			   foreach($this->getSerializableProperties() as $prop => $methods){		    
				 $this->stored[$prop] = packToString( call_user_func_array([$instance, $methods['get']], [$instance]) );	   
			   }	
			   
			 
		   }
	 
	       
	       
        public function __invoke(ContextContainer &$instance = null){     
					   			
				if(null!==$instance && null===$this->stored){
				   $this->store($instance);
			   }elseif(null!==$instance && null!==$this->stored){
				  $this->restore($instance);
				}			
				
			  return $this;
			}
	
		   public function restore(ContextContainer $instance = null){
			   foreach($this->getSerializableProperties() as $prop => $methods){		    
				   call_user_func_array([$instance, $methods['set']], [loadFromString($this->stored[$prop])]);	   
			   }					           
		   }	
		   
  protected function getSerializableProperties(){
	return [	
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
  }		   
	  
}
