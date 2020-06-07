<?php
namespace frdl;


use Psr\Container\ContainerInterface;
use Acclimate\Container\CompositeContainer;

use Opis\Closure\SerializableClosure;

use frdl\ContainerStorageWrapper;

use function Opis\Closure\{serialize as packToString, unserialize as loadFromString};


class ContextContainer extends CompositeContainer implements ContainerInterface, \ArrayAccess,  \Serializable
{
  
  protected $context = null;   
  protected $containers = [];  
  protected $containerObjectIds = [];  
  protected static $factories = [];
  protected $_prefix = '${';	
  protected $_suffix = '}';	

	
  public function __construct(string $prefix = '${', string $suffix = '}'){      
     $class = \Adbar\Dot::class;
   //  $this->context= new $class;
     $this
	     ->pfx($prefix)
	     ->sfx($suffix)
	    ;	  
	  
	   $this->setContext(new $class);
	/*   
	  $this->containers = [
	    //$this
	  ];
	 */ 
	  
  }
	
	

	

   

   public function serialize() {	
	   
	   if(getenv('APP_SECRET_CODE_SERIALIZATION')){
		   SerializableClosure::setSecretKey(getenv('APP_SECRET_CODE_SERIALIZATION'));       
	   }elseif(getenv('APP_SECRET')){
		   SerializableClosure::setSecretKey(getenv('APP_SECRET'));   
	   }
	   
    $bin=new \frdl\webfan\Serialize\Binary\bin;
	   
	  $ContainerStorageWrapper =new ContainerStorageWrapper(); 

	   
	   
	   

	   $ContainerStorageWrapper->store($this);
	   
		    $containers = $ContainerStorageWrapper->stored['containers'];

		    $_prefix = $ContainerStorageWrapper->stored['_prefix'];
		    $_suffix = $ContainerStorageWrapper->stored['_suffix'];
	        $loader=$ContainerStorageWrapper;
	   
	   
	                 $stored = $bin->serialize( $containers );
	   
	   
	             $containerLoader = function(&$i) use($stored){
					  $bin=new \frdl\webfan\Serialize\Binary\bin;
					  $containers =  $bin->unserialize($stored);
					 
					 	
					 foreach($containers as $container){		  
						 $i->addContainer($container);		
					 }
					 
				 };
	            

	                  $stringContainerLoader = \Opis\Closure\serialize($containerLoader);
	   
	      $context = [
			  'prefix' => $_prefix,
			  'suffix' => $_suffix,
			  'containerLoader' =>$stringContainerLoader,
			//  'prefix' => $_prefix,
			//  'prefix' => $_prefix,
			   'context'=> $this->context->toJSON(),
			  ];
	        
	  $contextString = $bin->serialize($context);
	  
	   
	    $ContainerStorageWrapper->closure = function(&$i) use($contextString){
			 $bin=new \frdl\webfan\Serialize\Binary\bin;
			$context =$bin->unserialize($contextString);
			  
			 $storedContext = json_decode($context['context']);
			$storedContext = (array)$storedContext;
			 $i->add($storedContext);

		      $containerLoader =$context['containerLoader'];
		
			  $Loader =unserialize($containerLoader);
			  $Loader($i);
					
           return $i;
        };
	 
	   
	   
        $packed = \Opis\Closure\serialize( $ContainerStorageWrapper->closure);

	   return $bin->serialize(['packedStorageWrapper'=>$packed]);
   }

  

   public function unserialize($str)  {            
	   if(getenv('APP_SECRET_CODE_SERIALIZATION')){
		   SerializableClosure::setSecretKey(getenv('APP_SECRET_CODE_SERIALIZATION'));       
	   }elseif(getenv('APP_SECRET')){
		   SerializableClosure::setSecretKey(getenv('APP_SECRET'));   
	   }
		

	    $bin=new \frdl\webfan\Serialize\Binary\bin;
	    $unpacked = $bin->unserialize($str);
	    $loader = $unpacked['packedStorageWrapper'];
	  $loader = unserialize($loader);
	  $newThis = $loader($this);

	  return $newThis;
    }  
	
	

 public function defaultInit(){
	 if(null === $this->context){
		 $context = new \Adbar\Dot;
		 $this->setContext($context); 
		 $this->set('__STR_CONTAINERS', '__containers');
	 } 	 
	 

	 
	 return $this; 
 }
	
