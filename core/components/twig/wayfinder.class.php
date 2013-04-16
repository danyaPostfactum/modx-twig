<?php
/**
 * Wayfinder Class
 *
 * @package wayfinder
 */
class Wayfinder {
    /**
     * The array of config parameters
     * @access private
     * @var array $_config
     */
    public $_config;
    public $_templates;
    public $_css;
    public $modx = null;
    public $docs = array ();
    public $parentTree = array ();
    public $hasChildren = array ();
    public $placeHolders = array (
        'wrapperLevel' => array (
            '[[+wf.wrapper]]',
            '[[+wf.classes]]',
            '[[+wf.classnames]]'
        ),
        'tvs' => array (),

    );
    public $tvList = array ();
    public $debugInfo = array ();
    private $_cached = false;
    private $_cachedTVs = array();
    private $_cacheKeys = array();
    private $_cacheOptions = array();

    function __construct(modX &$modx,array $config = array()) {
        $this->modx =& $modx;

        $this->_config = array_merge(array(
            'id' => $this->modx->resource->get('id'),
            'level' => 0,
            'include' => array(),
            'exclude' => array(),
            'ph' => false,
            'debug' => false,
            'ignoreHidden' =>false,
            'hideSubMenus' => false,
            'useWeblinkUrl' => true,
            'fullLink' => false,
            'sortOrder' => 'ASC',
            'sortBy' => 'menuindex',
            'limit' => 0,
            'cssTpl' => false,
            'jsTpl' => false,
            'rowIdPrefix' => false,
            'textOfLinks' => 'menutitle',
            'titleOfLinks' => 'pagetitle',
            'displayStart' => false,
            'permissions' => 'list',
            'previewUnpublished' => false,
        ),$config);
        if (empty($this->_config['hereId'])) {
            $this->_config['hereId'] = $this->modx->resource->get('id');
        }

        if (isset($config['sortOrder'])) {
            $this->_config['sortOrder'] = strtoupper($config['sortOrder']);
        }
        if (isset($config['root'])) { $this->_config['id'] = $config['root']; }
        if (isset($config['removeNewLines'])) { $this->_config['nl'] = ''; }
        
        if (isset($this->_config['contexts'])) {
            $this->_config['contexts'] = preg_replace('/,  +/', ',', $this->_config['contexts']);
        }
    }


    /**
     * Main entry point to generate the menu
     *
     * @return string The menu HTML or relevant error message.
     */
    public function run() {
        /* setup here checking array */
        $this->parentTree = $this->modx->getParentIds($this->_config['hereId']);
        $this->parentTree[] = $this->_config['hereId'];

        /* check for cached files */
        $cacheResults = $this->modx->getOption('cacheResults',$this->_config,true);
        if ($cacheResults) {
            $this->modx->getCacheManager();
            $cache = $this->getFromCache();
            if (!empty($cache) && !empty($cache['docs']) && !empty($cache['children'])) {
                /* cache files are set */
                $this->docs = $cache['docs'];
                $this->hasChildren = $cache['children'];
                $this->_cached = true;
            }
        }
        if (empty($this->_cached)) {
            /* cache files not set - get all of the resources */
            $this->docs = $this->getData();
            /* set cache files */
            if ($cacheResults) {
                $this->setToCache();
            }
        }
        if (!empty($this->docs)) {
            /* sort resources by level for proper wrapper substitution */
            ksort($this->docs);
            /* build the menu */
            return $this->buildMenu();
        } else {
            $noneReturn = $this->_config['debug'] ? '<p>No resources found for menu.</p>' : '';
            return $noneReturn;
        }
    }

    /**
     * Attempt to get the result set from the cache
     * 
     * @return array Cached result set, if existent
     */
    public function getFromCache() {
        $cacheKeys = $this->getCacheKeys();
        /* check for cache */
        $cache = array();
        $cache['docs'] = $this->modx->cacheManager->get($cacheKeys['docs'],$this->_cacheOptions);
        $cache['children'] = $this->modx->cacheManager->get($cacheKeys['children'],$this->_cacheOptions);
        return $cache;
    }

