<?php
require_once 'vendor/autoload.php';
require_once 'Renamer.php';
require_once 'ControlFlowRestorer.php';

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\NodeDumper;


ini_set('memory_limit', '1024M');
error_reporting(E_ALL);

$content = file_get_contents("example.php");

$parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
$ast = $parser->parse($content);


// 遍历
$traverser = new NodeTraverser();
$traverser->addVisitor(new Renamer());
$traverser->addVisitor(new ControlFlowRestorer());
$ast = $traverser->traverse($ast);

// 输出
$prettyPrinter = new Standard();
$ret = $prettyPrinter->prettyPrint($ast);
echo "<?php\n" . $ret;
file_put_contents("output.php", "<?php \n" . $ret);
