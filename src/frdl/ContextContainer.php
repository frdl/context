<?php
namespace frdl;


use Psr\Container\ContainerInterface;
use Acclimate\Container\CompositeContainer;
use frdl\ContextContainerSerializer;


class ContextContainer extends CompositeContainer implements ContainerInterface, \ArrayAccess,  \Serializable
{
  
  protected $context = null;   
  protected $containers = [];  
  protected $containerObjectIds = [];  
  protected static $factories = [];
  protected $_prefix = '${';	
  protected $_suffix = '}';	
  protected $SecretSigningKey = null;	


	
  protected function __construct(string $prefix = '${', string $suffix = '}'){      
   //  $class = \Adbar\Dot::class;
   //  $this->context= new $class;
     $this
	     ->pfx($prefix)
	     ->sfx($suffix)
	    ;	  
	  
	  $this->containers = [
	    //$this
	  ];
  }
	
  public function getSerializableProperties(){
	return [	
		'context' => 'setContext',	  
		'cotainers' => 'setCotainers',
		'_prefix' => 'pfx',
		'_suffix' => 'sfx',
	];  	  
  }
	
	
  public function getSerializer() {
	   $this->defaultInit();
     $analyzer = new \SuperClosure\Analyzer\AstAnalyzer();	
     $serializer = new ContextContainerSerializer($analyzer, (null=== $this->SecretSigningKey) ? null : $this->SecretSigningKey);
     return [$analyzer, $serializer];
  }	
	
	
  public function serialize() {	  
     list($analyzer, $serializer) =  $this->getSerializer();
	  
	  $props = $this->getSerializableProperties();
	  
	  $p = [
	  
	  ];
	  
	  foreach(\array_keys($props) as $prop){
		  $p[$prop] = $this->{$prop};
	  }
	  
	  
	  $load = (function(ContextContainer &$instance) use($p, $props){
	     foreach($props as $prop => $method){
		     call_user_func_array([$instance, $method], [$p[$prop]]);
	     }		  
	  });
	  
	  
	   $str = $serializer->serialize($load);	  
	  return $str;
  }

   



  
	public function unserialize( $str)  {
         list($analyzer, $serializer) =  $this->getSerializer();
		
		 $load = $serializer->unserialize($str);
		 $load($this);
    }  
	

	public function setSecretSigningKey($key){  
		$this->SecretSigningKey = $key;   
		return $this;
	} 
	
 public function defaultInit(){
	 if(null === $this->context){
		 $context = new \Adbar\Dot;
		$this->setContext($context); 
	 } 	 
	 
	 if($this->has('config.keys.code-serializer')){
		 $this->setSecretSigningKey($this->get('config.keys.code-serializer'));
	 }
	 
	 return $this; 
 }
	
 public function setContainers(array $containers){
	 $this->containers = $containers;	 
	return $this; 
 }
	
	
 public function setContext(\ArrayAccess $context){
		 
	 if(null !== $this->context){
		throw new \Exception('$context was applied already in '.__METHOD__); 
	 } 
	 
	 $methods = ['has','get','set','flatten'];
	 
	 foreach($methods as $m){
		if(!is_callable([$context,$m])){
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
 public function offsetGet (  $offset ) : mixed {
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
	
	
	
  public function pfx(string $prefix = '${') {
      $this->_prefix = $prefix;
      return $this;	  
  }
  public function prefix(string $prefix = '${') {
     return call_user_func_array([$this, 'pfx'], func_get_args());  
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
    return $this->get($name); 
  }
  
  public function __invoke(callable $script) {
	   $this->defaultInit();
	 return $script($this->context);      
  }
  public function __set($name, $value) {
	   $this->defaultInit();
      call_user_func_array([$this->context, 'set'], [$name, $value]);  
      return $this;
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
  
   public function ___get($id)
    {
        /** @var ContainerInterface $container */
        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return $container->get($id);
            }
        }

          return $this->_get($id);
    }
	
	
   public function get($id)
    {  	  
       
        foreach ($this->containers as $container) {
             if ( \spl_object_id($container) !== \spl_object_id($this) && $container->has($id)) {
                return $container->get($id);
            }
        }

	   
	   $result = ($this->_has($id)) 
		   ? $this->_get($id)
		   : NotFoundException::fromPrevious($id);
       // throw NotFoundException::fromPrevious($id);
	   if(is_object($result) && $result instanceof \Throwable){
		   throw $result;
		   return null;
	   }
	   
	   return $result;
    }	
	

   public function has($id)
    {
        /** @var ContainerInterface $container */
        foreach ($this->containers as $container) {
             if ( \spl_object_id($container) !== \spl_object_id($this) && $container->has($id)) {
                return true;
            }
        }

        return $this->_has($id);
    }
	
	
    public function _get($id)
    {
		 $this->defaultInit();
		
	$i = $id;
	$idResolved = $this->resolvePlaceholder($id);    
	$numParts = count(explode('.', $id));    
	$container = null;
	$path = [];    
		

		
	$result = ($this->context->has($id)) ?  $this->context->get($id) 
		: (($this->context->has($idResolved)) ?  $this->context->get($idResolved) 
		:  new NotFoundException
			)
			; 
	while($idResolved !== $id
		   && (is_null($result) || !is_object($result) || $result instanceof NotFoundException) 
		   && true !== $result instanceof ContainerInterface 
	       && (!is_object($container) || true!== $container instanceof ContainerInterface)
	      && count($path) < $numParts
	     ){
	      list($prefix, $i) = explode('.', $i, 2);
	      $path[]=$this->resolvePlaceholder($prefix);
	      $container = $container->get(implode('.', $path)); 	
	      $result = (is_object($container) && $container instanceof ContainerInterface 
			  && $container->has($i)
			)
		      ?  $container->get($i)
		      :  null; 	
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
	
    public function _has($id)
    {
		 $this->defaultInit();
		
	$i = $id;
	$idResolved = $this->resolvePlaceholder($id);    	
	$numParts = count(explode('.', $id));    
	$container = null;
	$path = [];    
	$result = ($this->context->has($id)) ? true : 
	    ($this->context->has($idResolved)) ? true :  false; 
	while($idResolved !== $id
		   && is_object($result) && $result instanceof NotFoundException 
		   && true !== $result instanceof ContainerInterface 
	       && (!is_object($container) || true!== $container instanceof ContainerInterface)
	      && count($path) < $numParts
	     ){
	      list($prefix, $i) = explode('.', $i, 2);
	      $path[]=$this->resolvePlaceholder($prefix);
	      $container = $container->get(implode('.', $path)); 	
	      $result = (is_object($container) && $container instanceof ContainerInterface 
			  && $container->has($i)
			)
		      ?  true
		      :  false; 	
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