    /**
     * Set result-set data to cache
     * @return boolean
     */
    public function setToCache() {
        $cacheKeys = $this->getCacheKeys();
        $cacheTime = $this->modx->getOption('cacheTime',$this->_config,3600);
        $this->modx->cacheManager->set($cacheKeys['docs'],$this->docs,$cacheTime,$this->_cacheOptions);
        $this->modx->cacheManager->set($cacheKeys['children'],$this->hasChildren,$cacheTime,$this->_cacheOptions);
        return true;
    }

    /**
     * Generate an array of cache keys used by wayfinder caching
     * @return array An array of cache keys
     */
    public function getCacheKeys() {
        if (!empty($this->_cacheKeys)) return $this->_cacheKeys;
        
        /* generate a UID based on the params passed to Wayfinder and the resource ID
         * and the User ID (so that permissions get correctly applied) */
        $cacheKey = 'wf-'.$this->modx->user->get('id').'-'.base64_encode(serialize($this->_config));
        $childrenCacheKey = $cacheKey.'-children';

        /* set cache keys to proper Resource cache so will sync with MODX core caching */
        $this->_cacheKeys = array(
            'docs' => $this->modx->resource->getCacheKey().'/'.md5($cacheKey),
            'children' => $this->modx->resource->getCacheKey().'/'.md5($childrenCacheKey),
        );

        $this->_cacheOptions = array(
            'cache_key' => $this->modx->getOption('cache_resource_key',null, 'resource'),
            'cache_handler' => $this->modx->getOption('cache_resource_handler', null, 'xPDOFileCache'),
            'cache_expires' => (int)$this->modx->getOption('cache_expires', null, 0),
        );
        return $this->_cacheKeys;
    }

    /**
     * Constructs the menu HTML by looping through the document array
     *
     * @return string The HTML for the menu
     */
    public function buildMenu() {
        $output = array();
        /* loop through all of the menu levels */
        foreach ($this->docs as $level => $subDocs) {
            /* loop through each document group (grouped by parent resource) */
            foreach ($subDocs as $parentId => $docs) {
                //if ($this->_config['startId'] != 0 && $this->_config['hideSubMenus']) continue;
                /* only process resource group, if starting at root, hidesubmenus is off, or is in current parenttree */
                if ((!$this->_config['hideSubMenus'] || $this->isHere($parentId) || $parentId == 0)) {

                    /* build the output for the group of resources */
                    $menuPart = $this->buildSubMenu($docs,$level);
                    /* if at the top of the menu start the output, otherwise replace the wrapper with the submenu */
                    if (($level == 1 && (!$this->_config['displayStart'] || $this->_config['id'] == 0)) || ($level == 0 && $this->_config['displayStart'])) {
                        return $menuPart;
                    } else {
                        return $menuPart;
                    }
                }
            }
        }
        return $output;
    }

