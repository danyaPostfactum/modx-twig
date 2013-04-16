<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Данил
 * Date: 15.04.13
 * Time: 14:57
 * To change this template use File | Settings | File Templates.
 */
class getResources
{
    /**
     * getResources
     *
     * A general purpose Resource listing and summarization snippet for MODX 2.x.
     *
     * @author Jason Coward
     * @copyright Copyright 2010-2012, Jason Coward
     *
     * TEMPLATES
     *
     * tpl - Name of a chunk serving as a resource template
     * [NOTE: if not provided, properties are dumped to output for each resource]
     *
     * tplOdd - (Opt) Name of a chunk serving as resource template for resources with an odd idx value
     * (see idx property)
     * tplFirst - (Opt) Name of a chunk serving as resource template for the first resource (see first
     * property)
     * tplLast - (Opt) Name of a chunk serving as resource template for the last resource (see last
     * property)
     * tpl_{n} - (Opt) Name of a chunk serving as resource template for the nth resource
     *
     * SELECTION
     *
     * parents - Comma-delimited list of ids serving as parents
     *
     * context - (Opt) Comma-delimited list of context keys to limit results by; if empty, contexts for all specified
     * parents will be used (all contexts if 0 is specified) [default=]
     *
     * depth - (Opt) Integer value indicating depth to search for resources from each parent [default=10]
     *
     * tvFilters - (Opt) Delimited-list of TemplateVar values to filter resources by. Supports two
     * delimiters and two value search formats. The first delimiter || represents a logical OR and the
     * primary grouping mechanism.  Within each group you can provide a comma-delimited list of values.
     * These values can be either tied to a specific TemplateVar by name, e.g. myTV==value, or just the
     * value, indicating you are searching for the value in any TemplateVar tied to the Resource. An
     * example would be &tvFilters=`filter2==one,filter1==bar%||filter1==foo`
     * [NOTE: filtering by values uses a LIKE query and % is considered a wildcard.]
     * [NOTE: this only looks at the raw value set for specific Resource, i. e. there must be a value
     * specifically set for the Resource and it is not evaluated.]
     *
     * tvFiltersAndDelimiter - (Opt) Custom delimiter for logical AND, default ',', in case you want to
     * match a literal comma in the tvFilters. E.g. &tvFiltersAndDelimiter=`&&`
     * &tvFilters=`filter1==foo,bar&&filter2==baz` [default=,]
     *
     * tvFiltersOrDelimiter - (Opt) Custom delimiter for logical OR, default '||', in case you want to
     * match a literal '||' in the tvFilters. E.g. &tvFiltersOrDelimiter=`|OR|`
     * &tvFilters=`filter1==foo||bar|OR|filter2==baz` [default=||]
     *
     * where - (Opt) A JSON expression of criteria to build any additional where clauses from. An example would be
     * &where=`{{"alias:LIKE":"foo%", "OR:alias:LIKE":"%bar"},{"OR:pagetitle:=":"foobar", "AND:description:=":"raboof"}}`
     *
     * sortby - (Opt) Field to sort by or a JSON array, e.g. {"publishedon":"ASC","createdon":"DESC"} [default=publishedon]
     * sortbyTV - (opt) A Template Variable name to sort by (if supplied, this precedes the sortby value) [default=]
     * sortbyTVType - (Opt) A data type to CAST a TV Value to in order to sort on it properly [default=string]
     * sortbyAlias - (Opt) Query alias for sortby field [default=]
     * sortbyEscaped - (Opt) Escapes the field name(s) specified in sortby [default=0]
     * sortdir - (Opt) Order which to sort by [default=DESC]
     * sortdirTV - (Opt) Order which to sort by a TV [default=DESC]
     * limit - (Opt) Limits the number of resources returned [default=5]
     * offset - (Opt) An offset of resources returned by the criteria to skip [default=0]
     * dbCacheFlag - (Opt) Controls caching of db queries; 0|false = do not cache result set; 1 = cache result set
     * according to cache settings, any other integer value = number of seconds to cache result set [default=0]
     *
     * OPTIONS
     *
     * includeContent - (Opt) Indicates if the content of each resource should be returned in the
     * results [default=0]
     * includeTVs - (Opt) Indicates if TemplateVar values should be included in the properties available
     * to each resource template [default=0]
     * includeTVList - (Opt) Limits the TemplateVars that are included if includeTVs is true to those specified
     * by name in a comma-delimited list [default=]
     * prepareTVs - (Opt) Prepares media-source dependent TemplateVar values [default=1]
     * prepareTVList - (Opt) Limits the TVs that are prepared to those specified by name in a comma-delimited
     * list [default=]
     * processTVs - (Opt) Indicates if TemplateVar values should be rendered as they would on the
     * resource being summarized [default=0]
     * processTVList - (opt) Limits the TemplateVars that are processed if included to those specified
     * by name in a comma-delimited list [default=]
     * tvPrefix - (Opt) The prefix for TemplateVar properties [default=tv.]
     * idx - (Opt) You can define the starting idx of the resources, which is an property that is
     * incremented as each resource is rendered [default=1]
     * first - (Opt) Define the idx which represents the first resource (see tplFirst) [default=1]
     * last - (Opt) Define the idx which represents the last resource (see tplLast) [default=# of
     * resources being summarized + first - 1]
     * outputSeparator - (Opt) An optional string to separate each tpl instance [default="\n"]
     *
     */
    function __construct(modX &$modx,array $options = array()) {
        $this->modx =& $modx;
        $output = array();

        $defaults = array(
            'includeContent' => false,
            'includeTVs' => false,
            'includeTVList' => array(),
            'processTVs' => false,
            'processTVList' => array(),
            'prepareTVs' => false,
            'prepareTVList' => array(),
            'depth' => 10,
            'sortby' => 'publishedon',
            'sortdir' => 'DESC',
            'where' => array(),
            'showUnpublished' => false,
            'showDeleted' => false,
            'debug' => false,
            'dbCacheFlag' => false,
            'totalVar' => 'total'
        );

        if (isset($options['parent'])){
            $options['parents'] = (array) $options['parent'];
        }
        if (empty($options['parents'])) {
            $options['parents'] = array($modx->resource->get('id'));
        }
        $this->options = array_merge($defaults, $options);
    }

//$includeContent = !empty($includeContent) ? true : false;
//$includeTVs = !empty($includeTVs) ? true : false;
//$includeTVList = !empty($includeTVList) ? explode(',', $includeTVList) : array();
//$processTVs = !empty($processTVs) ? true : false;
//$processTVList = !empty($processTVList) ? explode(',', $processTVList) : array();
//$prepareTVs = !empty($prepareTVs) ? true : false;
//$prepareTVList = !empty($prepareTVList) ? explode(',', $prepareTVList) : array();
//$tvPrefix = isset($tvPrefix) ? $tvPrefix : 'tv.';
//$parents = (!empty($parents) || $parents === '0') ? explode(',', $parents) : array($modx->resource->get('id'));
//array_walk($parents, 'trim');
//$parents = array_unique($parents);
//$depth = isset($depth) ? (integer) $depth : 10;
//
//$tvFiltersOrDelimiter = isset($tvFiltersOrDelimiter) ? $tvFiltersOrDelimiter : '||';
//$tvFiltersAndDelimiter = isset($tvFiltersAndDelimiter) ? $tvFiltersAndDelimiter : ',';
//$tvFilters = !empty($tvFilters) ? explode($tvFiltersOrDelimiter, $tvFilters) : array();
//
//$where = !empty($where) ? $modx->fromJSON($where) : array();
//$showUnpublished = !empty($showUnpublished) ? true : false;
//$showDeleted = !empty($showDeleted) ? true : false;
//
//$sortby = isset($sortby) ? $sortby : 'publishedon';
//$sortbyTV = isset($sortbyTV) ? $sortbyTV : '';
//$sortbyAlias = isset($sortbyAlias) ? $sortbyAlias : 'modResource';
//$sortbyEscaped = !empty($sortbyEscaped) ? true : false;
//$sortdir = isset($sortdir) ? $sortdir : 'DESC';
//$sortdirTV = isset($sortdirTV) ? $sortdirTV : 'DESC';
//$limit = isset($limit) ? (integer) $limit : 5;
//$offset = isset($offset) ? (integer) $offset : 0;
//$totalVar = !empty($totalVar) ? $totalVar : 'total';
//

