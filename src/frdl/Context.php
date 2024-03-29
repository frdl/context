<?php
namespace frdl;

use Psr\Container\ContainerInterface;

class Context implements ContainerInterface
{
  
  protected $context;   
  protected static $factories = [];
  protected $_prefix = '${';	
  protected $_suffix = '}';	
	
  protected function __construct(string $prefix = '${', string $suffix = '}'){      
     $class = \Adbar\Dot::class;
     $this->context= new $class;
     $this
	     ->pfx($prefix)
	     ->sfx($suffix)
	    ;	  
  }
	
  public static function runFile($file, array $context, int $flags = \EXTR_OVERWRITE, string $prefix = ""){
	  extract($context, $flags, $prefix);
	  return require $file;	  
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
        return call_user_func_array([$this->context, 'get'], \func_get_args());
    }
	
    public function has($id)
    {
	return call_user_func_array([$this->context, 'has'], \func_get_args());
    }	
	
	
 public function import(string $file, string $add = null, bool $throw = null){
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
		$data =  $this->context ->flatten();
	  }
	  
	  $dataSource = new \Dflydev\PlaceholderResolver\DataSource\ArrayDataSource($data) ;
      $placeholderResolver = new \Dflydev\PlaceholderResolver\RegexPlaceholderResolver($dataSource, $prefix, $suffix);	  
	  return $placeholderResolver->resolvePlaceholder($str);
  }

  public function resolve($payload = null, string $prefix = null, string $suffix = null){
	
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
