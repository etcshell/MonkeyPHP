<?php
namespace Library;

/**
 * Cart
 * 购物车类
 * @package Library
 */
class Cart {
    private $_cookier; //wy_cookie对象
    private $_cart_name = 'wy_shopping_cart'; //购物车名称
    /**
     * 购物车
     * @param Cookie $cookier //wy_cookie对象
     * @param string $cart_name //购物车名称
     */
    public function __construct(Cookie $cookier, $cart_name = NULL) {
        $this->_cookier = $cookier;
        if (!is_null($cart_name)) {
            $this->_cart_name = trim($cart_name);
        }
    }

    /**
     * 显示购物车内容
     * @return array
     * @example
     * 返回数据类型为:array(array(商品ID, 商品名称, 商品数量, 商品单价, array(其实信息)), array(...));
     */
    public function readCart() {
        //从购物车cookie中读取数据
        $data = $this->_cookier->get($this->_cart_name);
        if (!$data) {
            return false;
        }
        return $data;
    }

    /**
     * 添加商品
     * @param integer $id 商品ID(唯一)
     * @param string $product 商品名称
     * @param integer $num 商品数量
     * @param float $price 商品价格(单价)
     * @param array $options 商品其它属性
     * @return boolean
     */
    public function add($id, $product = null, $num = 1, $price = null, $options = array()) {
        if (!$id) {
            return false;
        }
        $num = (int)$num;
        $data = $this->readCart();
        //当购物车中没有商品记录时
        if (!$data) {
            $data = array($id => array($id, $product, $num, $price, $options));
        }
        else {
            //当购物车中已存在所要添加的商品时,只进行库存更改操作
            if (isset($data[$id])) {
                $data[$id][2] += $num;
            }
            else {
                $data[$id] = array($id, $product, $num, $price, $options);
            }
        }
        $this->_cookier->set($this->_cart_name, $data);
        return true;
    }

    /**
     * 将N多商品进行批处理添加到购物车
     * @param array $data 所要添加的购物车内容
     * @return boolean
     * @example
     * $data = array(
     *   array(101, 'ak47', 25, 10000, array('maker'=>'china')),
     *   array(103, 'mp5', 10, 2300, array('from'=>'usa')),
     *   array(200, 'F-22', 5, 20000000, array('from'=>'usa')),
     * );
     * $cart->insert($data);
     */
    public function insert($data) {
        //参数分析
        if (!$data || !is_array($data)) {
            return false;
        }
        $cart_data = $this->readCart();
        //当购物车中没有商品记录时
        if (!$cart_data) {
            $cart_data = array();
        }
        foreach ($data as $lines) {
            if (!is_array($lines) || empty($lines[0])) {
                continue;
            }
            if (isset($cart_data[$lines[0]])) {
                $cart_data[$lines[0]][2] += $lines[2];
            }
            else {
                $cart_data[$lines[0]] = array($lines[0], $lines[1], $lines[2], $lines[3], $lines[4]);
            }
        }
        $this->_cookier->set($this->_cart_name, $cart_data);
        return true;
    }

    /**
     * 修改购物车内容
     * 当购物车中已有要更改信息的商品时,将原有的商品信息替换掉,
     * 当购物车中没有该商品时将不更改购物车信息,而是直接返回false
     * @param array $data 将要修改后的内容,注:本参数为一维数组
     * @return boolean
     * @example
     * $data = array(101, 'ak47', 3, 1200, array('maker'=>'usa'));
     * $cart->update($data);
     * 注:参数$data也可以用于 $cart->add($data),区别在于
     * 用于add()时,如果购物车中已有ID为101的商品时,将库存自动增加3
     * 用于update()时,如果购物车中已有ID为101的商品时,将信息更改为当前$data的数据(更改的不只是库存)
     */
    public function update($data) {
        if (!is_array($data) || empty($data[0])) {
            return false;
        }
        $cart_data = $this->readCart();
        //判断将要更改的商品数据是否在购物车中存在
        if (!isset($cart_data[$data[0]])) {
            return false;
        }
        $cart_data[$data[0]] = array($data[0], $data[1], $data[2], $data[3], $data[4]);
        $this->_cookier->set($this->_cart_name, $cart_data);
        return true;
    }

    /**
     * 删除购物车中的某商品
     * 注:当购物车中没有商品ID为$key的商品时,同样返回true
     * @param integer $key 商品id(唯一标识)
     * @return boolean
     */
    public function delete($key) {
        if (!$key) {
            return false;
        }
        $cart_data = $this->readCart();
        if (!$cart_data) {
            return true;
        }
        if (isset($cart_data[$key])) {
            unset($cart_data[$key]);
            $this->_cookier->set($this->_cart_name, $cart_data);
        }
        return true;
    }

    /**
     * 清空购物车的内容
     * @return void
     */
    public function clear() {
        $this->_cookier->delete($this->_cart_name);
    }

    /**
     * 获取购物车内的总商种数(商品种类)
     *
     * @return integer
     */
    public function getTotalItems() {
        $cart_data = $this->readCart();
        if (!$cart_data) {
            $items = 0;
        }
        else {
            $items = sizeof($cart_data);
        }
        return $items;
    }

    /**
     * 获取购物车商品总数
     * @return integer
     */
    public function getTotalNum() {
        $cart_data = $this->readCart();
        $total_num = 0;
        if ($cart_data) {
            foreach ($cart_data as $lines) {
                $total_num += $cart_data[$lines[0]][2];
            }
        }
        return $total_num;
    }

    /**
     * 获取购物车总金额
     * @return integer
     */
    public function getTotalPrice() {
        $cart_data = $this->readCart();
        $total_price = 0;
        //当购物车中有商品记录时
        if ($cart_data) {
            foreach ($cart_data as $lines) {
                $total_price += $cart_data[$lines[0]][3];
            }
        }
        return $total_price;
    }

    /**
     * 购物车中是存在$key的商品
     * @param mixed $key 商品id
     * @return boolean
     */
    public function issetInCart($key) {
        if (!$key) {
            return false;
        }
        $cart_data = $this->readCart();
        if ($cart_data && isset($cart_data[$key])) {
            return true;
        }
        return false;
    }

    /**
     * 设置购物车名
     * @param string $cart_name 购物车名
     * @return $this
     */
    public function setName($cart_name) {
        if (!$cart_name) {
            return false;
        }
        $this->_cart_name = trim($cart_name);
        return $this;
    }

}