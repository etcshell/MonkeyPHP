<?php
namespace Library;

/**
 * Tree
 * 无限分类类
 * @package Library
 */
class Tree {

    private $idKey = 'id';
    private $pidKey = 'pid';
    private $nameKey = 'name';
    private $leftKey = 'left';
    private $rightKey = 'right';
    //    private $icon = array('　','│','├','└');
    private $data;

    /**
     * 无限分类树
     * @param array $data 节点数据
     * @param string $idKey 节点号键名
     * @param string $pidKey 父节点号键名
     * @param string $nameKey 节点名键名
     */
    public function __construct($data, $idKey = '', $pidKey = '', $nameKey = '') {
        $this->loadData($data, $idKey, $pidKey, $nameKey);
    }

    /**
     * 装载节点数据
     * @param array $data 节点数据
     * @param string $idKey 节点号键名
     * @param string $pidKey 父节点号键名
     * @param string $nameKey 节点名键名
     */
    public function loadData($data, $idKey = '', $pidKey = '', $nameKey = '') {
        $this->data = $data;
        if (!empty($idKey)) {
            $this->idKey = $idKey;
        }
        if (!empty($pidKey)) {
            $this->pidKey = $pidKey;
        }
        if (!empty($nameKey)) {
            $this->nameKey = $nameKey;
        }
    }

    /**
     * 查找子节点
     * @param int $pid 父节点号
     * @param array $data 节点数据
     * @return array 子节点数据
     */
    public function findChildren($pid, $data = null) {
        if (is_null($data)) {
            $data = $this->data;
        }
        $children = array();
        foreach ($data as $node) {
            if ($node[$this->pidKey] == $pid) {
                $children[] = $node;
            }
        }
        return $children;
    }

    /**
     * 建立有前导图标的树图
     * @param int $id 树根的节点号
     * @param string $iconFix 图标前导修正
     * @param array $data 节点数据
     * @return array 树图数据
     * 注释掉的'deep'字段可以记录每个节点的深度信息。
     * 取消注释就可以正常使用了。
     */
    public function buildTreeOfIcon($id, $iconFix = '', &$data = null) {
        if (empty($data)) {
            $data = $this->data;
        }
        $tree = array();
        //搜索树根
        foreach ($data as $key => $root) {
            if ($root[$this->idKey] == $id) {
                $root['ifix'] = $iconFix;
                $root['icon'] = '';
                $root['deep'] = 0;
                $tree[] = $root; //压入树中
                unset($data[$key]); //从数据中删除搜索到的树根，减小以后的搜索量
            }
        }
        if (empty($tree)) {
            return $tree;
        } //树根不存在时直接返回空树
        //搜索子树
        $stack = array();
        $lastDeep = 0;
        $lastDeepIconFix = $iconFix;
        while (true) {
            //搜索子节点，并将子节点入栈
            $children = array();
            $childrenCount = 0;
            foreach ($data as $key => $node) {
                if ($node[$this->pidKey] == $id) {
                    $node['deep'] = $lastDeep + 1; //写入层数
                    $node['ifix'] = $lastDeepIconFix;
                    $node['icon'] = '　├';
                    $children[] = $node;
                    $childrenCount++;
                    unset($data[$key]); //从数据中删除搜索到的子节点，减小以后的搜索量
                }
            }
            if ($childrenCount) {
                $children[$childrenCount - 1]['icon'] = '　└';
                $stack = array_merge($children, $stack);
            }
            //分析栈
            if (empty($stack)) {
                break;
            } //空栈退出循环
            $currentNode = array_shift($stack);
            $lastDeep = $currentNode['deep'];
            $lastDeepIconFix = $currentNode['ifix'] . ($currentNode['icon'] == '　└' ? '　　' : '　│');
            $id = $currentNode[$this->idKey]; //获取下次搜索的父节点号
            $tree[] = $currentNode; //压入树中
        }
        foreach ($tree as $key => $node) {
            $tree[$key]['icon'] = $node['ifix'] . $node['icon'];
            unset($tree[$key]['ifix']);
        }
        return $tree;
    }

