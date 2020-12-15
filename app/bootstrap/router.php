<?php

$router = $di->getRouter();

// Define your routes here

$router->handle($_SERVER['REQUEST_URI']);

// 删除多余的斜线
$router->removeExtraSlashes(true);