 public function addContainer(ContainerInterface $container){
	 $this->defaultInit();
	 $idContainers = $this->get('__STR_CONTAINERS');
	 $id = $idContainers.'.'. \spl_object_id($container);
	 $this->importContainer($id, $container);
	 return $this;
 }
	
 public function setContainers(array $containers = null){
	if(is_array($containers) && count($containerts)>0){
		foreach($containers as $container){
		  $this->addContainer($container);	
		}
	}
	return  $this; 
 }
	
 public function importContainers(array $containers = null){
	if(is_array($containers) && count($containerts)>0){
		foreach($containers as $id => $container){
		  $this->importContainer($id, $container);	
		}
	}
	return  $this; 
 }
	
 public function importContainer(string $id, ContainerInterface $container){
	 $this->defaultInit();
	 $idResolved = $this->resolvePlaceholder($id);
     $this->containerObjectIds[$id] = $idResolved;
	 $this->context->set($idResolved, $container);
	 return $this;
 }
	
	
	
 public function getContainers(){
	return $this->containers; 
 }
 public function getContext(){
	return $this->context; 
 }	
 public function setContext(\ArrayAccess $context){

	 $methods = ['has','get','set','flatten'];
	 
	 foreach($methods as $m){
		if(!\is_callable([$context,$m])){
			throw new \Exception('$context MUST imlement '.get_class($context).'::'.$m.' in '.__METHOD__); 
		}
	 }
	 
	 $this->context = $context;	 

	 return $this;
 }
	
	
 public function offsetExists (  $offset ) : bool {
  // return call_user_func_array([$this->context, 'offsetExists'], func_get_args());
	 $this->defaultInit();
	return isset($this->context[$offset]);
 }
 public function offsetGet (  $offset ) {
 // return call_user_func_array([$this->context, 'offsetGet'], func_get_args());
	 $this->defaultInit();
	  return isset($this->context[$offset]) ? $this->context[$offset] : null;
 }
 public function offsetSet (  $offset ,  $value ) : void {
   //call_user_func_array([$this->context, 'offsetSet'], func_get_args());	   
	 $this->defaultInit();
	    if (is_null($offset)) {
            $this->context[] = $value;
        } else {
            $this->context[$offset] = $value;
        }
 }
 public function offsetUnset (  $offset ) : void	 {
  // call_user_func_array([$this->context, 'offsetUnset'], func_get_args());
	 $this->defaultInit();
	  unset($this->context[$offset]);
 }	
	
	
  public function getPfx() {
      return $this->_prefix;	  
  }	
  public function pfx(string $prefix = '${') {
      $this->_prefix = $prefix;
      return $this;	  
  }
  public function prefix(string $prefix = '${') {
     return call_user_func_array([$this, 'pfx'], func_get_args());  
  } 		
	
  public function getSfx() {
      return $this->_suffix;	  
  }		
  public function sfx(string $suffix = '}') {
      $this->_suffix = $suffix;
      return $this;	  
  }	
  public function suffix(string $suffix = '}') {
     return call_user_func_array([$this, 'sfx'], func_get_args());  
  } 	
	
  public function __call($name, $arguments) {
	//  $this->defaultInit();
	  
      if(null!== $this->context && $this->context->has($name)){
          if(is_callable($this->context->get($name))){
              return call_user_func_array($this->context->get($name), $arguments);
          }
          return $this->context->get($name);
      }
      
      if(null!== $this->context && is_callable([$this->context, $name])){
          return call_user_func_array([$this->context, $name], $arguments);
      }
      
	   foreach ($this->containers as $container) {
          if( \spl_object_id($container) !== \spl_object_id($this) && is_callable([$container, $name])){
              return call_user_func_array([$container, $name], $arguments);
          }		   
	   }
	  
      return new NotFoundException;
  }
    
	
  public function &__get($name){	 
	    $props = $this->getSerializableProperties();
  
	  
	  if($this->has($name)){
		return $this->get($name);  
	  }
	  
	 
			   
	  	
	  if(isset($props[$name])){	
		  
		  
		  $res = call_user_func_array([$this, $props[$name]], []);  
		  

		  return $res;
	  }
	  
		  
    return $this->get($name); 
  }
  /*
  public function __invoke(callable $script) {
	   $this->defaultInit();
	 return $script($this->context);      
  }
  */
  public function __set($name, $value) {
	   $this->defaultInit();
      call_user_func_array([$this->context, 'set'], [$name, $value]);  
     
  } 
  public function flatten() {
	   $this->defaultInit();
     return call_user_func_array([$this->context, 'flatten'], func_get_args());  
  } 	
  public function link(&$items) {
	   $this->defaultInit();
      $this->context->setReference($items);
      return $this;
  }
  public static function create(&$items = null, string $prefix = '${', string $suffix = '}'){     
	  
	 
      $context = new self($prefix, $suffix);
      if(null!==$items){
		  $context->link($items);
	  }
      return $context;
  }
  
 
	