    /**
     * 建立有前导图标的子树图
     * @param int $pid 树林的父节点号
     * @param string $iconFix 图标前导修正
     * @param array $data 节点数据
     * @return array 树图数据
     * 注释掉的'deep'字段可以记录每个节点的深度信息。
     * 取消注释就可以正常使用了。
     */
    public function buildSubtreeOfIcon($pid, $iconFix = '', $data = null) {
        if (empty($data)) {
            $data = $this->data;
        }
        $subTree = array();
        foreach ($data as $node) {
            if ($node[$this->pidKey] == $pid) {
                $subTree = array_merge(
                    $subTree,
                    $this->buildTreeOfIcon(
                        $node[$this->idKey],
                        $iconFix,
                        $data
                    )
                );
            }
        }
        return $subTree;
    }

    /**
     * 建立有前导图标的森林图
     * @param array $idArray 由每颗树的根节点号组成的数组
     * @param string $iconFix 图标前导修正
     * @param array $data 节点数据
     * @return array 树图数据
     * 注释掉的'deep'字段可以记录每个节点的深度信息。
     * 取消注释就可以正常使用了。
     */
    public function buildForestOfIcon($idArray, $iconFix = '', $data = null) {
        if (empty($data)) {
            $data = $this->data;
        }
        $forest = array();
        foreach ($idArray as $id) {
            $temp = $data;
            $forest = array_merge($forest, $this->buildTreeOfIcon($id, $iconFix, $temp));
        }
        return $forest;
    }

    /**
     * 建立的树结构（无前导图标）
     * @param array $data 节点数据
     * @param int $rootid 根节点的节点id
     * @param string $idKey 节点id键名
     * @param string $pidKey 父节点id键名
     * @param string $children 子节点键名
     * @return array 树结构，非线性的，可以直接转为json，或用递归方式列遍树
     */
    public function buildTree($data, $rootid = 0, $idKey = 'id', $pidKey = 'pid', $children = 'children') {
        $tree = array();
        if (is_array($data)) {
            $temp = array();
            foreach ($data as $key => $val) {
                $temp[$val[$idKey]] =& $data[$key];
            }
            foreach ($data as $key => $val) {
                $parentId = $val[$pidKey];
                if ($rootid == $parentId) {
                    $tree[] = & $data[$key];
                }
                else {
                    if (isset($temp[$parentId])) {
                        $parent = & $temp[$parentId];
                        $parent[$children][] = & $data[$key];
                    }
                }
            }
        }
        return $tree;
    }

    /**
     * 有序树转化为无序树
     * @param array $data 节点数据
     * $data=array(
     * array(notevalue,left_key, right_key),
     * array(notevalue,left_key, right_key),
     * ......
     * )
     * @param bool $leftIsOrder 是否按照左边界做了升序排序
     * @param integer|null $rootId 根节点id，默认第一个节点就是根节点
     * @param string|null $leftKey 左边界键名，默认为left
     * @param string|null $rightKey 右边界键名，默认为right
     * @return array 已编排好了的无序树，可以用作本类树图方法的数据源
     */
    public function orderedToUnordered(
        $data, $leftIsOrder = true, $rootId = null, $leftKey = null, $rightKey = null
    ) {
        $leftKey and $this->leftKey = $leftKey;
        $rightKey and $this->rightKey = $rightKey;
        if ($rootId) {
            $data = $this->nodeFilterById($data, $rootId);
        }
        if (empty($data)) {
            return $data;
        }
        if (!$leftIsOrder) {
            $data = $this->orderLeft($data);
        }
        $parentKey = null;
        $rightParentKeys = array();
        $i = 0;
        foreach ($data as $key => $node) {
            if (is_null($parentKey)) {
                $data[$key][$this->pidKey] = -1;
            }
            elseif ($data[$parentKey][$this->leftKey] + 1 == $node[$this->leftKey]) {
                $data[$key][$this->pidKey] = $data[$parentKey][$this->idKey];
            }
            elseif (isset($rightParentKeys[$node[$this->leftKey] - 1])) {
                $parentKey = $rightParentKeys[$node[$this->leftKey] - 1];
                $data[$key][$this->pidKey] = $data[$parentKey][$this->idKey];
            }
            else {
                $data[$key][$this->pidKey] = -1;
                $parentKey = null;
            }
            $data[$key][$this->idKey] = $i++;
            $rightParentKeys[$node[$this->rightKey]] = $parentKey;
            $parentKey = $key;
        }
        return $data;
    }