    /**
     * Constructs a sub menu for the menu
     *
     * @param array $menuDocs Array of documents for the menu
     * @param int $level The heirarchy level of the sub menu to be rendered
     * @return string The submenu HTML
     */
    public function buildSubMenu($menuDocs,$level) {
        $subMenuOutput = array();
        $firstItem = 1;
        $counter = 1;
        $numSubItems = count($menuDocs);
        /* loop through each resource to render output */
        foreach ($menuDocs as $docId => $docInfo) {
            $docInfo['level'] = $level;
            $docInfo['first'] = $firstItem;
            $firstItem = 0;
            /* determine if last item in group */
            if ($counter == ($numSubItems) && $numSubItems > 1) {
                $docInfo['last'] = 1;
            } else {
                $docInfo['last'] = 0;
            }
            /* determine if resource has children */
            $docInfo['hasChildren'] = in_array($docInfo['id'],$this->hasChildren) ? 1 : 0;
            $numChildren = $docInfo['hasChildren'] ? count($this->docs[$level+1][$docInfo['id']]) : 0;
            /* render the row output */
            $subMenuOutput[] = $docInfo;
            /* update counter for last check */
            $counter++;
        }

        if ($level > 0 && false) {
            /* determine which wrapper template to use */
            if ($this->_templates['innerTpl'] && $level > 1) {
                $useChunk = $this->_templates['innerTpl'];
                $usedTemplate = 'innerTpl';
            } else {
                $useChunk = $this->_templates['outerTpl'];
                $usedTemplate = 'outerTpl';
            }
            /* determine wrapper class */
            if ($level > 1) {
                $wrapperClass = 'innercls';
            } else {
                $wrapperClass = 'outercls';
            }
            /* get the class names for the wrapper */
            $classNames = $this->setItemClass($wrapperClass);
            $useClass = $classNames ? ' class="' . $classNames . '"' : '';
            $phArray = array($subMenuOutput,$useClass,$classNames);
            /* process the wrapper */
            $subMenuOutput = str_replace($this->placeHolders['wrapperLevel'],$phArray,$useChunk);
            /* debug */
            if ($this->_config['debug']) {
                $debugParent = $docInfo['parent'];
                $debugDocInfo = array();
                $debugDocInfo['template'] = $usedTemplate;
                foreach ($this->placeHolders['wrapperLevel'] as $n => $v) {
                    if ($v !== '[[+wf.wrapper]]') {
                        $debugDocInfo[$v] = $phArray[$n];
                    }
                }
                $this->addDebugInfo("wrapper","{$debugParent}","Wrapper for items with parent {$debugParent}.","These fields were used when processing the wrapper for the following resources: ",$debugDocInfo);
            }
        }
        return $subMenuOutput;
    }

