<?php
/**
 * modExtra
 *
 * Copyright 2010 by Shaun McCormick <shaun+modextra@modx.com>
 *
 * modExtra is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * modExtra is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * modExtra; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package modextra
 */
/**
 * Loads system settings into build
 *
 * @package modextra
 * @subpackage build
 */
global  $modx, $sources;
$settings = array();

$settings['twig.template_dir'] = $modx->newObject('modSystemSetting');
$settings['twig.template_dir']->fromArray(array(
    'key' => 'twig.template_dir',
    'value' => '{core_path}components/twig/templates/',
    'xtype' => 'textfield',
    'namespace' => 'twig',
    'area' => 'site',
),'',true,true);

$settings['twig.cache_dir'] = $modx->newObject('modSystemSetting');
$settings['twig.cache_dir']->fromArray(array(
    'key' => 'twig.cache_dir',
    'value' => '{core_path}cache/twig/',
    'xtype' => 'textfield',
    'namespace' => 'twig',
    'area' => 'site',
),'',true,true);


return $settings;