<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'NameObfuscator.php';
require_once 'ControlFlowFlattener.php';
require_once 'ScalarObfuscator.php';
require_once 'LogicShuffler.php';

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinter\Standard;

error_reporting(0);
ini_set('memory_limit', '512M');

$sandbox = "./code/" . md5($_SERVER['REMOTE_ADDR'] . "challenge_salt");
echo $sandbox;
if (!is_dir($sandbox)) {
    mkdir($sandbox);
    $content = file_get_contents("input.php");
    $content = sprintf($content, randomString(4), randomString(4), randomString(4), rand(200, 800), rand(200, 800), randomString(), rand(100, 1000), randomString());
    // for debug
    file_put_contents($sandbox . "/secret_origin_code.php", $content);

    $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
    $ast = $parser->parse($content);

    $prettyPrinter = new Standard();

    // 遍历
    $traverser = new NodeTraverser();
    $controlFlowFlattener = new ControlFlowFlattener();
    $logicShuffler = new LogicShuffler();
    $scalarObfuscator = new ScalarObfuscator();
    $nameObfuscator = new NameObfuscator();

    // 先把文件里定义的变量名改掉
    $traverser->addVisitor($nameObfuscator);
    $ast = $traverser->traverse($ast);
    $traverser->removeVisitor($nameObfuscator);

    // 然后改一下常量，同时goto扰乱逻辑
    $traverser->addVisitor($scalarObfuscator);
    $traverser->addVisitor($logicShuffler);
    $ast = $traverser->traverse($ast);
    $traverser->removeVisitor($logicShuffler);
    $traverser->removeVisitor($scalarObfuscator);


    // 理论上这个控制流平坦化应该最后做？
    $traverser->addVisitor($controlFlowFlattener);
    $ast = $traverser->traverse($ast);
    $traverser->removeVisitor($controlFlowFlattener);

    $ret = $prettyPrinter->prettyPrint($ast);
    file_put_contents($sandbox . "/index.php", "<?php \n" . $ret);
    file_put_contents($sandbox . "/index.txt", "<?php \n" . $ret);
}
echo "<p>your code at " . $sandbox . "/index.php</p>";
echo "<p>you can view it at " . $sandbox . "/index.txt</p>";




