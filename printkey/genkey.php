<?php
if ($_GET["type"] < 2) {
  $nobits = 5;
} else {
  $nobits = 6;
}
for ($i = 1; $i <= $nobits; $i++) {
        if (is_numeric($_GET[$i])) {
                if ($currentbit = intval($_GET[$i])) {
                        if ($currentbit > 0 && $currentbit < 8) {
                                $bit[$i] = $_GET[$i];
                        } else {
                                die("Bit ".$i." is too big or too small!");
                        }
                } else {
                        die("Bit ".$i." isn't valid!");
                }
        } else {
                die("Bit ".$i." isn't a number!");
        }
}
if ($_GET["type"] == 0) {
  $scadcode = file_get_contents("kw1.scad");
} else if ($_GET["type"] == 1) {
  $scadcode = file_get_contents("sc1.scad");
} else if ($_GET["type"] == 2) {
  $scadcode = file_get_contents("sc4.scad");
} else {
  die("Key type isn't valid!");
}
$scadcode = rtrim($scadcode);
$scadcode .= implode(",",$bit);
$scadcode .= "]);";
$temp = tmpfile();
fwrite($temp, $scadcode);
shell_exec("openscad -o /tmp/generatedkey.stl ".stream_get_meta_data($temp)['uri']);
header("Content-Type: application/octet-stream");
header("Content-Transfer-Encoding: Binary");
header("Content-disposition: attachment; filename=\"yourkey.stl\"");
echo readfile("/tmp/generatedkey.stl");
?>
