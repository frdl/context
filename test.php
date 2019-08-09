<?php

use frdl\Context as Context;

die('Comment out this line: '.__FILE__.' '.__LINE__);

$context =  eval(Context::createContextFunctionAsString());

print_r($context);