   public function has($id)
    {
        /** @var ContainerInterface $container
        foreach ($this->containers as $container) {
             if ( \spl_object_id($container) !== \spl_object_id($this) && $container->has($id)) {
                return true;
            }
        }

        return $this->_has($id);
		*/
	   
	   if(true===$this->context->has($id) ){
		 return true;   
	   }
		
	     foreach($this->containerObjectIds as $_id => $_idResolved){
			if($this->context->has($_idResolved) && $this->context->get($_idResolved)->has( $id) ){
				return true;
			}
	 	}   
	   
	   return false;
    }
	
	
	
	  
	public function get($id)
    {  	  	   
	  
	   $this->defaultInit();
		
	  $i = 0;
	  $idResolved = $this->resolvePlaceholder($id);   
	  $parts = explode('.', $idResolved);	
	  $restParts = $parts;	
	  $numParts = count($parts); 
	  $path = '';  	
	  $restPath = '';  	
	  $pathParts = [];  	
	  $result = null; 	
		
			
		if(true===$this->context->has($idResolved) ){
		 $result = $this->context->get($idResolved);   
	   }
		
	 if(null===$result){	
		foreach($this->containerObjectIds as $_id => $_idResolved){
			if($this->context->has($_idResolved) && $this->context->get($_idResolved)->has( $idResolved) ){
				$result = $this->context->get($_idResolved)->get( $idResolved );
				break;
			}
		}
	 }
		
    	while(null===$result && $i < $numParts){
		  $i++;
		  	$pathParts[]=array_shift($restParts);
			$restPath = implode('.', $restParts);
			$path=implode('.', $pathParts);
			if($this->context->has($path) && $this->context->get($path) instanceof ContainerInterface
			   && $this->context->get($path)->has($restPath) 
			  ){
				 $result = $this->context->get($path)->get($restPath);
				break;
			}
			
			
		}
	   
	 if(is_callable($result) 
       && !(isset(self::$factories[\spl_object_id($result)]) && self::$factories[\spl_object_id($result)] === $idResolved) ){	    
       if(is_callable([$container, 'call'])){	       
	    $result = call_user_func_array([$container, 'call'], [$result]);   
       }elseif(is_callable([$container, 'make'])){
	    $result = call_user_func_array([$container, 'make'], [$result]);   
       }else{
	    $result = call_user_func_array($result, [$this]);    
       }
	    
	if(is_callable($result)){
	    self::$factories[\spl_object_id($result)] = $idResolved;	
	}
	     $this->context->set($idResolved, $result);
    }	    	

		
      return $result;
    }	
	
	

	
  
	
 public function import(string $file, string $add = null, bool $throw = null){
	  $this->defaultInit();
	 
	  if(!\is_bool($throw)){
	    $throw = false;	  
	  }
	  if(!\is_string($add)){
	    $add = '';	  
	  }	  
	 
    $exists = \file_exists($file);
    if(!$exists && $throw){
	throw new \Exception(\sprintf('File "%s" does not exist in %s', $file, __METHOD__));    
    }elseif(!$exists){
	return false;    
    }
    $extension = \pathinfo($file, \PATHINFO_EXTENSION); 
  
  if('json' === $extension){	 
    $data = \file_get_contents($file);
    $data = \json_decode($data);
  }elseif('php' ===\substr($extension,0,\strlen('php'))){
	$data = require $file;  
  }
    $data = (array)$data;	  
	 
	foreach($data as $key => $value){
	   if('.'===substr($add,0,1) && '.'!==substr($add,-1) ){
		$this->context->set($key.$add, $value);   
	   }elseif('.'!==substr($add,0,1) && '.'===substr($add,-1) ){
		$this->context->set($add.$key, $value);   
	   }elseif(0<strlen($key)){
	        $this->context->set(trim($add,'.').'.'.$key, $value);
	   }
	}
    	
   return true;	 
 }
	
	
  public function export(string $file, string $prepend = null, bool $makeDir = null, bool $throw = null){
	   $this->defaultInit();
	  
	  if(!\is_bool($makeDir)){
	    $makeDir = true;	  
	  }
	  if(!\is_bool($throw)){
	    $throw = true;	  
	  }	  
	  if(!\is_string($prepend)){
	    $prepend = '';	  
	  }	  
	  $dir = \dirname($file);
	//  $exports = \var_export($this->context->all(), true);
	  $exports = \var_export($this->context->flatten('.', null, $prepend), true);
	  $methodDescription = __METHOD__;
	  $time = time();
	  
	  $php = <<<PHPCODE
<?php
/**
* This file was generated automatically by
* @method	$methodDescription
* @time		$time
* @role		data
*/
return $exports;
PHPCODE;
	  
	 $sucess = ( ( \is_dir($dir) && \is_writable($dir) )
		 || (true === $makeDir && @\mdir($dir, 0755, true))
	       )
		 && @\file_put_contents($file, $php);
	  
	  if(true!==$sucess && false !== $throw){
	    throw new \Exception(\sprintf('Error writing "%s" in %s', $file, __METHOD__));	  
	  }	  
	  
   return $sucess;
  }
	
