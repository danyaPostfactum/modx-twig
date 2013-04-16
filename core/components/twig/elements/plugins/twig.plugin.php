<?php
// Инициализируем Смарти
  
$core_path = $modx->getOption('twig.core_path', $scriptProperties, $modx->getOption('core_path', null).'components/twig/');
$template_dir = $modx->getOption('twig.template_dir', $scriptProperties, $core_path.'templates/');
$cache_dir = $modx->getOption('twig.cache_dir', $scriptProperties, $modx->getOption('core_path', null).'cache/twig/');

require_once $core_path. 'Twig/Autoloader.php';
Twig_Autoloader::register();

$config = array(
    'cache'             => $modx->getOption('debug') ? null : $cache_dir,
    'autoescape'        => false,
);

switch($modx->event->name){
    case 'OnHandleRequest':
        if($modx->context->key == 'mgr'){
            return;
        }
        $loader = new Twig_Loader_Filesystem($template_dir . 'default/');
        $twig = new Twig_Environment($loader, $config);
        $modx->twig = & $twig;
        include_once($core_path . 'extensions.php');
        break;
    
    case 'OnSiteRefresh':
        $twig = new Twig_Environment(null, $config);
        $twig->clearCacheFiles();
        break;
    
    default:;
}