    /**
     * Renders a row item for the menu
     *
     * @param array $resource An array containing the document information for the row
     * @param int $numChildren The number of children that the document contains
     * @return string The HTML for the row item
     */
    public function renderRow(&$resource,$numChildren) {
        $output = '';
        /* determine which template to use */
        if ($this->_config['displayStart'] && $resource['level'] == 0) {
            $usedTemplate = 'startItemTpl';
        } elseif ($resource['id'] == $this->_config['hereId'] && $resource['isfolder'] && $this->_templates['parentRowHereTpl'] && ($resource['level'] < $this->_config['level'] || $this->_config['level'] == 0) && $numChildren) {
            $usedTemplate = 'parentRowHereTpl';
        } elseif ($resource['id'] == $this->_config['hereId'] && $this->_templates['innerHereTpl'] && $resource['level'] > 1) {
            $usedTemplate = 'innerHereTpl';
        } elseif ($resource['id'] == $this->_config['hereId'] && $this->_templates['hereTpl']) {
            $usedTemplate = 'hereTpl';
        } elseif ($resource['isfolder'] && $this->_templates['activeParentRowTpl'] && ($resource['level'] < $this->_config['level'] || $this->_config['level'] == 0) && $this->isHere($resource['id'])) {
            $usedTemplate = 'activeParentRowTpl';
        } elseif ($resource['isfolder'] && ($resource['template']=="0" || is_numeric(strpos($resource['link_attributes'],'rel="category"'))) && $this->_templates['categoryFoldersTpl'] && ($resource['level'] < $this->_config['level'] || $this->_config['level'] == 0)) {
            $usedTemplate = 'categoryFoldersTpl';
        } elseif ($resource['isfolder'] && $this->_templates['parentRowTpl'] && ($resource['level'] < $this->_config['level'] || $this->_config['level'] == 0) && $numChildren) {
            $usedTemplate = 'parentRowTpl';
        } elseif ($resource['level'] > 1 && $this->_templates['innerRowTpl']) {
            $usedTemplate = 'innerRowTpl';
        } else {
            $usedTemplate = 'rowTpl';
        }
        /* get the template */
        $useChunk = $this->_templates[$usedTemplate];
        /* setup the new wrapper name and get the class names */
        $useSub = $resource['hasChildren'] ? "[[+wf.wrapper.{$resource['id']}]]" : "";
        $classNames = $this->setItemClass('rowcls',$resource['id'],$resource['first'],$resource['last'],$resource['level'],$resource['isfolder'],$resource['class_key']);
        $useClass = $classNames ? ' class="' . $classNames . '"' : '';
        /* setup the row id if a prefix is specified */
        if ($this->_config['rowIdPrefix']) {
            $useId = ' id="' . $this->_config['rowIdPrefix'] . $resource['id'] . '"';
        } else {
            $useId = '';
        }

        /* set placeholders for row */
        $placeholders = array();
        foreach ($resource as $k => $v) {
            $placeholders['wf.'.$k] = $v;
        }
        $placeholders['wf.wrapper'] = $useSub;
        $placeholders['wf.classes'] = $useClass;
        $placeholders['wf.classNames'] = $classNames;
        $placeholders['wf.classnames'] = $classNames;
        $placeholders['wf.id'] = $useId;
        $placeholders['wf.level'] = $resource['level'];
        $placeholders['wf.docid'] = $resource['id'];
        $placeholders['wf.subitemcount'] = $numChildren;
        $placeholders['wf.attributes'] = $resource['link_attributes'];
		
        if (!empty($this->tvList)) {
            $usePlaceholders = array_merge($placeholders,$this->placeHolders['tvs']);
            foreach ($this->tvList as $tvName) {
                $placeholders[$tvName]=$resource[$tvName];
            }
        } else {
            $usePlaceholders = $placeholders;
        }
        /* debug */
        if ($this->_config['debug']) {
            $debugDocInfo = array();
            $debugDocInfo['template'] = $usedTemplate;
            foreach ($usePlaceholders as $n => $v) {
                $debugDocInfo[$v] = $placeholders[$n];
            }
            $this->addDebugInfo("row","{$resource['parent']}:{$resource['id']}","Doc: #{$resource['id']}","The following fields were used when processing this document.",$debugDocInfo);
            $this->addDebugInfo("rowdata","{$resource['parent']}:{$resource['id']}","Doc: #{$resource['id']}","The following fields were retrieved from the database for this document.",$resource);
        }
        /* @var modChunk $chunk process content as chunk */
        $chunk = $this->modx->newObject('modChunk');
        $chunk->setCacheable(false);
        $output .= $chunk->process($placeholders, $useChunk);
		
        /* return the row */
        $separator = $this->modx->getOption('nl',$this->_config,"\n");
        return $output . $separator;
    }