  public function resolvePlaceholder(string $str,array $data = null, string $prefix = null, string $suffix = null){
	  
	  
	  if(null===$prefix){
	    $prefix = $this->_prefix;
	  }
	  if(null===$suffix){
	    $suffix = $this->_suffix;
	  }
	  
	  if(null === $data){
		  $this->defaultInit();
		$data =  $this->context ->flatten();
	  }
	  
	  $dataSource = new \Dflydev\PlaceholderResolver\DataSource\ArrayDataSource($data) ;
      $placeholderResolver = new \Dflydev\PlaceholderResolver\RegexPlaceholderResolver($dataSource, $prefix, $suffix);	  
	  return $placeholderResolver->resolvePlaceholder($str);
  }

  public function resolve($payload = null, string $prefix = null, string $suffix = null){
	
	   $this->defaultInit();
	  
	  if(null===$prefix){
	    $prefix = $this->_prefix;
	  }
	  if(null===$suffix){
	    $suffix = $this->_suffix;
	  }	  
	  
	  
	  $data = $this->context ->flatten();
	 	  
	  switch ($payload){
		  case is_string($payload) :
			   $payload =  $this->context->get($payload);
			  break;
		  case is_array($payload) :
			   $data = $payload;
			  break;
		  case null : 
			 default : 
			   $payload =  $data;
			  break;
			  
	  }
	  
	  $dataSource = new \Dflydev\PlaceholderResolver\DataSource\ArrayDataSource($data) ;
	  
      $placeholderResolver = new \Dflydev\PlaceholderResolver\RegexPlaceholderResolver($dataSource, $prefix, $suffix);	  
	   
	  if(is_array($payload)){
		 $a = $payload;
		  $c = self::create($a);
		  $fn;		   
           foreach($c->flatten() as $k => $v){
			  if(is_string($v)){
				  $v = $placeholderResolver->resolvePlaceholder($v);
			  }
			  $c->set($k, $v);
		     }		  
		  return $c;		 
	  }elseif(is_string($payload)){
		  return $placeholderResolver->resolvePlaceholder($payload);
	  }else{
		  return $payload;
	  }
	 
  }
	
	
  public static function createContextFunctionAsString() : string {
      
    $ContextClass = self::class;  
      
      
      $evalMe = <<<PHPCODETOEVALOUTSIDE
      
 \$context = $ContextClass::create(compact(array_keys(get_defined_vars())));   
          
          
    foreach(array_keys(get_defined_vars()) as \$key){
        if(\$key !== 'this'){
           \$context->{\$key} = \${\$key};
        }
     }
          
      
PHPCODETOEVALOUTSIDE;
    return $evalMe;
  }
  
}