    /**
     * 有序树转化为无序树结构
     * @param array $data 节点数据
     * $data=array(
     * array(notevalue,left_key, right_key),
     * array(notevalue,left_key, right_key),
     * ......
     * )
     * @param string $children 保存子节点的键名
     * @param bool $leftIsOrder 是否按照左边界做了升序排序
     * @param integer|null $rootLeft 根节点左边界值，默认左边界值最小的（排序后，第一个节点就是根节点）
     * @param string|null $leftKey 左边界键名，默认为left
     * @param string|null $rightKey 右边界键名，默认为right
     * @return array 已编排好了的无序树结构，非线性的，可以直接转为json，或用递归方式列遍树
     */
    public function orderedToTree(
        $data, $children = 'children', $leftIsOrder = true, $rootLeft = null, $leftKey = null, $rightKey = null
    ) {
        $leftKey and $this->leftKey = $leftKey;
        $rightKey and $this->rightKey = $rightKey;
        if ($rootLeft) {
            $data = $this->nodeFilterByLeft($data, $rootLeft);
        }
        if (empty($data)) {
            return $data;
        }
        if (!$leftIsOrder) {
            $data = $this->orderLeft($data);
        }
        $parentKey = null;
        $rightParentKeys = array();
        $tree = array();
        foreach ($data as $key => $node) {
            if (is_null($parentKey)) {
                $tree[] = & $data[$key];
            }
            elseif ($data[$parentKey][$this->leftKey] + 1 == $node[$this->leftKey]) {
                $data[$parentKey][$children][] = & $data[$key];
            }
            elseif (isset($rightParentKeys[$node[$this->leftKey] - 1])) {
                $parentKey = $rightParentKeys[$node[$this->leftKey] - 1];
                $data[$parentKey][$children][] = & $data[$key];
            }
            else {
                $tree[] = & $data[$key];
                $parentKey = null;
            }
            $rightParentKeys[$node[$this->rightKey]] = $parentKey;
            $parentKey = $key;
        }
        return $tree;
    }

    private function orderLeft($data) {
        $tempData = array();
        foreach ($data as $node) {
            $tempData[$node[$this->leftKey]] = $node;
        }
        ksort($tempData);
        return $tempData;
    }

    private function nodeFilterById($data, $rootId) {
        $rangeNode = array();
        foreach ($data as $node) {
            if ($node[$this->idKey] == $rootId) {
                $rangeNode[] = $node;
                break;
            }
        }
        if (empty($rangeNode)) {
            return $rangeNode;
        }
        $rootLeft = $rangeNode[0][$this->leftKey];
        $rootRight = $rangeNode[0][$this->rightKey];
        foreach ($data as $node) {
            if ($node[$this->leftKey] > $rootLeft && $node[$this->rightKey] < $rootRight) {
                $rangeNode[] = $node;
            }
        }
        return $rangeNode;
    }

    private function nodeFilterByLeft($data, $rootLeft) {
        $rangeNode = array();
        foreach ($data as $node) {
            if ($node[$this->leftKey] == $rootLeft) {
                $rangeNode[] = $node;
                break;
            }
        }
        if (empty($rangeNode)) {
            return $rangeNode;
        }
        $rootRight = $rangeNode[0][$this->rightKey];
        foreach ($data as $node) {
            if ($node[$this->leftKey] > $rootLeft && $node[$this->rightKey] < $rootRight) {
                $rangeNode[] = $node;
            }
        }
        return $rangeNode;
    }
}