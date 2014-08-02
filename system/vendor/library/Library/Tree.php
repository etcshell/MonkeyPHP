<?php
namespace Library;

/**
 * Tree
 * 无限分类类
 * @package Library
 */
class Tree {
    private $_id_key = 'id';
    private $_pid_key = 'pid';
    private $_name_key = 'name';
    private $_left_key = 'left';
    private $_right_key = 'right';
    //    private $_icon = array('　','│','├','└');
    private $_data;

    /**
     * 无限分类树
     * @param array $data 节点数据
     * @param string $id_key 节点号键名
     * @param string $pid_key 父节点号键名
     * @param string $name_key 节点名键名
     */
    public function __construct($data, $id_key = '', $pid_key = '', $name_key = '') {
        $this->loadData($data, $id_key, $pid_key, $name_key);
    }

    /**
     * 装载节点数据
     * @param array $data 节点数据
     * @param string $id_key 节点号键名
     * @param string $pid_key 父节点号键名
     * @param string $name_key 节点名键名
     */
    public function loadData($data, $id_key = '', $pid_key = '', $name_key = '') {
        $this->_data = $data;
        if (!empty($id_key)) {
            $this->_id_key = $id_key;
        }
        if (!empty($pid_key))
            $this->_pid_key = $pid_key;
        if (!empty($name_key))
            $this->_name_key = $name_key;
    }

    /**
     * 查找子节点
     * @param int $pid 父节点号
     * @param array $data 节点数据
     * @return array 子节点数据
     */
    public function findChildren($pid, $data = null) {
        if (is_null($data))
            $data = $this->_data;
        $children = array();
        foreach ($data as $node) {
            if ($node[$this->_pid_key] == $pid) {
                $children[] = $node;
            }
        }
        return $children;
    }

