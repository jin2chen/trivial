<?php
defined('NI_LEVELS') or define('NI_LEVELS',3); // number of hierarchy levels below root to initialise - min=3
defined('NI_TREES') or define('NI_TREES',1); // number of trees
defined('NI_CHILDREN') or define('NI_CHILDREN',4); // number of children each node has - min=4

if (!function_exists('children')) {
	function children($parent,&$nodes,$level) {
		for ($child=1;$child<=NI_CHILDREN;$child++) {
			$node=array(
				'name'=>"{$parent['name']}.$child",
				'nv'=>$parent['nv']+$child*$parent['snv'],
				'dv'=>$parent['dv']+$child*$parent['sdv'],
				'snv'=>$parent['nv']+($child+1)*$parent['snv'],
				'sdv'=>$parent['dv']+($child+1)*$parent['sdv'],
			);
			$nodes[$node['name']]=$node;

			if ($level<NI_LEVELS)
				children($node,$nodes,$level+1);
		}
	}
}

$nodes = array();
for ($tree=1;$tree<=NI_TREES;$tree++) {
	$node = array(
		'name'=>$tree,
		'nv'=>$tree,
		'dv'=>1,
		'snv'=>$tree+1,
		'sdv'=>1,
	);
	$nodes[$node['name']]=$node;
	children($node,$nodes,1);

}

return $nodes;