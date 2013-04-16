<?php
class BreadCrumbs{
/**
 * BreadCrumb
 * Copyright 2011 Benjamin Vauchel <contact@omycode.fr>
 *
 * BreadCrumb is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) any
 * later version.
 *
 * BreadCrumb is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * BreadCrumb; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package breadcrumb
 * @author Benjamin Vauchel <contact@omycode.fr>
 *
 * @version Version 1.3.0 pl
 * 28/08/12
 *
 * Breadcrumb is a snippet for MODx Revolution, inspired by the Jared's BreadCrumbs snippet.
 * It will create a breadcrumb navigation for the current resource or a specific resource.
 *
 * Optional properties:
 *
 * @property resourceId - (int) Resource ID whose breadcrumb is created; [Default value : null].
 * @property from - (int) Resource ID of the first crumb; [Default value : 0].
 * @property to - (int) Resource ID of the last crumb; [Default value : current resource id].
 * @property maxCrumbs - (int) Max crumbs shown in breadcrumb. Max delimiter template can be customize with property maxCrumbTpl ; [Default value : 100].
 * @property showHidden - (bool) Show hidden resources in breadcrumb; [Default value : true].
 * @property showContainer - (bool) Show container resources in breadcrumb; [Default value : true].
 * @property showUnPub - (bool) Show unpublished resources in breadcrumb; [Default value : true].
 * @property showCurrentCrumb - (bool) Show current resource as a crumb; [Default value : true].
 * @property showBreadCrumbAtHome - (bool) Show BreadCrumb on the home page; [Default value : true].
 * @property showHomeCrumb - (bool) Show the home page as a crumb; [Default value : false].
 * @property useWebLinkUrl - (bool) Use the weblink url instead of the url to the weblink; [Default value : true].
 * @property direction - (string) Direction or breadcrumb : Left To Right (ltr) or Right To Left (rtl) for Arabic language for example; [Default value : ltr].
 * @property scheme - (string) URL Generation Scheme; [Default value : -1].
 *
 * Templates :
 *
 * @property containerTpl - (string) Container template for BreadCrumb; [Default value : BreadCrumbContainerTpl].
 * @property currentCrumbTpl - (string) Current crumb template for BreadCrumb; [Default value : BreadCrumbCurrentCrumbTpl].
 * @property linkCrumbTpl - (string) Default crumb template for BreadCrumb; [Default value : BreadCrumbLinkCrumbTpl].
 * @property maxCrumbTpl - (string) Max delimiter crumb template for BreadCrumb; [Default value : BreadCrumbMaxCrumbTpl].
 */

function __construct(modX &$modx,array $config = array()) {
	$this->modx =& $modx;
	$this->config = array_merge(array(
		'resourceId' => null,
		'from' => 0,
		'to' => $this->modx->resource->get('id'),
		'maxCrumbs' => 100,
		'showHidden' => true,
		'direction' => 'ltr',
		'scheme' => null,
		'showCurrentCrumb' => true,
		'showHomeCrumb' => false,
		'showUnpublished' => true,
		'showContainer' => true
	), $config);
}
// $resourceId           = $modx->getOption('resourceId', $config, null, true);
// $from                 = $modx->getOption('from', $config, 0, true, true);
// $to                   = $currentResourceId = (!is_null($resourceId) ? $resourceId : $modx->getOption('to', $config, $modx->resource->get('id'), true));
// $maxCrumbs            = $modx->getOption('maxCrumbs', $config, 100, true);
// $showHidden           = (bool)$modx->getOption('showHidden', $config, true, true);
// $showContainer        = isset($showContainer) ? (bool)$showContainer : (bool)$modx->getOption('showContainer', $scriptProperties, true, true);
// $showUnPub            = isset($showUnPub) ? (bool)$showUnPub : (bool)$modx->getOption('showUnPub', $scriptProperties, true, true);
// $showCurrentCrumb     = isset($showCurrentCrumb) ? (bool)$showCurrentCrumb : (bool)$modx->getOption('showCurrentCrumb', $scriptProperties, true, true);
// $showBreadCrumbAtHome = isset($showBreadCrumbAtHome) ? (bool)$showBreadCrumbAtHome : (bool)$modx->getOption('showBreadCrumbAtHome', $scriptProperties, true, true);
// $showHomeCrumb        = isset($showHomeCrumb) ? (bool)$showHomeCrumb : (bool)$modx->getOption('showHomeCrumb', $scriptProperties, false, true);
// $useWebLinkUrl        = isset($useWebLinkUrl) ? (bool)$useWebLinkUrl : (bool)$modx->getOption('useWebLinkUrl', $scriptProperties, true, true);
// $direction            = !empty($direction) ? $direction : $modx->getOption('direction', $scriptProperties, 'ltr', true);
// $scheme               = !empty($scheme) ? $scheme : $modx->getOption('scheme', $scriptProperties, $modx->getOption('link_tag_scheme'), true);

// Output variable

function run(){
	$output = array();
	// We get all the crumbs
	$crumbs = array();
	$crumbsCount = 0;
	$resourceId = $this->config['to'];
	$from = $this->config['from'];
	$scheme = $this->config['scheme'];
	$direction = $this->config['direction'];
	$maxCrumbs = $this->config['maxCrumbs'];
	$showHidden = $this->config['showHidden'];
	$showContainer = $this->config['showContainer'];
	$showUnpublished = $this->config['showUnpublished'];
	$showCurrentCrumb = $this->config['showCurrentCrumb'];
	$currentResourceId = $this->config['currentResourceId'];
	while($resourceId != $from && $crumbsCount < $maxCrumbs)
	{
		$resource = $this->modx->getObject('modResource', $resourceId);

		// We check the conditions to show crumb
		if(
			(($resourceId == $this->modx->getOption('site_start') && $showHomeCrumb) || $resourceId != $this->modx->getOption('site_start'))  // ShowHomeCrumb
			&& (($resource->get('hidemenu') && $showHidden) || !$resource->get('hidemenu'))										// ShowHidden
			&& (($resource->get('isfolder') && $showContainer) || !$resource->get('isfolder'))									// ShowContainer
			&& ((!$resource->get('published') && $showUnpublished) || $resource->get('published')) 									// UnPub
			&& (($resourceId == $currentResourceId && $showCurrentCrumb) || $resourceId != $currentResourceId)  // ShowCurrent
		)
		{
			// If is LTR direction, we push resource at the beginning of the array
			if($direction == 'ltr')
			{
			    array_unshift($crumbs, $resource);
			}
			// Else we push it at the end
			else
			{
			    $crumbs[] = $resource;
			}

			$crumbsCount++;
		}
		$resourceId = $resource->get('parent');
	}

	// We build the output of crumbs
	foreach($crumbs as $key => $resource)
	{
		// Placeholders
		$placeholders = $resource->toArray();
		if($resource->get('class_key') == 'modWebLink' && $useWebLinkUrl)
		{
			if(is_numeric($resource->get('content')))
			{
				$link = $this->modx->makeUrl($resource->get('content'), '', '', $scheme);
			}
			else
			{
				$link = $resource->get('content');
			}
		}
		else
		{
			$link = $this->modx->makeUrl($resource->get('id'), '', '', $scheme);
		}
		$placeholders = array_merge($resource->toArray(), array('link' => $link));

		// Output
		$output[] = $placeholders;
	}

	return $output;
}

}

?>
