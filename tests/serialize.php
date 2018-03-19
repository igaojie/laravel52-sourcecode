<?php

class Person {
    // 下面是人的成员属性
    var $name;  // 人的名子
    var $sex;   // 人的性别
    var $age;   // 人的年龄

    // 定义一个构造方法参数为属性姓名$name、性别$sex和年龄$age进行赋值
    function __construct($name = "", $sex = "", $age = "") {
        $this->name = $name;
        $this->sex = $sex;
        $this->age = $age;
    }

    // 这个人可以说话的方法, 说出自己的属性
    function say() {
        echo "我的名子叫：" . $this->name . " 性别：" . $this->sex . " 我的年龄是：" . $this->age . "<br>";
    }
}

$p1 = new Person("张三", "男", 20);
$p1_string = serialize($p1);    // 把一个对象串行化，返一个字符串
echo $p1_string . PHP_EOL;     // 串行化的字符串我们通常不去解析
$p2 = unserialize($p1_string);  // 把一个串行化的字符串反串行化形成对象$p2
$p2->say();
?>