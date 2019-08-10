<?php

use frdl\Context as Context;

die('Comment out this line: '.__FILE__.' '.__LINE__);

eval(Context::createContextFunctionAsString());

$context(function(){
  print_r(func_get_args());
  print_r(compact(array_keys(get_defined_vars())));
});


