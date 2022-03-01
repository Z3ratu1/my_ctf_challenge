<?php
require_once "Util.php";
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class NameObfuscator extends NodeVisitorAbstract
{
    // 用来修改变量名的类，理论上来说只修改一个文件里的变量名是不会有问题的吧？和CFF一样维护一个栈来对不同作用域的变量进行修改
    // 最先应用的visitor

    // 同样二维数组
    private array $name_table;
    private int $depth;
    public function __construct()
    {
        $this->name_table[] = [];
        $this->depth = 0;
    }

    public function enterNode(Node $node)
    {
        if($node instanceof Node\Stmt\Function_){
            $this->depth++;
            $this->name_table[$this->depth] = [];
        }
    }

    public function leaveNode(Node $node)
    {
        if($node instanceof Node\Expr\Variable){
            // 处理一下下划线开头的超全局变量，把这个搅合了就不用跑了
            if($node->name[0] === '_'){
                return $node;
            }
            if(array_key_exists($node->name, $this->name_table[$this->depth])){
                return new Node\Expr\Variable($this->name_table[$this->depth][$node->name]);
            }else{
                $name = randomString();
                $this->name_table[$this->depth][$node->name] = $name;
                return new Node\Expr\Variable($name);
            }
        }elseif ($node instanceof Node\Stmt\Function_){
            $this->depth--;
            array_pop($this->name_table);
        }
    }

}