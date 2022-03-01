<?php

require_once 'Util.php';

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ScalarObfuscator extends NodeVisitorAbstract
{
    // 混淆常量字符串和数字的类

    // 字面量数字混淆
    private function obfuscateInt($node)
    {
        assert($node instanceof Node\Scalar\LNumber);
        // 设定一个上限，不然可能多跑几遍数字就炸了
        if ($node->value < 9999999999) {
            // 有一半几率不进行混淆
            $i = rand(0, 3);
            switch ($i) {
                case 0:
                    // 把数字变成 $xxx ?? 1形式
                    $var = new Node\Expr\Variable(randomString());
                    return new Node\Expr\BinaryOp\Coalesce($var, $node);
                case 1:
                    if ($node->value) {
                        // 随便整个数出来相减还是原来的数，确保不产生负数
                        $len = strlen($node->value);
                        $pri = rand() % 10000 + $node->value;
                        $pad = $pri - $node->value;
                        $string = new Node\Scalar\String_($pri . randomString());
                        if (rand() % 2) {
                            return new Node\Expr\BinaryOp\Minus($string, new Node\Scalar\LNumber($pad));
                        } else {
                            $pad_string = new Node\Scalar\String_(randomString());
                            return new Node\Expr\BinaryOp\Plus(new Node\Expr\BinaryOp\Minus($string, new Node\Scalar\LNumber($pad)), $pad_string);
                        }
                    }
                    break;
                case 2:
                    // 把数字变成取模的值
                    $mod = rand() % 1000 + $node->value + 1;    // 保证模的时候不会更改数字的值，以及及其背时的情况下这个值为0
                    $times = rand() % 10;
                    $value = $node->value + $mod * $times;
                    return new Node\Expr\BinaryOp\Mod(new Node\Scalar\LNumber($value), new Node\Scalar\LNumber($mod));
                case 3:
                    // 简单愚蠢位运算
                    $offset = rand() % 5;
                    $division = $node->value / pow(2, $offset);
                    $mod = $node->value % pow(2, $offset);
                    return new Node\Expr\BinaryOp\Plus(new Node\Expr\BinaryOp\ShiftLeft(new Node\Scalar\LNumber($division), new Node\Scalar\LNumber($offset)), new Node\Scalar\LNumber($mod));
                default:
                    break;
            }
        }
        return $node;
    }

    private function obfuscateString($string): Node\Expr
    {
        $i = rand(0, 4);
        switch ($i) {
            case 0:
                // rot13+base64
                $obs_string = base64_encode(str_rot13($string));
                $b64_decode = new Node\Expr\FuncCall(new Node\Name("base64_decode"), array(new Node\Arg(new Node\Scalar\String_($obs_string))));
                return new Node\Expr\FuncCall(new Node\Name("str_rot13"), array(new Node\Arg($b64_decode)));
            case 1:
                // base64+join
                // 这里的join引入了一堆单个的字符，这就导致最后控制流平坦化之后有好多单字符在那里面。。。
                $obs_string = str_split(base64_encode($string));
                $array_items = [];
                foreach ($obs_string as $item){
                    $array_items[] = new Node\Expr\ArrayItem(new Node\Scalar\String_($item));
                }
                // 这里Array对象不能直接拿Array初始化，必须拿一个值为ArrayItem的array进行初始化
                $join = new Node\Expr\FuncCall(new Node\Name("join"), array(new Node\Arg(new Node\Scalar\String_("")), new Node\Arg(new Node\Expr\Array_($array_items))));
                return new Node\Expr\FuncCall(new Node\Name("base64_decode"), array(new Node\Arg($join)));
            case 2:
                // strrev+base64
                $obs_string = strrev(base64_encode($string));
                $strrev = new Node\Expr\FuncCall(new Node\Name("strrev"), array(new Node\Arg(new Node\Scalar\String_($obs_string))));
                return new Node\Expr\FuncCall(new Node\Name("base64_decode"), array(new Node\Arg($strrev)));
            case 3:
                $obs_string = gzdeflate($string);
                return new Node\Expr\FuncCall(new Node\Name("gzinflate"), array(new Node\Arg(new Node\Scalar\String_($obs_string))));
            case 4:
                // parse_str+base64
                $key = randomString(4);
                $var = randomString();
                $obs_string = $key . "=" . base64_encode($string);
                $parse_str = new Node\Expr\FuncCall(new Node\Name("parse_str"), array(new Node\Arg(new Node\Scalar\String_($obs_string)), new Node\Arg(new Node\Expr\Variable($var))));
                $b64_decode = new Node\Expr\FuncCall(new Node\Name("base64_decode"), array(new Node\Arg(new Node\Expr\ArrayDimFetch(new Node\Expr\Variable($var), new Node\Scalar\String_($key)))));
                $ternary = new Node\Expr\Ternary(new Node\Expr\BinaryOp\BooleanOr($parse_str, new Node\Expr\Variable($var)), $b64_decode, new Node\Expr\Variable($var));
                return $ternary;
            default:
                throw new LogicException("unknown obscure method");
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Scalar\String_) {
            return $this->obfuscateString($node->value);
        } elseif ($node instanceof Node\Scalar\LNumber) {
            return $this->obfuscateInt($node);
        }
    }
}