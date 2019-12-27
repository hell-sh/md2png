<?php
if(empty($argv[1]))
{
	die("Syntax: md2png <mdfile>\n");
}
if(!is_file($argv[1]))
{
	die("Error: mdfile '{$argv[1]}' was not found.\n");
}
if(!is_file(__DIR__."/vendor/autoload.php"))
{
	echo "vendor/autoload.php was not found, attempting to generate it...\n";
	passthru("composer install -o -d \"".__DIR__."\"");
	if(!is_file(__DIR__."/vendor/autoload.php"))
	{
		die("Welp, that didn't work. Try again as root/administrator.\n");
	}
}
require __DIR__."/vendor/autoload.php";
use pac\
{Chromium, Page};
use pas\
{pas, stdin};

$output = join(".", array_slice(array_reverse(explode(".", $argv[1])), 1)).".png";
if(is_file($output))
{
	stdin::init(null, false);
	echo $output." already exists. Override? [Y/n] ";
	if(stdin::getNextLine() == "n")
	{
		exit;
	}
	if(!unlink($output))
	{
		die("Failed to delete $output\n");
	}
}
$c = new Chromium();
if(!$c->isAvailable())
{
	echo "Downloading Chromium...";
	$c->download();
	echo " Done.\n";
}
$i = $c->start();
//$i->logging = true;
$i->newPage(function(Page $page)
{
	global $argv;
	$page->once("Page.loadEventFired", function() use (&$page)
	{
		$page->getLayoutMetrics(function($result) use (&$page)
		{
			$page->setDeviceMetrics($result["contentSize"]["width"], $result["contentSize"]["height"] + 120, 1, function() use (&$page)
			{
				$page->captureScreenshot("png", [], function($data)
				{
					global $i, $output;
					file_put_contents($output, base64_decode($data));
					echo "Screenshot saved to $output.\n";
					$i->close();
				});
			});
		});
	})->setDocumentContent("<style>".file_get_contents(__DIR__."/assets/Caret.css")."</style>".(new Parsedown())->text(file_get_contents($argv[1])));
});
pas::loop();
