<?php
//设计容器类 容器类装实例或者提供实例的回调函数
class Container
{
    //用于装提供实例的回调函数 真正的容器还会装实例等其他内容
    //从而实现单例等高级功能
    protected $bindings = [];

    //绑定接口和生成相应实例的回调函数
    public function bind($abstract, $concrete = null, $shared = false)
    {
        if (!$concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    //默认生成实例的回调函数
    protected function getClosure($abstract, $concrete)
    {
        //echo "abstract:{$abstract} concrete:{$concrete}".PHP_EOL;
        return function ($c) use ($abstract, $concrete) {
            $method = ($abstract == $concrete) ? 'build' : 'make';
            return $c->$method($concrete);
        };
    }

    //生成实例对象 首先解决接口和要实例化类之间的依赖关系
    public function make($abstract)
    {
        $concrete = $this->getConcrete($abstract);
        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete);
        } else {
            $object = $this->make($concrete);
        }

        return $object;
    }

    protected function isBuildable($concrete, $abstract)
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    protected function getConcrete($abstract)
    {
        if (!isset($this->bindings[$abstract])) {
            return $abstract;
        }

        return $this->bindings[$abstract]['concrete'];
    }

    //实例化对象
    public function build($concrete)
    {
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }

        $reflector = new ReflectionClass($concrete);
        #检查类是否可实例化
        if (!$reflector->isInstantiable()) {
            echo $message = "Target [$concrete] is not instantiable.";
        }

        #获取类的构造函数
        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            return new $concrete;
        }

        #获取构造方法的参数
        $dependencies = $constructor->getParameters();
        $instances = $this->getDependencies($dependencies);
        return $reflector->newInstanceArgs($instances);
    }

    //解决通过反射机制实例化对象时的依赖
    protected function getDependencies($parameters)
    {
        $dependencies = [];
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();
            if (is_null($dependency)) {
                $dependencies[] = NULL;
            } else {
                $dependencies[] = $this->resolveClass($parameter);
            }
        }

        return (array)$dependencies;
    }

    protected function resolveClass(ReflectionParameter $parameter){
        return $this->make($parameter->getClass()->name);
    }



}
interface Visit{
    public function go();
}

class Leg implements Visit{
    public function go($a=1,$b=2){
        echo "walt to Tibet!!!";
    }
}
class Train implements Visit{
    public function go(){
        echo "train to Tibet!!!".PHP_EOL;
    }

}

class Traveller{
    protected $trafficTool;

    public function __construct(Visit $trafficTool)
    {
        $this->trafficTool = $trafficTool;
    }

    public function visitTibet(){
        $this->trafficTool->go();
    }
}

//实例化IoC容器
$app = new Container();

//容器填充
$app->bind("Visit","Train");
$app->bind("traveller","Traveller");

//通过容器实现依赖注入 完成类的实例化
$tra = $app->make("traveller");
$tra->visitTibet();

?>