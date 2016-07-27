<?php
/**
 * Description...
 *
 * @author     mole <mole1230@gmail.com>
 * @version    $Id: qsort.php 104 2011-03-24 02:38:38Z mole1230 $
 */

function qsort(&$a)
{
	return _qsort($a, 0, count($a) - 1);
}

function _qsort(&$a, $l, $r)
{
	if ($l >= $r) {
		return;
	}

	$i = $l;
	$j = $r;
	$t = $a[$l];
	while ($i != $j) {
		while ($a[$j] >= $t && $j > $i) {
			$j--;
		}

		if ($j > $i) {
			$a[$i++] = $a[$j];
		}

		while ($a[$i] <= $t && $j > $i) {
			$i++;
		}

		if ($j > $i) {
			$a[$j--] = $a[$i];
		}
	}

	$a[$i] = $t;
	_qsort($a, $l, $i - 1);
	_qsort($a, $i + 1, $r);
}

$a = array(9, 8, 7, 6, 5, 4, 3, 2, 1, 0);
print_r($a);
qsort($a);
print_r($a);
