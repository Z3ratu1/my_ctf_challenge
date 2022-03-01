<?php

use PhpParser\NodeDumper;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;

class ControlFlowRestorer extends NodeVisitorAbstract
{
    // 还原控制流平坦化函数
    private array $restored_stmts;
    private string $key;
    private string $table_func_name;

    public function __construct()
    {
        $this->restored_stmts = array();
        $this->key = "";
        $this->table_func_name = "";
    }

    public function leaveNode(Node $node)
    {
        // 匿名函数的类型是Expr_Closure,应该不会和这个冲突?
        if ($node instanceof Node\Stmt\Function_) {
            $label_index = array();
            $this->table_func_name = $node->name->name;
            $stmts = $node->stmts;
            foreach ($stmts as $index => $stmt) {
                if ($stmt instanceof Node\Stmt\Label) {
                    $label_index[$stmt->name->name] = $index;
                }
            }
            assert($stmts[0] instanceof Node\Stmt\Goto_);
            $assign_expr = $stmts[$label_index[$stmts[0]->name->name] + 1]->expr;
            assert($assign_expr instanceof Node\Expr\Assign);
            $this->key = $assign_expr->expr->value;
            foreach ($label_index as $name => $index) {
                if ($stmts[$index + 1] instanceof Node\Stmt\If_) {
                    $if_stmt = $stmts[$index + 1];
                    // === 走if内的选项
                    if ($if_stmt->cond instanceof Node\Expr\BinaryOp\Identical) {
                        assert($if_stmt->stmts[0] instanceof Node\Stmt\Goto_);
                        $return_stmt = $stmts[$label_index[$if_stmt->stmts[0]->name->name] + 1];
                        assert($return_stmt instanceof Node\Stmt\Return_);
                        $arg = $this->key ^ $if_stmt->cond->left->value;
                        $this->restored_stmts[$arg] = $return_stmt->expr;
                    } elseif ($if_stmt->cond instanceof Node\Expr\BinaryOp\NotIdentical) {
                        // !== 走if外的选项
                        assert($stmts[$index + 2] instanceof Node\Stmt\Goto_);
                        $return_stmt = $stmts[$label_index[$stmts[$index + 2]->name->name] + 1];
                        assert($return_stmt instanceof Node\Stmt\Return_);
                        $arg = $this->key ^ $if_stmt->cond->left->value;
                        $this->restored_stmts[$arg] = $return_stmt->expr;
                    }
                }
            }
            return NodeTraverser::REMOVE_NODE;
        }
        // TODO 这段的处理还有一堆bug....
        if ($node instanceof Node\Expr\FuncCall) {
            // 直接调用
            if ($node->name instanceof Node\Name) {
                // 弱等于做toString之后再比较
                if ($node->name == $this->table_func_name) {
                    assert(count($node->args) === 1 && $node->args[0]->value instanceof Node\Scalar\String_);
                    assert(array_key_exists($node->args[0]->value->value, $this->restored_stmts));
                    return $this->restored_stmts[$node->args[0]->value->value];

                }
            } elseif ($node->name instanceof Node\Expr\Closure) {
                // 由于leaveNode先处理里层的，导致处理到此时时node->name已经由FuncCall变为了Closure，故直接做Closure进行处理
                if(count($node->name->params) === 2 && $node->name->stmts[0] instanceof Node\Stmt\Return_) {
                    $classname = "PhpParser\\Node\\" . str_replace("_", "\\", $node->name->stmts[0]->expr->getType());
                    $bin_expr = new $classname($node->args[0]->value, $node->args[1]->value);
                    return $bin_expr;
                }
                // 字符串形式调用
            } elseif ($node->name instanceof Node\Scalar\String_) {
                return new Node\Expr\FuncCall(new Node\Name($node->name->value), $node->args);
            }

        }
    }

    public function afterTraverse(array $nodes)
    {
        // 把goto顺一下
        $label_index = array();
        // 记一下出现过的label
        $prev_label = array();
        $stmts = array();
        foreach ($nodes as $index => $node) {
            if ($node instanceof Node\Stmt\Label) {
                $label_index[$node->name->name] = $index;
            }
        }
        $index = 0;
        assert($nodes[$index] instanceof Node\Stmt\Goto_);
        $prev_label[$nodes[$index]->name->name] = 1;
        $index = $label_index[$nodes[$index]->name->name];
        $return_flag = false;
        while (!$return_flag) {
            assert($nodes[$index] instanceof Node\Stmt\Label);
            $index++;
            while (!($nodes[$index] instanceof Node\Stmt\Goto_)) {
                if ($nodes[$index] instanceof Node\Stmt\If_ and $nodes[$index]->stmts[0] instanceof Node\Stmt\Goto_) {
                    // 往回跳的都是假的
                    if (array_key_exists($nodes[$index]->stmts[0]->name->name, $prev_label)) {
                        $index++;
                    } else {
                        $prev_label[$nodes[$index]->stmts[0]->name->name] = 1;
                        $index = $label_index[$nodes[$index]->stmts[0]->name->name];
                        break;
                    }
                } elseif ($nodes[$index] instanceof Node\Stmt\Return_) {
                    $return_flag = true;
                    break;
                } else {
                    $stmts[] = $nodes[$index];
                    $index++;
                }
            }
            if ($nodes[$index] instanceof Node\Stmt\Goto_) {
                $prev_label[$nodes[$index]->name->name] = 1;
                $index = $label_index[$nodes[$index]->name->name];
            }
        }

        return $stmts;
    }
}