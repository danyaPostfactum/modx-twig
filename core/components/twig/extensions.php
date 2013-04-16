<?php

global $modx;

$function = new Twig_SimpleFunction('navigation', function ($options) {
    include_once MODX_CORE_PATH.'/components/twig/wayfinder.class.php';
global $modx;
$wf = new Wayfinder($modx,$options);
$result = $wf->run();

    return $result;
});
$modx->twig->addFunction($function);


$function = new Twig_SimpleFunction('breadcrumbs', function ($options = array()) {
global $modx;
    include_once MODX_CORE_PATH.'/components/twig/breadcrumbs.class.php';
$breadcrumbs = new BreadCrumbs($modx,$options);
$result = $breadcrumbs->run();

    return $result;
});
$modx->twig->addFunction($function);


$function = new Twig_SimpleFunction('resources', function ($options = array()) {
global $modx;
    include_once MODX_CORE_PATH.'/components/twig/getresources.class.php';
$resources = new getResources($modx,$options);
$result = $resources->run();
    return $result;
});
$modx->twig->addFunction($function);


$function = new Twig_SimpleFunction('page', function ($options = array()) {
global $modx;
    include_once MODX_CORE_PATH.'/components/twig/getpage.class.php';
$resources = new getPage($modx,$options);
$result = $resources->run();

    return $result;
});
$modx->twig->addFunction($function);


$function = new Twig_SimpleFunction('setting', function ($name, $default = null) {
global $modx;
    return $modx->getOption($name, null, $default);
});
$modx->twig->addFunction($function);

$function = new Twig_SimpleFunction('chunk', function ($name, $scriptProperties = array()) {
    global $modx;
    $modx->getParser(); 
        
    $output = $modx->getChunk($name, $scriptProperties);

    $maxIterations= intval($modx->getOption('parser_max_iterations', $options, 10));
    $modx->parser->processElementTags('', $output, true, false, '[[', ']]', array(), $maxIterations);
    $modx->parser->processElementTags('', $output, true, true, '[[', ']]', array(), $maxIterations);

    return $output;
});
$modx->twig->addFunction($function);

$function = new Twig_SimpleFunction('placeholder', function ($name) {
    global $modx;
    return $modx->getPlaceholder($name);
});
$modx->twig->addFunction($function);


$function = new Twig_SimpleFunction('snippet', function ($name, $scriptProperties = array()) {
    global $modx;

    $modx->getParser(); 

    $output = $modx->runSnippet($name, $scriptProperties);
    
    $maxIterations= intval($modx->getOption('parser_max_iterations', $options, 10));
    $modx->parser->processElementTags('', $output, true, false, '[[', ']]', array(), $maxIterations);
    $modx->parser->processElementTags('', $output, true, true, '[[', ']]', array(), $maxIterations);

    return $output;
});
$modx->twig->addFunction($function);


$function = new Twig_SimpleFunction('link', function ($id, $context='',$args=array(),$scheme = -1, $options = array()) {
global $modx;

    return $modx->makeUrl($id, $context,$args,$scheme, $options);
});
$modx->twig->addFunction($function);


$function = new Twig_SimpleFunction('albums', function ($options = array()) {
global $modx;
    include_once MODX_CORE_PATH.'/components/twig/getalbums.php';
    return getAlbums($options);
});
$modx->twig->addFunction($function);


$function = new Twig_SimpleFunction('album', function ($options = array()) {
global $modx;
    include_once MODX_CORE_PATH.'/components/twig/getalbum.php';
    var_dump(getAlbum($options));
    return getAlbum($options);
});
$modx->twig->addFunction($function);

$test = new Twig_SimpleTest('home', function ($subject) {
global $modx;
    return $subject->id == $modx->getOption('site_start');
});
$modx->twig->addTest($test);