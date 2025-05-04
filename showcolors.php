<?php

$PageTitle = "phpDVDProfiler CSS Color Selections";

echo "<html><head><title>$PageTitle</title><style>\n";

include_once('format.css.php');

echo <<<EOT
</style>
<body style="background-color: white;"><center><br><br><br><big><b>$PageTitle</b></big><br><br>
<table border=1>
<tr><td align=center>Colorname</td><td align=center>Looks Like</td><td align=center>(In case that was illegible)</td></tr>
EOT;

foreach ($ClassColor as $ind => $val) {
    echo "<tr><td>ClassColor[$ind]</td>";
    echo "<td style=\"background-color: " . $ClassColor[$ind] . ";\">This cell background is color " . $ClassColor[$ind] . "</td>";
    echo "<td>That cell background is color " . $ClassColor[$ind] . "</td></tr>";
}
echo "</table></center></body></html>";
