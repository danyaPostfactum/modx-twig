<?php
global  $modx, $sources;

$plugins = array();

$plugin = $modx->newObject('modPlugin');
$plugin->set('id', null);
$plugin->set('name', 'Twig');
$plugin->set('description', 'Enables Twig template engine');
$plugin->set('plugincode', getSnippetContent($sources['source_core'].'/elements/plugins/twig.plugin.php'));


/* add plugin events */
$events = include $sources['data'].'transport.plugins.events.php';
if (is_array($events) && !empty($events)) {
    $plugin->addMany($events, 'PluginEvents');
    $modx->log(xPDO::LOG_LEVEL_INFO,'Packaged in '.count($events).' Plugin Events.'); flush();
} else {
    $modx->log(xPDO::LOG_LEVEL_ERROR,'Could not find plugin events!');
}
 
$plugins[] = $plugin;

return $plugins;
