<?php

use frdl\Context as Context;

$context =  eval(Context::createContextFunctionAsString());

print_r($context);