    /**
     * Determine style class for current item being processed
     *
     * @param string $classType The type of class to be returned
     * @param int $docId The document ID of the item being processed
     * @param int $first Integer representing if the item is the first item (0 or 1)
     * @param int $last Integer representing if the item is the last item (0 or 1)
     * @param int $level The heirarchy level of the item being processed
     * @param int $isFolder Integer representing if the item is a container (0 or 1)
     * @param string $type Resource type of the item being processed
     * @return string The class string to use
     */
    public function setItemClass($classType, $docId = 0, $first = 0, $last = 0, $level = 0, $isFolder = 0, $type = 'modDocument') {
        $returnClass = '';
        $hasClass = 0;

        if ($classType === 'outercls' && !empty($this->_css['outer'])) {
            /* set outer class if specified */
            $returnClass .= $this->_css['outer'];
            $hasClass = 1;
        } elseif ($classType === 'innercls' && !empty($this->_css['inner'])) {
            /* set inner class if specified */
            $returnClass .= $this->_css['inner'];
            $hasClass = 1;
        } elseif ($classType === 'rowcls') {
            /* set row class if specified */
            if (!empty($this->_css['row'])) {
                $returnClass .= $this->_css['row'];
                $hasClass = 1;
            }
            /* set first class if specified */
            if ($first && !empty($this->_css['first'])) {
                $returnClass .= $hasClass ? ' ' . $this->_css['first'] : $this->_css['first'];
                $hasClass = 1;
            }
            /* set last class if specified */
            if ($last && !empty($this->_css['last'])) {
                $returnClass .= $hasClass ? ' ' . $this->_css['last'] : $this->_css['last'];
                $hasClass = 1;
            }
            /* set level class if specified */
            if (!empty($this->_css['level'])) {
                $returnClass .= $hasClass ? ' ' . $this->_css['level'] . $level : $this->_css['level'] . $level;
                $hasClass = 1;
            }
            /* set parentFolder class if specified */
            if ($isFolder && !empty($this->_css['parent']) && ($level < $this->_config['level'] || $this->_config['level'] == 0)) {
                $returnClass .= $hasClass ? ' ' . $this->_css['parent'] : $this->_css['parent'];
                $hasClass = 1;
            }
            /* set here class if specified */
            if (!empty($this->_css['here']) && $this->isHere($docId)) {
                $returnClass .= $hasClass ? ' ' . $this->_css['here'] : $this->_css['here'];
                $hasClass = 1;
            }
            /* set self class if specified */
            if (!empty($this->_css['self']) && $docId == $this->_config['hereId']) {
                $returnClass .= $hasClass ? ' ' . $this->_css['self'] : $this->_css['self'];
                $hasClass = 1;
            }
            /* set class for weblink */
            if (!empty($this->_css['weblink']) && $type == 'modWebLink') {
                $returnClass .= $hasClass ? ' ' . $this->_css['weblink'] : $this->_css['weblink'];
                $hasClass = 1;
            }
        }
        return $returnClass;
    }

    /**
     * Determine the "you are here" point in the menu
     *
     * @param $did Document ID to find
     * @return bool Returns true if the document ID was found
     */
    public function isHere($did) {
        return in_array($did,$this->parentTree);
    }


    /**
     * Smarter getChildIds that will iterate across Contexts if needed
     *
     * @param integer $startId The ID which to start at
     * @param integer $depth The depth in which to parse
     * @return array
     */
    public function getChildIds($startId = 0,$depth = 10) {
        $ids = array();
        if (!empty($this->_config['contexts'])) {
            $contexts = explode(',',$this->_config['contexts']);
            $contexts = array_unique($contexts);
            $currentContext = $this->modx->context->get('key');
            $activeContext = $currentContext;
            $switched = false;
            foreach ($contexts as $context) {
                if ($context != $currentContext) {
                    $this->modx->switchContext($context);
                    $switched = true;
                    $currentContext = $context;
                }
                /* use modx->getChildIds here, since we dont need to switch contexts within resource children */
                $contextIds = $this->modx->getChildIds($startId,$depth);
                if (!empty($contextIds)) {
                    $ids = array_merge($ids,$contextIds);
                }
            }
            $ids = array_unique($ids);
            if ($switched) { /* make sure to switch back to active context */
                $this->modx->switchContext($activeContext);
            }
        } else { /* much faster if not using contexts */
            $ids = $this->modx->getChildIds($startId,$depth);
        }
        return $ids;
    }

