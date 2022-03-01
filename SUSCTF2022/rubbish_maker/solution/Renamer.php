<?php


use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;

class Renamer extends NodeVisitorAbstract
{
    private array $var_table;
    private array $label_table;
    private array $func_table;
    private int $depth;

    public function __construct()
    {
        $this->var_table[] = array();
        $this->label_table[] = array();
        $this->func_table = array();
        $this->depth = 0;
    }

    public function enterNode(Node $node)
    {
        if($node instanceof Node\Stmt\Function_){
            $this->var_table[] = array();
            $this->label_table[] = array();
            $this->depth++;
        }
    }

    public function leaveNode(Node $node)
    {
        if($node instanceof Node\Expr\Variable){
            if(array_key_exists($node->name, $this->var_table[$this->depth])){
                $node->name = $this->var_table[$this->depth][$node->name];
            }elseif($node->name[0] === "_"){
                // 别把超全局变量给干了
                return;
            }
            else{
                $name = "var".$this->depth."_".count($this->var_table[$this->depth]);
                $this->var_table[$this->depth][$node->name] = $name;
                $node->name = $name;
            }
            return $node;
        }elseif ($node instanceof Node\Stmt\Label || $node instanceof Node\Stmt\Goto_){
            if(array_key_exists($node->name->name, $this->label_table[$this->depth])) {
                $node->name->name = $this->label_table[$this->depth][$node->name->name];
            }else{
                $name = "label".$this->depth."_".count($this->label_table[$this->depth]);
                $this->label_table[$this->depth][$node->name->name] = $name;
                $node->name->name = $name;
            }
        }elseif ($node instanceof Node\Stmt\Function_){
            array_pop($this->var_table);
            array_pop($this->label_table);
            $this->depth--;
            // 函数大概都是全局的吧，暂不考虑类和闭包之类的东西
            if($node->name instanceof Node\Identifier) {
                $name = "func" . count($this->func_table);
                $this->func_table[$node->name->name] = $name;
                $node->name->name = $name;
                return $node;
            }
        }
        // 非动态调用
        elseif($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name){
            if(array_key_exists($node->name->parts[0], $this->func_table)){
                $node->name->parts[0] = $this->func_table[$node->name->parts[0]];
                return $node;
            }
        }
    }
}