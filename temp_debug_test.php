<?php

echo "<pre>";
$module->getUser();
$module->authorizeUser();
echo 'user:<br>';
print_r($module->user);
echo 'uad_data:<br>';
print_r($module->uad_data);
echo "</pre>";

?>