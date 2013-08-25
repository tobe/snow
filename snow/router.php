<?php

namespace Snow\Router;

/** Router  */
$request = (isset($_GET['request'])) ? htmlspecialchars($_GET['request']) : 'index/main';

// Split the request by a slash(/)
$request = explode('/', $request);

// $request[0] = class; $request[1] = func; $request[3] = arguments
$class    = strtolower($request[0]); # A class is _always_ present. Otherwise it's overridden by "index/main".
$function = (isset($request[1])) ? strtolower($request[1]) : 'main'; # If there is a function, catch it, otherwise revert to "class/main".

// Now remove the first two keys from the $request array so we can merge (implode) the other arguments.
unset($request[0], $request[1]);

// Implode da args
$arguments = strtolower(implode('/', $request));

#if(empty($arguments)) { $arguments = NULL; }

echo 'Class is: ' . $class . '<br>';
echo 'Function is: ' . $function . '<br>';
echo 'Arguments are: ' . $arguments . '<br>';

// Now check if we can get the class loaded.
try {
    $_c = ROOT . 'requests' . DS . strtolower($class) . '.php';
    if(!file_exists($_c) || !is_readable($_c)) { throw new Exception('Cannot read the class file - ' . $_c); }

    // The file exists, that is good, now load it.
    require_once $_c;
    
    // Try to initiate the class
    $class = ucfirst($class);
    $request_instance = new $class($Base);

    // Don't forget Snow is RESTful...find out if the user wants it RESTful.
    if(isset($request_instance->restful)) {
        switch($_SERVER['REQUEST_METHOD']) {
            default:
            case 'GET':
                $function = 'get_' . $function;
            break;

            case 'POST':
                $function = 'post_' . $function;
            break;

            case 'PUT':
                $function = 'put_' . $function;
            break;

            case 'DELETE':
                $function = 'delete_' . $function;
            break;

            case 'xmlhttprequest':
                $function = 'ajax_' . $function;
            break;
        }
    }

    // Now let's check whether the function we're calling is public or private.
    // Incase it's private or, perhaps it doesn't exist, throw a 404 error.
    if(!method_exists($request_instance, $function) || !in_array($function, get_class_methods($request_instance))) {
        throw new Exception(404); // TODO: This will be handled via the HTTP static library.
        unset($request_instance); // Poof!
    }

    // That's it...and I pass it to the class, what what.
    if(!empty($arguments)) {
        $request_instance->$function($arguments);
    }else {
        $request_instance->$function();
    }
}catch(Exception $e) {
    // TODO: detect 404 here and use HTTP lib.
    trigger_error($e->getMessage());
}