    /**
     * 建立有前导图标的树图
     * @param int $id 树根的节点号
     * @param string $icon_fix 图标前导修正
     * @param array $data 节点数据
     * @return array 树图数据
     * 注释掉的'deep'字段可以记录每个节点的深度信息。
     * 取消注释就可以正常使用了。
     */
    public function buildTreeOfIcon($id, $icon_fix = '', &$data = null) {
        if (empty($data))
            $data = $this->_data;
        $tree = array();
        //搜索树根
        foreach ($data as $key => $root) {
            if ($root[$this->_id_key] == $id) {
                $root['ifix'] = $icon_fix;
                $root['icon'] = '';
                $root['deep'] = 0;
                $tree[] = $root; //压入树中
                unset($data[$key]); //从数据中删除搜索到的树根，减小以后的搜索量
            }
        }
        if (empty($tree))
            return $tree; //树根不存在时直接返回空树
        //搜索子树
        $stack = array();
        $last_deep = 0;
        $last_deep_icon_fix = $icon_fix;
        while (true) {
            //搜索子节点，并将子节点入栈
            $children = array();
            $children_count = 0;
            foreach ($data as $key => $node) {
                if ($node[$this->_pid_key] == $id) {
                    $node['deep'] = $last_deep + 1; //写入层数
                    $node['ifix'] = $last_deep_icon_fix;
                    $node['icon'] = '　├';
                    $children[] = $node;
                    $children_count++;
                    unset($data[$key]); //从数据中删除搜索到的子节点，减小以后的搜索量
                }
            }
            if ($children_count) {
                $children[$children_count - 1]['icon'] = '　└';
                $stack = array_merge($children, $stack);
            }
            //分析栈
            if (empty($stack))
                break; //空栈退出循环
            $current_node = array_shift($stack);
            $last_deep = $current_node['deep'];
            $last_deep_icon_fix = $current_node['ifix'] . ($current_node['icon'] == '　└' ? '　　' : '　│');
            $id = $current_node[$this->_id_key]; //获取下次搜索的父节点号
            $tree[] = $current_node; //压入树中
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
     * @param string $icon_fix 图标前导修正
     * @param array $data 节点数据
     * @return array 树图数据
     * 注释掉的'deep'字段可以记录每个节点的深度信息。
     * 取消注释就可以正常使用了。
     */
    public function buildSubtreeOfIcon($pid, $icon_fix = '', $data = null) {
        if (empty($data))
            $data = $this->_data;
        $subTree = array();
        foreach ($data as $node) {
            if ($node[$this->_pid_key] == $pid) {
                $subTree = array_merge($subTree, $this->buildTreeOfIcon($node[$this->_id_key], $icon_fix, $data));
            }
        }
        return $subTree;
    }

    /**
     * 建立有前导图标的森林图
     * @param array $id_array 由每颗树的根节点号组成的数组
     * @param string $icon_fix 图标前导修正
     * @param array $data 节点数据
     * @return array 树图数据
     * 注释掉的'deep'字段可以记录每个节点的深度信息。
     * 取消注释就可以正常使用了。
     */
    public function buildForestOfIcon($id_array, $icon_fix = '', $data = null) {
        if (empty($data))
            $data = $this->_data;
        $forest = array();
        foreach ($id_array as $id) {
            $temp = $data;
            $forest = array_merge($forest, $this->buildTreeOfIcon($id, $icon_fix, $temp));
        }
        return $forest;
    }

    /**
     * 建立的树结构（无前导图标）
     * @param array $data 节点数据
     * @param int $rootid 根节点的节点id
     * @param string $id_key 节点id键名
     * @param string $pid_key 父节点id键名
     * @param string $children 子节点键名
     * @return array 树结构，非线性的，可以直接转为json，或用递归方式列遍树
     */
    public function buildTree($data, $rootid = 0, $id_key = 'id', $pid_key = 'pid', $children = 'children') {
        $tree = array();
        if (is_array($data)) {
            $_temp = array();
            foreach ($data as $key => $val) {
                $_temp[$val[$id_key]] =& $data[$key];
            }
            foreach ($data as $key => $val) {
                $parentId = $val[$pid_key];
                if ($rootid == $parentId) {
                    $tree[] = & $data[$key];
                }
                else {
                    if (isset($_temp[$parentId])) {
                        $parent = & $_temp[$parentId];
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
     * @param bool $left_is_order 是否按照左边界做了升序排序
     * @param integer|null $root_id 根节点id，默认第一个节点就是根节点
     * @param string|null $left_key 左边界键名，默认为left
     * @param string|null $right_key 右边界键名，默认为right
     * @return array 已编排好了的无序树，可以用作本类树图方法的数据源
     */
    public function orderedToUnordered($data, $left_is_order = true, $root_id = null, $left_key = null, $right_key = null) {
        $left_key and $this->_left_key = $left_key;
        $right_key and $this->_right_key = $right_key;
        if ($root_id)
            $data = $this->_node_filter_by_id($data, $root_id);
        if (empty($data))
            return $data;
        if (!$left_is_order)
            $data = $this->_order_left($data);
        $parent_key = null;
        $right_parent_keys = array();
        $i = 0;
        foreach ($data as $key => $node) {
            if (is_null($parent_key)) {
                $data[$key][$this->_pid_key] = -1;
            }
            elseif ($data[$parent_key][$this->_left_key] + 1 == $node[$this->_left_key]) {
                $data[$key][$this->_pid_key] = $data[$parent_key][$this->_id_key];
            }
            elseif (isset($right_parent_keys[$node[$this->_left_key] - 1])) {
                $parent_key = $right_parent_keys[$node[$this->_left_key] - 1];
                $data[$key][$this->_pid_key] = $data[$parent_key][$this->_id_key];
            }
            else {
                $data[$key][$this->_pid_key] = -1;
                $parent_key = null;
            }
            $data[$key][$this->_id_key] = $i++;
            $right_parent_keys[$node[$this->_right_key]] = $parent_key;
            $parent_key = $key;
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
     * @param bool $left_is_order 是否按照左边界做了升序排序
     * @param integer|null $root_left 根节点左边界值，默认左边界值最小的（排序后，第一个节点就是根节点）
     * @param string|null $left_key 左边界键名，默认为left
     * @param string|null $right_key 右边界键名，默认为right
     * @return array 已编排好了的无序树结构，非线性的，可以直接转为json，或用递归方式列遍树
     */
    public function orderedToTree($data, $children = 'children', $left_is_order = true, $root_left = null, $left_key = null, $right_key = null) {
        $left_key and $this->_left_key = $left_key;
        $right_key and $this->_right_key = $right_key;
        if ($root_left)
            $data = $this->_node_filter_by_left($data, $root_left);
        if (empty($data))
            return $data;
        if (!$left_is_order)
            $data = $this->_order_left($data);
        $parent_key = null;
        $right_parent_keys = array();
        $tree = array();
        foreach ($data as $key => $node) {
            if (is_null($parent_key)) {
                $tree[] = & $data[$key];
            }
            elseif ($data[$parent_key][$this->_left_key] + 1 == $node[$this->_left_key]) {
                $data[$parent_key][$children][] = & $data[$key];
            }
            elseif (isset($right_parent_keys[$node[$this->_left_key] - 1])) {
                $parent_key = $right_parent_keys[$node[$this->_left_key] - 1];
                $data[$parent_key][$children][] = & $data[$key];
            }
            else {
                $tree[] = & $data[$key];
                $parent_key = null;
            }
            $right_parent_keys[$node[$this->_right_key]] = $parent_key;
            $parent_key = $key;
        }
        return $tree;
    }

    private function _order_left($data) {
        $temp_data = array();
        foreach ($data as $node) {
            $temp_data[$node[$this->_left_key]] = $node;
        }
        ksort($temp_data);
        return $temp_data;
    }

    private function _node_filter_by_id($data, $root_id) {
        $range_node = array();
        foreach ($data as $node) {
            if ($node[$this->_id_key] == $root_id) {
                $range_node[] = $node;
                break;
            }
        }
        if (empty($range_node))
            return $range_node;
        $root_left = $range_node[0][$this->_left_key];
        $root_right = $range_node[0][$this->_right_key];
        foreach ($data as $node) {
            if ($node[$this->_left_key] > $root_left && $node[$this->_right_key] < $root_right) {
                $range_node[] = $node;
            }
        }
        return $range_node;
    }

    private function _node_filter_by_left($data, $root_left) {
        $range_node = array();
        foreach ($data as $node) {
            if ($node[$this->_left_key] == $root_left) {
                $range_node[] = $node;
                break;
            }
        }
        if (empty($range_node))
            return $range_node;
        $root_right = $range_node[0][$this->_right_key];
        foreach ($data as $node) {
            if ($node[$this->_left_key] > $root_left && $node[$this->_right_key] < $root_right) {
                $range_node[] = $node;
            }
        }
        return $range_node;
    }
}