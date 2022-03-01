<?php
include_once "Util.php";

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class LogicShuffler extends NodeVisitorAbstract
{
    // 在afterTraverse时才触发
    private array $stmts;
    private array $labels;
    public function __construct()
    {
        $this->stmts = array();
        $this->labels = array();
    }


    public function afterTraverse(array $nodes)
    {
        $len = count($nodes);
        $order = range(0, $len-1); // 0~n-1的数组，对后续stmt进行排序，表示现在取出来的是原序列的哪个值
        shuffle($order);
        for($i = 0; $i<$len; $i++){
            $label = randomString();
            $this->labels[] = $label;
        }
        // 开局先goto到第一句对应的label
        $this->stmts[] = new Node\Stmt\Goto_($this->labels[0]);
        for($i = 0; $i<$len; $i++){
            // 尝试在这里进行低强度破坏
            $r = rand(0, 5);
            switch ($r){
                case 0:
                case 1:
                    // 增大垃圾label的概率
                    // 加一个不存在的label，无影响，随机加在前面或者后面
                    if(rand(0, 1)) {
                        $this->stmts[] = new Node\Stmt\Label($this->labels[$order[$i]]);
                        $this->stmts[] = new Node\Stmt\Label(randomString());
                    }else{
                        $this->stmts[] = new Node\Stmt\Label(randomString());
                        $this->stmts[] = new Node\Stmt\Label($this->labels[$order[$i]]);
                    }
                    break;
                // 假if先if再执行语句，真if先执行语句再if
                case 2:
                    $this->stmts[] = new Node\Stmt\Label($this->labels[$order[$i]]);
                    // 当get内容和随机字符串相等时goto一个之前的label，往回跳
                    $get = new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("_GET"), new Node\Scalar\String_(randomString()));
                    $cond = new Node\Expr\BinaryOp\Equal($get, new Node\Scalar\String_(randomString()));
                    $sub_node = array();
                    $sub_node['stmts'][] = new Node\Stmt\Goto_($this->labels[rand(0, $order[$i])]);
                    $this->stmts[] = new Node\Stmt\If_($cond, $sub_node);
                    break;
                case 3:
                    // 写一个垃圾if，不等于时跳转到正确值(正常情况就是不等于来着)
                    $this->stmts[] = new Node\Stmt\Label($this->labels[$order[$i]]);
                    // 运气不好直接是最后一轮的话就不这么混淆了，直接拉倒
                    // 但label还是要写，主要是因为case0中第一个label不一定有用导致每个case开头需要加一行而不能成为公用代码
                    if ($order[$i] === $len-1) {
                        break;
                    }
                    $this->stmts[] = $nodes[$order[$i]];
                    // 当get内容和随机字符串不一致时goto一个之前的label
                    $get = new Node\Expr\ArrayDimFetch(new Node\Expr\Variable("_GET"), new Node\Scalar\String_(randomString()));
                    $cond = new Node\Expr\BinaryOp\NotIdentical($get, new Node\Scalar\String_(randomString()));
                    $sub_node = array();
                    $sub_node['stmts'][] = new Node\Stmt\Goto_($this->labels[$order[$i]+1]);
                    $this->stmts[] = new Node\Stmt\If_($cond, $sub_node);
                    $this->stmts[] = new Node\Stmt\Goto_($this->labels[rand(0, $order[$i])]);
                    // 跳过后面的通用步骤，感觉整个flag标识然后每轮判断可能还不如使用垃圾goto语句
                    goto end;
                default:
                    // 不做混淆的情况
                    $this->stmts[] = new Node\Stmt\Label($this->labels[$order[$i]]);
            }

            $this->stmts[] = $nodes[$order[$i]];
            if ($order[$i] === $len-1) { // 最后一个stmt,exit和die都属于语言结构而不是函数，所以用return中断吧
                $this->stmts[] = new Node\Stmt\Return_();
            }
            else {
                $this->stmts[] = new Node\Stmt\Goto_($this->labels[$order[$i]+1]);
            }
            // 跳过上面这段公共代码,case3使用,将裸goto变成了if条件下的goto
            end:
        }
        return $this->stmts;
    }
}