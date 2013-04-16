<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Данил
 * Date: 15.04.13
 * Time: 20:41
 * To change this template use File | Settings | File Templates.
 */
class getPage
{
    function __construct(modX &$modx, array $options = array()){
        $this->modx =& $modx;
        $this->count = 1;
        $this->page = isset($_REQUEST['page']) ? (integer) $_REQUEST['page'] : 1;
        $this->limit = isset($_REQUEST['limit']) ? (integer) $_REQUEST['limit'] : (integer) $options['limit'];
        $this->offset = (!empty($this->limit) && !empty($this->page)) ? ($this->limit * ($this->page - 1)) : 0;
        $this->total = !empty($options['total']) ? (integer) $options['total'] : 0;
        $this->element = $options['of'];
        $this->options = array_merge($options, array(
            'limit' => $this->limit,
            'offset' => $this->offset,
        ));
    }

    function run(){
        include_once MODX_CORE_PATH.'/components/twig/get' . $this->element . '.class.php';
        $class = 'get' . ucfirst($this->element);
        $element = new $class($this->modx,$this->options);
        $result = $element->run();
        $total = (integer) $this->modx->getPlaceholder('total');
        if ($total) {
            $this->total = $total;
        }
        if (!empty($this->total) && !empty($this->limit)) {
//            if ($properties['pageOneLimit'] !== $properties['actualLimit']) {
//                $adjustedTotal = $properties['total'] - $properties['pageOneLimit'];
//                $properties['pageCount'] = $adjustedTotal > 0 ? ceil($adjustedTotal / $properties['actualLimit']) + 1 : 1;
//            } else {
//                $properties['pageCount'] = ceil($properties['total'] / $properties['actualLimit']);
//            }
            $this->count = ceil($this->total / $this->limit);
        }
        if (empty($this->total) || empty($this->limit) || $this->total <= $this->limit /*|| ($this->page == 1 && $this->total <= $properties['pageOneLimit'])*/) {
            $this->page = 1;
        }

        return array(
            'result' => & $result,
            'count' => $this->count,
            'current' => $this->page,
        );
    }
}