    function run(){
        $modx =& $this->modx;
        extract($this->options);

/* multiple context support */
$contextArray = array();
$contextSpecified = false;
if (!empty($context)) {
    $contexts = array();
    foreach ($context as $ctx) {
        $contexts[] = $modx->quote($ctx);
    }
    $context = implode(',',$contexts);
    $contextSpecified = true;
    unset($contexts,$ctx);
} else {
    $context = $modx->quote($modx->context->get('key'));
}

$pcMap = array();
$pcQuery = $modx->newQuery('modResource', array('id:IN' => $parents), $dbCacheFlag);
$pcQuery->select(array('id', 'context_key'));
if ($pcQuery->prepare() && $pcQuery->stmt->execute()) {
    foreach ($pcQuery->stmt->fetchAll(PDO::FETCH_ASSOC) as $pcRow) {
        $pcMap[(integer) $pcRow['id']] = $pcRow['context_key'];
    }
}

$children = array();
$parentArray = array();
foreach ($parents as $parent) {
    $parent = (integer) $parent;
    if ($parent === 0) {
        $pchildren = array();
        if ($contextSpecified) {
            foreach ($contextArray as $pCtx) {
                if (!in_array($pCtx, $contextArray)) {
                    continue;
                }
                $options = $pCtx !== $modx->context->get('key') ? array('context' => $pCtx) : array();
                $pcchildren = $modx->getChildIds($parent, $depth, $options);
                if (!empty($pcchildren)) $pchildren = array_merge($pchildren, $pcchildren);
            }
        } else {
            $cQuery = $modx->newQuery('modContext', array('key:!=' => 'mgr'));
            $cQuery->select(array('key'));
            if ($cQuery->prepare() && $cQuery->stmt->execute()) {
                foreach ($cQuery->stmt->fetchAll(PDO::FETCH_COLUMN) as $pCtx) {
                    $options = $pCtx !== $modx->context->get('key') ? array('context' => $pCtx) : array();
                    $pcchildren = $modx->getChildIds($parent, $depth, $options);
                    if (!empty($pcchildren)) $pchildren = array_merge($pchildren, $pcchildren);
                }
            }
        }
        $parentArray[] = $parent;
    } else {
        $pContext = array_key_exists($parent, $pcMap) ? $pcMap[$parent] : false;
        if ($debug) $modx->log(modX::LOG_LEVEL_ERROR, "context for {$parent} is {$pContext}");

        if ($pContext && $contextSpecified && !in_array($pContext, $contextArray, true)) {
            $parent = next($parents);
            continue;
        }
        $parentArray[] = $parent;
        $options = !empty($pContext) && $pContext !== $modx->context->get('key') ? array('context' => $pContext) : array();
        $pchildren = $modx->getChildIds($parent, $depth, $options);
    }
    if (!empty($pchildren)) $children = array_merge($children, $pchildren);
    $parent = next($parents);
}
$parents = array_merge($parentArray, $children);
/* build query */
$criteria = array("modResource.parent IN (" . implode(',', $parents) . ")");
if ($contextSpecified) {
    $contextResourceTbl = $modx->getTableName('modContextResource');
    $criteria[] = "(modResource.context_key IN ({$context}) OR EXISTS(SELECT 1 FROM {$contextResourceTbl} ctx WHERE ctx.resource = modResource.id AND ctx.context_key IN ({$context})))";
}
if (empty($showDeleted)) {
    $criteria['deleted'] = '0';
}
if (empty($showUnpublished)) {
    $criteria['published'] = '1';
}
if (empty($showHidden)) {
    $criteria['hidemenu'] = '0';
}
if (!empty($hideContainers)) {
    $criteria['isfolder'] = '0';
}
$criteria = $modx->newQuery('modResource', $criteria);
if (!empty($tvFilters)) {
    $tmplVarTbl = $modx->getTableName('modTemplateVar');
    $tmplVarResourceTbl = $modx->getTableName('modTemplateVarResource');
    $conditions = array();
    $operators = array(
        '<=>' => '<=>',
        '===' => '=',
        '!==' => '!=',
        '<>' => '<>',
        '==' => 'LIKE',
        '!=' => 'NOT LIKE',
        '<<' => '<',
        '<=' => '<=',
        '=<' => '=<',
        '>>' => '>',
        '>=' => '>=',
        '=>' => '=>'
    );
    foreach ($tvFilters as $fGroup => $tvFilter) {
        $filterGroup = array();
        $filters = explode($tvFiltersAndDelimiter, $tvFilter);
        $multiple = count($filters) > 0;
        foreach ($filters as $filter) {
            $operator = '==';
            $sqlOperator = 'LIKE';
            foreach ($operators as $op => $opSymbol) {
                if (strpos($filter, $op, 1) !== false) {
                    $operator = $op;
                    $sqlOperator = $opSymbol;
                    break;
                }
            }
            $tvValueField = 'tvr.value';
            $tvDefaultField = 'tv.default_text';
            $f = explode($operator, $filter);
            if (count($f) == 2) {
                $tvName = $modx->quote($f[0]);
                if (is_numeric($f[1]) && !in_array($sqlOperator, array('LIKE', 'NOT LIKE'))) {
                    $tvValue = $f[1];
                    if ($f[1] == (integer)$f[1]) {
                        $tvValueField = "CAST({$tvValueField} AS SIGNED INTEGER)";
                        $tvDefaultField = "CAST({$tvDefaultField} AS SIGNED INTEGER)";
                    } else {
                        $tvValueField = "CAST({$tvValueField} AS DECIMAL)";
                        $tvDefaultField = "CAST({$tvDefaultField} AS DECIMAL)";
                    }
                } else {
                    $tvValue = $modx->quote($f[1]);
                }
                if ($multiple) {
                    $filterGroup[] =
                        "(EXISTS (SELECT 1 FROM {$tmplVarResourceTbl} tvr JOIN {$tmplVarTbl} tv ON {$tvValueField} {$sqlOperator} {$tvValue} AND tv.name = {$tvName} AND tv.id = tvr.tmplvarid WHERE tvr.contentid = modResource.id) " .
                            "OR EXISTS (SELECT 1 FROM {$tmplVarTbl} tv WHERE tv.name = {$tvName} AND {$tvDefaultField} {$sqlOperator} {$tvValue} AND tv.id NOT IN (SELECT tmplvarid FROM {$tmplVarResourceTbl} WHERE contentid = modResource.id)) " .
                            ")";
                } else {
                    $filterGroup =
                        "(EXISTS (SELECT 1 FROM {$tmplVarResourceTbl} tvr JOIN {$tmplVarTbl} tv ON {$tvValueField} {$sqlOperator} {$tvValue} AND tv.name = {$tvName} AND tv.id = tvr.tmplvarid WHERE tvr.contentid = modResource.id) " .
                            "OR EXISTS (SELECT 1 FROM {$tmplVarTbl} tv WHERE tv.name = {$tvName} AND {$tvDefaultField} {$sqlOperator} {$tvValue} AND tv.id NOT IN (SELECT tmplvarid FROM {$tmplVarResourceTbl} WHERE contentid = modResource.id)) " .
                            ")";
                }
            } elseif (count($f) == 1) {
                $tvValue = $modx->quote($f[0]);
                if ($multiple) {
                    $filterGroup[] = "EXISTS (SELECT 1 FROM {$tmplVarResourceTbl} tvr JOIN {$tmplVarTbl} tv ON {$tvValueField} {$sqlOperator} {$tvValue} AND tv.id = tvr.tmplvarid WHERE tvr.contentid = modResource.id)";
                } else {
                    $filterGroup = "EXISTS (SELECT 1 FROM {$tmplVarResourceTbl} tvr JOIN {$tmplVarTbl} tv ON {$tvValueField} {$sqlOperator} {$tvValue} AND tv.id = tvr.tmplvarid WHERE tvr.contentid = modResource.id)";
                }
            }
        }
        $conditions[] = $filterGroup;
    }
    if (!empty($conditions)) {
        $firstGroup = true;
        foreach ($conditions as $cGroup => $c) {
            if (is_array($c)) {
                $first = true;
                foreach ($c as $cond) {
                    if ($first && !$firstGroup) {
                        $criteria->condition($criteria->query['where'][0][1], $cond, xPDOQuery::SQL_OR, null, $cGroup);
                    } else {
                        $criteria->condition($criteria->query['where'][0][1], $cond, xPDOQuery::SQL_AND, null, $cGroup);
                    }
                    $first = false;
                }
            } else {
                $criteria->condition($criteria->query['where'][0][1], $c, $firstGroup ? xPDOQuery::SQL_AND : xPDOQuery::SQL_OR, null, $cGroup);
            }
            $firstGroup = false;
        }
    }
}
/* include/exclude resources, via &resources=`123,-456` prop */
if (!empty($resources)) {
    $resourceConditions = array();
    $resources = explode(',',$resources);
    $include = array();
    $exclude = array();
    foreach ($resources as $resource) {
        $resource = (int)$resource;
        if ($resource == 0) continue;
        if ($resource < 0) {
            $exclude[] = abs($resource);
        } else {
            $include[] = $resource;
        }
    }
    if (!empty($include)) {
        $criteria->where(array('OR:modResource.id:IN' => $include), xPDOQuery::SQL_OR);
    }
    if (!empty($exclude)) {
        $criteria->where(array('modResource.id:NOT IN' => $exclude), xPDOQuery::SQL_AND, null, 1);
    }
}
if (!empty($where)) {
    $criteria->where($where);
}

$total = $modx->getCount('modResource', $criteria);

$modx->setPlaceholder($totalVar, $total);

$fields = array_keys($modx->getFields('modResource'));
if (empty($includeContent)) {
    $fields = array_diff($fields, array('content'));
}
$columns = $includeContent ? $modx->getSelectColumns('modResource', 'modResource') : $modx->getSelectColumns('modResource', 'modResource', '', array('content'), true);
$criteria->select($columns);
if (!empty($sortbyTV)) {
    $criteria->leftJoin('modTemplateVar', 'tvDefault', array(
        "tvDefault.name" => $sortbyTV
    ));
    $criteria->leftJoin('modTemplateVarResource', 'tvSort', array(
        "tvSort.contentid = modResource.id",
        "tvSort.tmplvarid = tvDefault.id"
    ));
    if (empty($sortbyTVType)) $sortbyTVType = 'string';
    if ($modx->getOption('dbtype') === 'mysql') {
        switch ($sortbyTVType) {
            case 'integer':
                $criteria->select("CAST(IFNULL(tvSort.value, tvDefault.default_text) AS SIGNED INTEGER) AS sortTV");
                break;
            case 'decimal':
                $criteria->select("CAST(IFNULL(tvSort.value, tvDefault.default_text) AS DECIMAL) AS sortTV");
                break;
            case 'datetime':
                $criteria->select("CAST(IFNULL(tvSort.value, tvDefault.default_text) AS DATETIME) AS sortTV");
                break;
            case 'string':
            default:
                $criteria->select("IFNULL(tvSort.value, tvDefault.default_text) AS sortTV");
                break;
        }
    } elseif ($modx->getOption('dbtype') === 'sqlsrv') {
        switch ($sortbyTVType) {
            case 'integer':
                $criteria->select("CAST(ISNULL(tvSort.value, tvDefault.default_text) AS BIGINT) AS sortTV");
                break;
            case 'decimal':
                $criteria->select("CAST(ISNULL(tvSort.value, tvDefault.default_text) AS DECIMAL) AS sortTV");
                break;
            case 'datetime':
                $criteria->select("CAST(ISNULL(tvSort.value, tvDefault.default_text) AS DATETIME) AS sortTV");
                break;
            case 'string':
            default:
                $criteria->select("ISNULL(tvSort.value, tvDefault.default_text) AS sortTV");
                break;
        }
    }
    $criteria->sortby("sortTV", $sortdirTV);
}
if (!empty($sortby)) {
    if (strpos($sortby, '{') === 0) {
        $sorts = $modx->fromJSON($sortby);
    } else {
        $sorts = array($sortby => $sortdir);
    }
    if (is_array($sorts)) {
        while (list($sort, $dir) = each($sorts)) {
            if ($sortbyEscaped) $sort = $modx->escape($sort);
            if (!empty($sortbyAlias)) $sort = $modx->escape($sortbyAlias) . ".{$sort}";
            $criteria->sortby($sort, $dir);
        }
    }
}
if (!empty($limit)) $criteria->limit($limit, $offset);

if (!empty($debug)) {
    $criteria->prepare();
    $modx->log(modX::LOG_LEVEL_ERROR, $criteria->toSQL());
}
$collection = $modx->getCollection('modResource', $criteria, $dbCacheFlag);

$idx = !empty($idx) && $idx !== '0' ? (integer) $idx : 1;
$first = empty($first) && $first !== '0' ? 1 : (integer) $first;
$last = empty($last) ? (count($collection) + $idx - 1) : (integer) $last;


$templateVars = array();
if (!empty($includeTVs) && !empty($includeTVList)) {
    $templateVars = $modx->getCollection('modTemplateVar', array('name:IN' => $includeTVList));
}
/** @var modResource $resource */
foreach ($collection as $resourceId => $resource) {
    $tvs = array();
    if (!empty($includeTVs)) {
        if (empty($includeTVList)) {
            $templateVars = $resource->getMany('TemplateVars');
        }
        /** @var modTemplateVar $templateVar */
        foreach ($templateVars as $tvId => $templateVar) {
            if (!empty($includeTVList) && !in_array($templateVar->get('name'), $includeTVList)) continue;
            if ($processTVs && (empty($processTVList) || in_array($templateVar->get('name'), $processTVList))) {
                $tvs[$templateVar->get('name')] = $templateVar->renderOutput($resource->get('id'));
            } else {
                $value = $templateVar->getValue($resource->get('id'));
                if ($prepareTVs && method_exists($templateVar, 'prepareOutput') && (empty($prepareTVList) || in_array($templateVar->get('name'), $prepareTVList))) {
                    $value = $templateVar->prepareOutput($value);
                }
                $tvs[$templateVar->get('name')] = $value;
            }
        }
    }
    $odd = ($idx & 1);
    $properties = array_merge(
        array(
            'idx' => $idx
        ,'first' => $first
        ,'last' => $last
        ,'odd' => $odd
        )
        ,$includeContent ? $resource->toArray() : $resource->get($fields)
        ,$tvs
    );
    $output[]= $properties;

    $idx++;
}


return $output;

    }
}