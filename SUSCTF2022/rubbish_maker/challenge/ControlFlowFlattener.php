<?php

require_once 'Util.php';

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ControlFlowFlattener extends NodeVisitorAbstract
{
    // 把所有二元操作，字符串和函数调用收集起来，打包到一个表函数里面进行动态调用，做到所谓的控制流平坦化
    // 理论上来说一个表里面打一万个东西应该会导致查表效率低下，but who care?多打几个表又要多写代码，开始偷懒
    // 字符串和函数都收集到字符串表里面去，函数调用就返回函数名就行
    // 字符串混淆暂时不知道咋做，先用字符串混淆类处理，但函数名是用字符串形式收集的，在这之后又不会有混淆了呜呜

    /** @var array $string_table 收集所有字符串的二维数组，key为用于混淆的键，值为字符串值 */
    private array $string_table;
    /** @var array $op_table 类似string table，生成所有二元操作，但key为二元操作名，值为用于混淆的key */
    private array $op_table;
    /** @var array $seed_table key===param^seed进行判断，用于记录seed */
    private array $seed_table;
    /** @var array $table_name 记录当前作用域下表函数的名字 */
    private array $table_name;
    /** @var int $depth 考虑定义域问题，每个定义域需要独立操作，故上述变量均为数组，depth表示定义域深度 */
    private int $depth;

    public function __construct()
    {
        $this->string_table[] = [];
        $this->op_table[] = [];
        $this->table_name[] = randomString();
        $this->seed_table[] = randomString();
        $this->depth = 0;
    }

    /** 对二元操作打包成一个闭包函数返回
     * 一开始是想把操作放到表中进行再返回出去的，但是这样子表函数就需要对更多的参数进行处理，开始偷懒
     */
    private function getBinOpClosure($type): Node\Expr\Closure
    {

        $param1 = randomString();
        $param2 = randomString();
        $params = array(new Node\Param(new Node\Expr\Variable($param1)), new Node\Param(new Node\Expr\Variable($param2)));
        $classname = "PhpParser\\Node\\" . str_replace("_", "\\", $type);
        // 动态生成二元操作
        $return = new Node\Stmt\Return_(new $classname(new Node\Expr\Variable($param1), new Node\Expr\Variable($param2)));

        $closure = new Node\Expr\Closure(["params" => $params, "stmts" => array($return)]);
        return $closure;
    }

    /**
     * @return Node\Stmt\Function_
     * 按之前收集的数据产生一个表，记录二元操作闭包和字符串
     */
    public function getFlattener(): Node\Stmt\Function_
    {
        // 函数变量名
        $param = randomString();
        // seed变量名和seed变量值
        $seed_name = randomString();
        $seed_value = $this->seed_table[$this->depth];
        // 用于结束的标签
        $end = randomString();
        $check_labels = array();
        $return_labels = array();

        foreach ($this->op_table[$this->depth] as $name => $value) {
            $check_labels[$name] = randomString();
            $return_labels[$name] = randomString();
        }
        foreach ($this->string_table[$this->depth] as $name => $value) {
            $check_labels[$name] = randomString();
            $return_labels[$name] = randomString();
        }
        // 打乱一下顺序
        $check_labels = shuffle_assoc($check_labels);

        // 手写入口部分，先把seed整出来
        $stmts = array();
        $begin = randomString();
        $stmts[] = new Node\Stmt\Goto_($begin);
        $stmts[] = new Node\Stmt\Label($begin);
        $stmts[] = new Node\Stmt\Expression(new Node\Expr\Assign(new Node\Expr\Variable($seed_name), new Node\Scalar\String_($seed_value)));

        // 先存下来当前key，数组永远指向下一个key
        $current_key = key($check_labels);
        $stmts[] = new Node\Stmt\Goto_($check_labels[$current_key]);
        next($check_labels);
        $next_key = key($check_labels);
        $check_labels_len = count($check_labels);

        // 把所有的表打出来
        for ($i = 0; $i < $check_labels_len; $i++) {
            $stmts[] = new Node\Stmt\Label($check_labels[$current_key]);
            // 两种情况，一种if里面正确的路，一种if外面正确的路，错误情况下只是前往下一个语句进行判断
            if (rand(0, 1)) {
                // 只有最后一个语句会前往end处
                if($i !== $check_labels_len - 1) {
                    $if_stmt = new Node\Stmt\Goto_($check_labels[$next_key]);
                }else{
                    $if_stmt = new Node\Stmt\Goto_($end);
                }
                // key !== param^seed,由于op table和string table的键值关系不一样，还需要额外判断
                if(array_key_exists($current_key, $this->op_table[$this->depth])){
                    $cond = new Node\Expr\BinaryOp\NotIdentical(new Node\Scalar\String_($this->op_table[$this->depth][$current_key]), new Node\Expr\BinaryOp\BitwiseXor(new Node\Expr\Variable($param), new Node\Expr\Variable($seed_name)));
                }else {
                    $cond = new Node\Expr\BinaryOp\NotIdentical(new Node\Scalar\String_($current_key), new Node\Expr\BinaryOp\BitwiseXor(new Node\Expr\Variable($param), new Node\Expr\Variable($seed_name)));
                }
                $stmts[] = new Node\Stmt\If_($cond, array('stmts'=>array($if_stmt)));
                // 还得有一套label用来返回数据。。。。
                $stmts[] = new Node\Stmt\Goto_($return_labels[$current_key]);
            } else {
                $if_stmt = new Node\Stmt\Goto_($return_labels[$current_key]);
                // key ===  param^seed
                if(array_key_exists($current_key, $this->op_table[$this->depth])){
                    $cond = new Node\Expr\BinaryOp\Identical(new Node\Scalar\String_($this->op_table[$this->depth][$current_key]), new Node\Expr\BinaryOp\BitwiseXor(new Node\Expr\Variable($param), new Node\Expr\Variable($seed_name)));
                }else {
                    $cond = new Node\Expr\BinaryOp\Identical(new Node\Scalar\String_($current_key), new Node\Expr\BinaryOp\BitwiseXor(new Node\Expr\Variable($param), new Node\Expr\Variable($seed_name)));
                }
                $stmts[] = new Node\Stmt\If_($cond, array('stmts'=>array($if_stmt)));
                if($i !== $check_labels_len - 1) {
                    $stmts[] = new Node\Stmt\Goto_($check_labels[$next_key]);
                }else{
                    $stmts[] = new Node\Stmt\Goto_($end);
                    // 到头了跑路
                    break;
                }
            }
            // 维护状态
            $current_key = $next_key;
            next($check_labels);
            $next_key = key($check_labels);
        }

        // 把return的数据打一下表
        foreach ($return_labels as $name => $return_label) {
            $stmts[] = new Node\Stmt\Label($return_label);
            if (array_key_exists($name, $this->op_table[$this->depth])){
                // binop要直接返回值的话还要进行额外的参数操作，还是返回一个闭包
                $stmts[] = new Node\Stmt\Return_($this->getBinOpClosure($name));
            }else{
                $stmts[] = new Node\Stmt\Return_(new Node\Scalar\String_($this->string_table[$this->depth][$name]));
            }
        }
        // end label
        // exit不是函数，所以用return进行结束。。。
        $stmts[] = new Node\Stmt\Label($end);
        $stmts[] = new Node\Stmt\Return_();

        // 想想办法把上面这堆stmt打乱
        // range包括最后一个数，即产生的数组长度为$check_labels_len+1，但开始时有三句用于初始化seed的语句，故此处不-1
        $check_labels_order = range(0, $check_labels_len);
        shuffle($check_labels_order);
        // 结束的exit也是两句，把那句也算在一起处理了，故不-1
        $return_labels_len = count($return_labels);
        $return_labels_order = range(0, $return_labels_len);
        shuffle($return_labels_order);

        // 起步goto先存着
        $shuffled_stmts = array($stmts[0]);
        $i = $j = 0;
        // 两数组长度是len+1
        while ($i<$check_labels_len+1 || $j<$return_labels_len+1){
            if(rand(0, 1) && $i != $check_labels_len+1){
                // +1是加的初始goto语句的偏移
                $shuffled_stmts[] = $stmts[3*$check_labels_order[$i]+1];
                $shuffled_stmts[] = $stmts[3*$check_labels_order[$i]+2];
                $shuffled_stmts[] = $stmts[3*$check_labels_order[$i]+3];
                $i++;
            }elseif($j != $return_labels_len+1){
                $shuffled_stmts[] = $stmts[3*($check_labels_len+1)+2*$return_labels_order[$j]+1];
                $shuffled_stmts[] = $stmts[3*($check_labels_len+1)+2*$return_labels_order[$j]+2];
                $j++;
            }
        }
        // 把stmt打包到函数
        $func = new Node\Stmt\Function_($this->table_name[$this->depth], array("stmts"=>$shuffled_stmts, "params"=> array(new Node\Param(new Node\Expr\Variable($param)))));
        return $func;
    }

    public function initOpTable()
    {
        // 先把表打出来，初始化，实际代码再结束的时候再插入
        // 扫描的binaryOp类并去除当前目录和跳目录。。。但总觉得这么写好愚蠢？
        $binary_ops = array_diff(scandir("vendor/nikic/php-parser/lib/PhpParser/Node/Expr/BinaryOp"), array('..', '.'));
        foreach ($binary_ops as $op) {
            $name = randomString();
            $op = "Expr_BinaryOp_" . ucfirst(substr($op, 0, -4));
            $this->op_table[$this->depth][$op] = $name;
        }


    }

    public function beforeTraverse(array $nodes)
    {
        $this->initOpTable();
    }

    public function enterNode(Node $node)
    {
        // 维护一个栈的关系，进函数定义的时候就压一层栈
        if ($node instanceof Node\Stmt\Function_) {
            $this->depth++;
            $this->string_table[$this->depth] = [];
            $this->initOpTable();
            $this->table_name[$this->depth] = randomString();
            $this->seed_table[$this->depth] = randomString();
        }
    }

    public function leaveNode(Node $node)
    {
        // $node->name instanceof Expr_Variable时是动态调用，只考虑静态调用
        if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name) {
            $key = randomString();
            // 函数调用就通过收集函数名丢进表里然后进行动态调用
            // 控制流平坦化是最后做的，这会导致$node->name被收集后没有进一步混淆，也就是说调用了什么函数都看得见
            $this->string_table[$this->depth][$key] = $node->name;
            $fetch_table = new Node\Expr\FuncCall(new Node\Name($this->table_name[$this->depth]), array(new Node\Arg(new Node\Scalar\String_($key^$this->seed_table[$this->depth]))));
            return new Node\Expr\FuncCall($fetch_table, $node->args);
        } elseif ($node instanceof Node\Scalar\String_) {
            // string收集,并计算param值
            $key = randomString();
            $this->string_table[$this->depth][$key] = $node->value;
            // key === param^seed
            // 函数调用和字符串的区别就在于函数调用在查回来结果后需要再进行一次调用
            return new Node\Expr\FuncCall(new Node\Name($this->table_name[$this->depth]), array(new Node\Arg(new Node\Scalar\String_($key^$this->seed_table[$this->depth]))));
        } elseif ($node instanceof Node\Expr\BinaryOp) {
            // $a??1这种操作打包成函数之后直接使用未定义变量传入函数也不会炸，还有这种好事?
            // 二元操作打包加入
            $node_type = $node->getType();
            $key = $this->op_table[$this->depth][$node_type];
            // 先数组名再key
            $name = new Node\Expr\FuncCall(new Node\Name($this->table_name[$this->depth]), array(new Node\Arg(new Node\Scalar\String_($key^$this->seed_table[$this->depth]))));
            $args = array($node->left, $node->right);
            return new Node\Expr\FuncCall($name, $args);

        } elseif ($node instanceof Node\Stmt\Function_) {
            // 退出函数定义时，把收集起来的func,binop,string打一个表
            $table_expr = $this->getFlattener();
            array_unshift($node->stmts, $table_expr);

            // 退栈
            array_pop($this->string_table);
            array_pop($this->table_name);
            array_pop($this->seed_table);
            $this->depth--;
        }
    }

    public function afterTraverse(array $nodes)
    {
        // 把全局的字符串和函数也打一个表
        $table_expr = $this->getFlattener();
        array_unshift($nodes, $table_expr);
        return $nodes;
    }


}