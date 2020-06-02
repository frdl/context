# context
Context ArrayObject DotNotation Container

## Extends/Implements

*    Dot.Notation.Identifiers tansforming into Array-Structure
     e.g. `adbario/php-dot-notation` from [adbario](https://github.com/adbario/php-dot-notation)
 
 *   CompositeContainer [Container Adapters ](https://github.com/AcclimateContainer/acclimate-container)
     
    
# Dynamic Variable Placeholders
````PHP
<?php

//...
	
$items = [
  'selected' => 'member2',	
  'test' => [
      'member1' => '${test.member2}',
      'member2' => 'FooBar',
      'member2.prop1' => '${selected}.FooBar',
 ],
];
	
	
$context = \frdl\Context::create($items);
	

$content.= '$context->resolve()<pre>';	
$content.= print_r($context->resolve(), true); 
$content.= '</pre>';
	
$content.= '$context->get("test.member2.prop1")<pre>';	
$content.= print_r($context->resolve('test.member2.prop1'), true); 
$content.= '</pre>';
	
	
	
$content.= '$context->resolve()->all()<pre>';	
$content.= print_r($context->resolve()->all(), true); 
$content.= '</pre>';
	
$content.= '$context->all()<pre>';	
$content.= print_r($context->all(), true); 
$content.= '</pre>';
	
	
$content.= '$context->resolve()->flatten()<pre>';	
$content.= print_r($context->resolve()->flatten(), true); 
$content.= '</pre>';	
	
	
	
// YourDataSource implements Dflydev\PlaceholderResolver\DataSource\DataSourceInterface
//$dataSource = new YourDataSource;
$dataSource = new \Dflydev\PlaceholderResolver\DataSource\ArrayDataSource($context->flatten()) ;


// Create the placeholder resolver
$placeholderResolver = new \Dflydev\PlaceholderResolver\RegexPlaceholderResolver($dataSource
																				 , '${', '}'
																				);

// Start resolving placeholders
$value = $placeholderResolver->resolvePlaceholder('afsasf ${test.member1}');

$content.= '$value<pre>';	
$content.= print_r($value, true); 
$content.= '</pre>';

$content.= 'test.member1<pre>';	
$content.= print_r( $dataSource->get('test.member1'), true); 
$content.= '</pre>';

````
#### Result
````
$context->resolve()
frdl\Context Object
(
    [context:protected] => Adbar\Dot Object
        (
            [items:protected] => Array
                (
                    [selected] => member2
                    [test] => Array
                        (
                            [member1] => FooBar
                            [member2] => Array
                                (
                                    [prop1] => member2.FooBar
                                )
                            [member2.prop1] => ${selected}.FooBar
                        )
                )
        )
    [_prefix:protected] => ${
    [_suffix:protected] => }
)
$context->get("test.member2.prop1")
$context->resolve()->all()
Array
(
    [selected] => member2
    [test] => Array
        (
            [member1] => FooBar
            [member2] => Array
                (
                    [prop1] => member2.FooBar
                )
            [member2.prop1] => ${selected}.FooBar
        )
)
$context->all()
Array
(
    [selected] => member2
    [test] => Array
        (
            [member1] => ${test.member2}
            [member2] => FooBar
            [member2.prop1] => ${selected}.FooBar
        )
)
$context->resolve()->flatten()
Array
(
    [selected] => member2
    [test.member1] => FooBar
    [test.member2.prop1] => ${selected}.FooBar
)
$value
afsasf FooBar
test.member1
${test.member2}
````


# Multiple Combined Containers

````PHP
<?php

//App.php

		$servicesLegacyContainer = new \compiled\CompiledContainer();
		
		try{
		$configItems = [
		//  'app' => $this,
		];
		$context = \frdl\ContextContainer::create($configItems, '${', '}');
		}catch(\Exception $e){
		  print_r(	$e->getMessage());
		}

		
	$acclimator = new ContainerAcclimator;
	$items = [
	  // 'app' => $this,
	];
		

    $serviceContainer = $acclimator->acclimate($servicesLegacyContainer);
    $contextContainer = $acclimator->acclimate($context);

	
		$this->container = \frdl\ContextContainer::create($items, '${', '}');
		$this->container->addContainer($serviceContainer);
		$this->container->addContainer($contextContainer);


//test.php
               $container = \frdlweb\Level2App::getInstance('production', $projectDir. \DIRECTORY_SEPARATOR)
				   ->getContainer()
				   ;

$env = [];
$context = \frdl\ContextContainer::create($env);

$container->set('context', $context);
$container->set('env', $env);

$context->set('context.app.container.doc.title', 'My TestCase Application');

echo '<pre>';
 print_r($env);
echo '</pre>';


echo '<pre>';
 print_r($container->get('context'));
echo '</pre>';

````
#### Result
````
Array
(
    [context] => Array
        (
            [app] => Array
                (
                    [container] => Array
                        (
                            [doc] => Array
                                (
                                    [title] => My TestCase Application
                                )

                        )

                )

        )

)
````


# Serialization
Serialize/Unserialize between String-/Object Presentation
````PHP
<?php

$s=serialize($container);

print_r('$s=serialize($container): <pre>'.gettype($s).'</pre>');

echo '<pre>'.$s.'</pre>';

$c = unserialize($s);

print_r('$c = unserialize($s): <pre>'.gettype($c).'</pre>');

print_r($c);

print_r($c->get('project')->title);
//....

````
#### Result
````
$s=serialize($container): <pre>string</pre><pre>C:21:"frdl\ContextContainer":1311:{@`packedStorageWrapper`C:32:"Opis\Closure\SerializableClosure":1233:{a:5:{s:3:"use";a:1:{s:13:"contextString";s:629:"@q`prefix`${`suffix`}`containerLoader`C:32:"Opis\Closure\SerializableClosure":485:{a:5:{s:3:"use";a:1:{s:6:"stored";s:63:"@<P`compiled\CompiledContainerP`frdl\ContextContainer";}s:8:"function";s:250:"function(&$i) use($stored){
					  $bin=new \frdl\webfan\Serialize\Binary\bin;
					  $containers =  $bin->unserialize($stored);
					 
					 	
					 foreach($containers as $container){		  
						 $i->addContainer($container);		
					 }
					 
				 }";s:5:"scope";s:21:"frdl\ContextContainer";s:4:"this";N;s:4:"self";s:32:"00000000622cbbd8000000000dbdeb25";}}`context` {"app":{},"context":{},"env":[]}";}s:8:"function";s:423:"function(&$i) use($contextString){
			 $bin=new \frdl\webfan\Serialize\Binary\bin;
			$context =$bin->unserialize($contextString);
			  
			 $storedContext = json_decode($context['context']);
			$storedContext = (array)$storedContext;
			 $i->add($storedContext);

		      $containerLoader =$context['containerLoader'];
		
			  $Loader =unserialize($containerLoader);
			  $Loader($i);
					
           return $i;
        }";s:5:"scope";s:21:"frdl\ContextContainer";s:4:"this";N;s:4:"self";s:32:"00000000622cbbd3000000000dbdeb25";}}}</pre>$c = unserialize($s): <pre>object</pre>frdl\ContextContainer Object
(
    [context:protected] => 
    [containers:protected] => Array
        (
            [0] => compiled\CompiledContainer Object
                (
                    [factoryInvoker:DI\CompiledContainer:private] => 
                    [resolvedEntries:protected] => Array
                        (
                            [DI\Container] => compiled\CompiledContainer Object
 *RECURSION*
                            [Psr\Container\ContainerInterface] => compiled\CompiledContainer Object
 *RECURSION*
                            [DI\FactoryInterface] => compiled\CompiledContainer Object
 *RECURSION*
                            [Invoker\InvokerInterface] => compiled\CompiledContainer Object
 *RECURSION*
                        )

                    [definitionSource:DI\Container:private] => DI\Definition\Source\SourceChain Object
                        (
                            [sources:DI\Definition\Source\SourceChain:private] => Array
                                (
                                    [0] => DI\Definition\Source\DefinitionArray Object
                                        (
                                            [definitions:DI\Definition\Source\DefinitionArray:private] => Array
                                                (
                                                )

                                            [wildcardDefinitions:DI\Definition\Source\DefinitionArray:private] => 
                                            [normalizer:DI\Definition\Source\DefinitionArray:private] => DI\Definition\Source\DefinitionNormalizer Object
                                                (
                                                    [autowiring:DI\Definition\Source\DefinitionNormalizer:private] => DI\Definition\Source\ReflectionBasedAutowiring Object
                                                        (
                                                        )

                                                )

                                        )

                                    [1] => DI\Definition\Source\ReflectionBasedAutowiring Object
                                        (
                                        )

                                )

                            [rootSource:DI\Definition\Source\SourceChain:private] => DI\Definition\Source\SourceChain Object
 *RECURSION*
                            [mutableSource:DI\Definition\Source\SourceChain:private] => DI\Definition\Source\DefinitionArray Object
                                (
                                    [definitions:DI\Definition\Source\DefinitionArray:private] => Array
                                        (
                                        )

                                    [wildcardDefinitions:DI\Definition\Source\DefinitionArray:private] => 
                                    [normalizer:DI\Definition\Source\DefinitionArray:private] => DI\Definition\Source\DefinitionNormalizer Object
                                        (
                                            [autowiring:DI\Definition\Source\DefinitionNormalizer:private] => DI\Definition\Source\ReflectionBasedAutowiring Object
                                                (
                                                )

                                        )

                                )

                        )

                    [definitionResolver:DI\Container:private] => DI\Definition\Resolver\ResolverDispatcher Object
                        (
                            [container:DI\Definition\Resolver\ResolverDispatcher:private] => compiled\CompiledContainer Object
 *RECURSION*
                            [proxyFactory:DI\Definition\Resolver\ResolverDispatcher:private] => DI\Proxy\ProxyFactory Object
                                (
                                    [writeProxiesToFile:DI\Proxy\ProxyFactory:private] => 
                                    [proxyDirectory:DI\Proxy\ProxyFactory:private] => 
                                    [proxyManager:DI\Proxy\ProxyFactory:private] => 
                                )

                            [arrayResolver:DI\Definition\Resolver\ResolverDispatcher:private] => 
                            [factoryResolver:DI\Definition\Resolver\ResolverDispatcher:private] => 
                            [decoratorResolver:DI\Definition\Resolver\ResolverDispatcher:private] => 
                            [objectResolver:DI\Definition\Resolver\ResolverDispatcher:private] => 
                            [instanceResolver:DI\Definition\Resolver\ResolverDispatcher:private] => 
                            [envVariableResolver:DI\Definition\Resolver\ResolverDispatcher:private] => 
                        )

                    [fetchedDefinitions:DI\Container:private] => Array
                        (
                        )

                    [entriesBeingResolved:protected] => Array
                        (
                        )

                    [invoker:DI\Container:private] => 
                    [delegateContainer:protected] => compiled\CompiledContainer Object
 *RECURSION*
                    [proxyFactory:protected] => DI\Proxy\ProxyFactory Object
                        (
                            [writeProxiesToFile:DI\Proxy\ProxyFactory:private] => 
                            [proxyDirectory:DI\Proxy\ProxyFactory:private] => 
                            [proxyManager:DI\Proxy\ProxyFactory:private] => 
                        )

                )

            [1] => frdl\ContextContainer Object
                (
                    [context:protected] => Adbar\Dot Object
                        (
                            [items:protected] => Array
                                (
                                )

                        )

                    [containers:protected] => Array
                        (
                        )

                    [containerObjectIds:protected] => Array
                        (
                        )

                    [_prefix:protected] => ${
                    [_suffix:protected] => }
                )

        )

    [containerObjectIds:protected] => Array
        (
        )

    [_prefix:protected] => ${
    [_suffix:protected] => }
)
Testprojekt
````



