# context
Context ArrayObject DotNotation

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


````PHP
<?php


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


````PHP

<?php

$s=serialize($container);

print_r('$s=serialize($container): <pre>'.gettype($s).'</pre>');

$c = unserialize($s);

print_r('$c = unserialize($s): <pre>'.gettype($c).'</pre>');

print_r($c);

print_r($c->get('project')->title);
//....

````