    /**
     * Get the required resources from the database to build the menu
     *
     * @return array The resource array of documents to be processed
     */
    public function getData() {
        $depth = !empty($this->_config['level']) ? $this->_config['level'] : 10;
        $ids = $this->getChildIds($this->_config['id'],$depth);
        $resourceArray = array();

        /* get all of the ids for processing */
        if ($this->_config['displayStart'] && $this->_config['id'] !== 0) {
            $ids[] = $this->_config['id'];
        }
        if (!empty($ids)) {
            $c = $this->modx->newQuery('modResource');
            $c->leftJoin('modResourceGroupResource','ResourceGroupResources');
            $c->query['distinct'] = 'DISTINCT';

            /* add the ignore hidden option to the where clause */
            if (!$this->_config['ignoreHidden']) {
                $c->where(array('hidemenu:=' => 0));
            }

            /* if set, limit results to specific resources */
            if (!empty($this->_config['include'])) {
                $c->where(array('modResource.id:IN' => $this->_config['include']));
            }

            /* add the exclude resources to the where clause */
            if (!empty($this->_config['contexts'])) {
                $c->where(array('modResource.context_key:IN' => explode(',',$this->_config['contexts'])));
                $c->sortby('context_key','DESC');
            }

            /* add the exclude resources to the where clause */
            if (!empty($this->_config['exclude'])) {
                $c->where(array('modResource.id:NOT IN' => $this->_config['exclude']));
            }
            
            /* add the limit to the query */
            if (!empty($this->_config['limit'])) {
                $offset = !empty($this->_config['offset']) ? $this->_config['offset'] : 0;
                $c->limit($this->_config['limit'], $offset);
            }

            /* JSON where ability */
            if (!empty($this->_config['where'])) {
                $where = $this->modx->fromJSON($this->_config['where']);
                if (!empty($where)) {
                    $c->where($where);
                }
            }
            if (!empty($this->_config['templates'])) {
                $c->where(array(
                    'template:IN' => explode(',',$this->_config['templates']),
                ));
            }

            /* determine sorting */
            if (strtolower($this->_config['sortBy']) == 'random') {
                $c->sortby('rand()', '');
            } else {
                $c->sortby($this->_config['sortBy'],$this->_config['sortOrder']);
            }

            $c->where(array('modResource.id:IN' =>  $ids));
            if ($this->modx->user->hasSessionContext('mgr') && $this->modx->hasPermission('view_unpublished') && $this->_config['previewUnpublished']) {} else {
                $c->where(array('modResource.published:=' => 1));
            }
            $c->where(array('modResource.deleted:=' => 0));

            /* not sure why this groupby is here in the first place. removing for now as it causes
             * issues with the sortby clauses */
            //$c->groupby($this->modx->getSelectColumns('modResource','modResource','',array('id')));

            $c->select($this->modx->getSelectColumns('modResource','modResource'));
            $c->select(array(
                'protected' => 'ResourceGroupResources.document_group',
            ));

            $result = $this->modx->getCollection('modResource', $c);


            $resourceArray = array();
            $level = 1;
            $prevParent = -1;
            /* setup start level for determining each items level */
            if ($this->_config['id'] == 0) {
                $startLevel = 0;
            } else {
                $activeContext = $this->modx->context->get('key');
                $contexts = !empty($this->_config['contexts']) ? explode(',',$this->_config['contexts']) : array();
                /* switching ctx, as this startId may not be in current Context */
                if (!empty($this->_config['startIdContext'])) {
                    $this->modx->switchContext($this->_config['startIdContext']);
                    $startLevel = count($this->modx->getParentIds($this->_config['id']));
                    $this->modx->switchContext($activeContext);

                /* attempt to auto-find startId context if &contexts param only has one context */
                } else if (!empty($contexts) && !empty($contexts[0]) && $contexts[0] != $activeContext) {
                    $this->modx->switchContext($contexts[0]);
                    $startLevel = count($this->modx->getParentIds($this->_config['id']));
                    $this->modx->switchContext($activeContext);

                } else {
                    $startLevel = count($this->modx->getParentIds($this->_config['id']));
                }
            }
            $resultIds = array();

            $activeContext = $this->modx->context->get('key');
            $currentContext = $activeContext;
            $switchedContext = false;
            /** @var modResource $doc */
            foreach ($result as $doc)  {
                $docContextKey = $doc->get('context_key');
                if (!empty($docContextKey) && $docContextKey != $currentContext) {
                    $this->modx->switchContext($docContextKey);
                    $switchedContext = true;
                    $currentContext = $doc->get('context_key');
                }
		        if ((!empty($this->_config['permissions'])) && (!$doc->checkPolicy($this->_config['permissions']))) continue;
                $tempDocInfo = $doc->toArray();
                $resultIds[] = $tempDocInfo['id'];
                $tempDocInfo['content'] = $tempDocInfo['class_key'] == 'modWebLink' ? $tempDocInfo['content'] : '';
                /* create the link */
                $linkScheme = $this->_config['fullLink'] ? 'full' : '';
                if (!empty($this->_config['scheme'])) $linkScheme = $this->_config['scheme'];

                if ($this->_config['useWeblinkUrl'] !== 'false' && $tempDocInfo['class_key'] == 'modWebLink') {
                    if (is_numeric($tempDocInfo['content'])) {
                        $tempDocInfo['link'] = $this->modx->makeUrl(intval($tempDocInfo['content']),'','',$linkScheme);
                    } else {
                        $tempDocInfo['link'] = $tempDocInfo['content'];
                    }
                } elseif ($tempDocInfo['id'] == $this->modx->getOption('site_start')) {
                    $tempDocInfo['link'] = $this->modx->getOption('site_url');
                } else {
                    $tempDocInfo['link'] = $this->modx->makeUrl($tempDocInfo['id'],'','',$linkScheme);
                }
                /* determine the level, if parent has changed */
                if ($prevParent !== $tempDocInfo['parent']) {
                    $level = count($this->modx->getParentIds($tempDocInfo['id'])) - $startLevel;
                }
                /* add parent to hasChildren array for later processing */
                if (($level > 1 || $this->_config['displayStart']) && !in_array($tempDocInfo['parent'],$this->hasChildren)) {
                    $this->hasChildren[] = $tempDocInfo['parent'];
                }
                /* set the level */
                $tempDocInfo['level'] = $level;
                $prevParent = $tempDocInfo['parent'];
                /* determine other output options */
                $useTextField = (empty($tempDocInfo[$this->_config['textOfLinks']])) ? 'pagetitle' : $this->_config['textOfLinks'];
                $tempDocInfo['linktext'] = $tempDocInfo[$useTextField];
                $tempDocInfo['title'] = $tempDocInfo[$this->_config['titleOfLinks']];
                $tempDocInfo['protected'] = !empty($tempDocInfo['protected']);
                if (!empty($this->tvList)) {
                    $tempResults[] = $tempDocInfo;
                } else {
                    $resourceArray[$tempDocInfo['level']][$tempDocInfo['parent']][] = $tempDocInfo;
                }
            }
            /* process the tvs */
            if (!empty($this->tvList) && !empty($resultIds)) {
                $tvValues = array();
                /* loop through all tvs and get their values for each resource */
                foreach ($this->tvList as $tvName) {
                    $tvValues = array_merge_recursive($this->appendTV($tvName,$resultIds),$tvValues);
                }
                /* loop through the document array and add the tvarpublic ues to each resource */
                foreach ($tempResults as $tempDocInfo) {
                    if (array_key_exists("#{$tempDocInfo['id']}",$tvValues)) {
                        foreach ($tvValues["#{$tempDocInfo['id']}"] as $tvName => $tvValue) {
                            $tempDocInfo[$tvName] = $tvValue;
                        }
                    }
                    $resourceArray[$tempDocInfo['level']][$tempDocInfo['parent']][] = $tempDocInfo;
                }
            }
            if (!empty($switchedContext)) {
                $this->modx->switchContext($activeContext);
            }
        }
        return $resourceArray;
    }

    /**
     * Append a TV to the resource array
     *
     * @param string $tvName Name of the Template Variable to append
     * @param array $docIds An array of document IDs to append the TV to
     * @return array A resource array with the TV information
     */
    public function appendTV($tvName,$docIds){
        $resourceArray = array();
        /** @var modTemplateVar $tv */
        if (empty($this->_cachedTVs[$tvName])) {
            $tv = $this->modx->getObject('modTemplateVar',array(
                'name' => $tvName,
            ));
        } else {
            $tv =& $this->_cachedTVs[$tvName];
        }
        if ($tv) {
            foreach ($docIds as $docId) {
                $resourceArray["#{$docId}"][$tvName] = $tv->renderOutput($docId);
            }
        }
        return $resourceArray;
    }

}