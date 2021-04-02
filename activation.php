<?php
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . "/templates");
$twig = new \Twig\Environment($loader, [ 'debug' => true]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

//Just for right now Static info
$template = $twig->load("activation.twig");


$testingArray = array("Site 1", "Site 2", "Site 3");

echo $template->render([
   'data' => $testingArray
]);

