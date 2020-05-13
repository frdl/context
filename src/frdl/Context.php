<?php
namespace frdl;

use Psr\Container\ContainerInterface;

class Context implements ContainerInterface
{
  
  protected $context;   
  protected function __construct(){      
     $class = \Adbar\Dot::class;
     $this->context= new $class;
  }
    
  public function __call($name, $arguments) {
      if($this->context->has($name)){
          if(is_callable($this->context->get($name))){
              return call_user_func_array($this->context->get($name), $arguments);
          }
          return $this->context->get($name);
      }
      
      if(is_callable([$this->context, $name])){
          return call_user_func_array([$this->context, $name], $arguments);
      }
      
      return new NotFoundException;
  }
    
  public function &__get($name){
    return $this->get($name); 
  }
  
  public function __invoke(\Closure $script) {
      return $script($this->context);      
  }
  public function __set($name, $value) {
      call_user_func_array([$this->context, 'set'], [$name, $value]);  
      return $this;
  } 
  public function flatten() {
     return call_user_func_array([$this->context, 'flatten'], func_get_args());  
  } 	
  public function link(&$items) {
      $this->context->setReference($items);
      return $this;
  }
  public static function create(&$items){      
      $context = new self;
      $context->link($items);
      return $context;
  }
  
    public function get($id)
    {
	$i = $id;
	$idResolved = $this->resolvePlaceholder($id);    
	$numParts = count(explode('.', $id));    
	$container = $this;
	$path = [];    
	$result = ($this->context->has($id)) ?  $this->context->get($id) 
		: (($this->context->has($idResolved)) ?  $this->context->get($idResolved) 
		:  new NotFoundException); 
	while(is_object($result) && $result instanceof NotFoundException 
	   //   && is_object($container) && $container instanceof ContainerInterface
	      && count($path) < $numParts
	     ){
	      list($prefix, $i) = explode('.', $i, 2);
	      $path[]=$this->resolvePlaceholder($prefix);
	      $container = $container->get(implode('.', $path)); 	
	      $result = (is_object($container) && $container instanceof ContainerInterface 
			  && $container->has($i)
			)
		      ?  $container->get($i)
		      :  new NotFoundException; 	
	}	    
	
    if(is_callable($result) ){	    
       if(is_callable([$container, 'call'])){	       
	    $result = call_user_func_array([$container, 'call'], [$result]);   
       }elseif(is_callable([$container, 'make'])){
	    $result = call_user_func_array([$container, 'make'], [$result]);   
       }else{
	    $result = call_user_func_array($result, [$this]);    
       }
	$this->context->set($idResolved, $result);
    }	    
	    
       return $result;
    }
	
    public function has($id)
    {
	$i = $id;
	$numParts = count(explode('.', $id));    
	$container = $this;
	$path = [];    
	$result = ($this->context->has($id)) ? true : 
	    ($this->context->has($this->resolvePlaceholder($id))) ? true :  false; 
	while(is_bool($result) && true !== $result
	    //  && is_object($container) && $container instanceof ContainerInterface
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
	
	
 public function import(string $file, bool $throw = null){
	  if(!\is_bool($throw)){
	    $throw = false;	  
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
    $data = (array)$data;	  
  }elseif('php' ===\substr($extension,0,\strlen('php'))){
	$data = require $file;  
  }
	 
	foreach($data as $key => $value){
	   $this->context->set($key, $value);
	}
    	
   return true;	 
 }
	
	
  public function export(string $file, string $prepend = null, bool $makeDir = null, bool $throw = null){
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
	
  public function resolvePlaceholder(string $str,array $data = null, string $prefix = '${', string $suffix = '}'){
	  if(null === $data){
		$data =  $this->context ->flatten();
	  }
	  
	  $dataSource = new \Dflydev\PlaceholderResolver\DataSource\ArrayDataSource($data) ;
      $placeholderResolver = new \Dflydev\PlaceholderResolver\RegexPlaceholderResolver($dataSource, $prefix, $suffix);	  
	  return $placeholderResolver->resolvePlaceholder($str);
  }

  public function resolve($payload = null, string $prefix = '${', string $suffix = '}'){
	
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
