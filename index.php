<?php

echo "<h1>HLF.com RESTfull API Index</h1>";

if( count( $_POST ) > 0 ) {
	echo "<ol>";
	foreach( $_POST as $key => $val ) {
		echo "<li>$key : $val</li>";
	}
	echo "</ol>";
}