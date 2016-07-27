<?php
Yii::import('common.modules.galleries.components.behaviors.*');
Yii::import('common.modules.galleries.models.*');
class NestedIntervalTest extends CDbTestCase {
	public $fixtures = array(
		'nested_intervals'=>'NestedInterval'
	);

	/**
	* Test the isX methods as we use them to check things later
	*/
	public function testIsX() {
		$node_1 = NestedInterval::model()->find('nv=1 AND dv=1');
		$node_1_1 = NestedInterval::model()->find('nv=3 AND dv=2');
		$node_1_2 = NestedInterval::model()->find('nv=5 AND dv=3');
		$node_1_3 = NestedInterval::model()->find('nv=7 AND dv=4');
		$node_1_2_2 = NestedInterval::model()->find('nv=19 AND dv=11');
		$node_1_2_4 = NestedInterval::model()->find('nv=33 AND dv=19');
		$node_1_2_2_1 = NestedInterval::model()->find('nv=45 AND dv=26');
		$node_1_2_2_3 = NestedInterval::model()->find('nv=97 AND dv=56');

		$this->assertTrue($node_1->getIsRoot());
		$this->assertFalse($node_1_1->getIsRoot());
		$this->assertFalse($node_1_2->getIsRoot());
		$this->assertFalse($node_1_3->getIsRoot());
		$this->assertFalse($node_1_2_2->getIsRoot());
		$this->assertFalse($node_1_2_4->getIsRoot());
		$this->assertTrue($node_1_1->isChildOf($node_1));
		$this->assertTrue($node_1_2->isChildOf($node_1));
		$this->assertTrue($node_1_3->isChildOf($node_1));
		$this->assertFalse($node_1->isChildOf($node_1_1));
		$this->assertFalse($node_1->isChildOf($node_1_2));
		$this->assertFalse($node_1->isChildOf($node_1_3));
		$this->assertTrue($node_1->isParentOf($node_1_1));
		$this->assertTrue($node_1->isParentOf($node_1_2));
		$this->assertTrue($node_1->isParentOf($node_1_3));
		$this->assertFalse($node_1->isParentOf($node_1_2_2));
		$this->assertFalse($node_1_2->isParentOf($node_1_2_2_1));
		$this->assertTrue($node_1->isAncestorOf($node_1_2_2));
		$this->assertTrue($node_1_2->isAncestorOf($node_1_2_2_1));

		$this->assertTrue($node_1_2_2->isChildOf($node_1_2));
		$this->assertTrue($node_1_2_4->isChildOf($node_1_2));
		$this->assertFalse($node_1_2_2->isChildOf($node_1_1));
		$this->assertFalse($node_1_2_2->isChildOf($node_1_3));
		$this->assertFalse($node_1_2_4->isChildOf($node_1_1));
		$this->assertFalse($node_1_2_4->isChildOf($node_1_3));
		$this->assertTrue($node_1_2_2_1->isChildOf($node_1_2_2));
		$this->assertTrue($node_1_2_2_3->isChildOf($node_1_2_2));
		$this->assertFalse($node_1_2_2_1->isChildOf($node_1_2));
		$this->assertFalse($node_1_2_2_3->isChildOf($node_1_2));
		$this->assertTrue($node_1_2_2_1->isDescendantOf($node_1));
		$this->assertTrue($node_1_2_2_3->isDescendantOf($node_1));
		$this->assertTrue($node_1_2_2_1->isDescendantOf($node_1_2));
		$this->assertTrue($node_1_2_2_3->isDescendantOf($node_1_2));
		$this->assertTrue($node_1_2_2_1->isDescendantOf($node_1_2_2));
		$this->assertTrue($node_1_2_2_3->isDescendantOf($node_1_2_2));
		$this->assertTrue($node_1_1->isSiblingOf($node_1_2));
		$this->assertTrue($node_1_1->isSiblingOf($node_1_3));
		$this->assertTrue($node_1_2->isSiblingOf($node_1_3));
		$this->assertTrue($node_1_2_2->isSiblingOf($node_1_2_4));
		$this->assertTrue($node_1_2_2_1->isSiblingOf($node_1_2_2_3));
		$this->assertFalse($node_1_2->isSiblingOf($node_1_2_2));

		$this->assertFalse($node_1->getIsLeaf());
		$this->assertFalse($node_1_1->getIsLeaf());
		$this->assertFalse($node_1_2_2->getIsLeaf());
		$this->assertTrue($node_1_2_2_3->getIsLeaf());
	}

	/**
	* Test finding root nodes
	*/
	public function testFindRoots() {
		$roots = NestedInterval::model()->roots()->findAll();
		$this->assertCount(NI_TREES,$roots);
		foreach ($roots as $n=>$root) {
			$n++;
			$this->assertEquals($n,$root->nv);
			$this->assertTrue($root->getIsRoot());
		}
	}

	/**
	* Test finding a particular root node
	* (though in real usage the root will be found on other predicates)
	*/
	public function testFindNthRoot() {
		$n = rand(1,NI_TREES);
		$root = NestedInterval::model()->root($n)->find();

		$this->assertTrue($root->getIsRoot());
			$this->assertEquals($n,$root->getPosition());
	}

	/**
	* Test finding the root node of a node
	*/
	public function testFindRoot() {
		$node = $this->loadNode(1,NI_LEVELS);
		$root = $node->root()->find();

		$this->assertTrue($root->getIsRoot());
		$this->assertTrue($root->isAncestorOf($node));
	}


	/**
	* Test finding the first child of a node
	*/
	public function testFindFirstChild() {
		$node = $this->loadNode(0,NI_LEVELS-1);
		$child = $node->firstChild()->find();

		$this->assertTrue($child->isChildOf($node));
		$this->assertEquals(1,$child->getPosition());
		$this->assertEquals($child,$node->firstChild);
	}

	/**
	* Test finding the last child of a node
	*/
	public function testFindLastChild() {
		$node = $this->loadNode(0,NI_LEVELS-1);
		$child = $node->lastChild()->find();

		$this->assertTrue($child->isChildOf($node));
		$this->assertEquals(NI_CHILDREN,$child->getPosition());
		$this->assertEquals($child,$node->lastChild);
	}

	/**
	* Test finding the nth child of a node
	*/
	public function testFindNthChild() {
		$n = rand(1,NI_CHILDREN);
		$node = $this->loadNode(0,NI_LEVELS-1);
		$child = $node->child($n)->find();

		$this->assertTrue($child->isChildOf($node));
		$this->assertEquals($n,$child->getPosition());
	}

	/**
	* Test finding the children of a node
	*/
	public function testFindChildren() {
		$node = $this->loadNode(0,NI_LEVELS-1);
		$children = $node->children()->findAll();

		$this->assertCount(NI_CHILDREN,$children);
		foreach ($children as $n=>$child) {
			$n++;
			$this->assertTrue($child->isChildOf($node));
			$this->assertEquals($n,$child->getPosition());
		}

		$children = $node->children;

		$this->assertCount(NI_CHILDREN,$children);
		foreach ($children as $n=>$child) {
			$n++;
			$this->assertTrue($child->isChildOf($node));
			$this->assertEquals($n,$child->getPosition());
		}
	}

	/**
	* Test finding the first sibling of a node
	*/
	public function testFindFirstSibling() {
		$node = $this->loadNode(1);
		$sibling = $node->firstSibling()->find();

		$this->assertTrue($sibling->isSiblingOf($node));
		$this->assertEquals(1,$sibling->getPosition());
		$this->assertEquals($sibling,$node->firstSibling);
	}

	/**
	* Test finding the last sibling of a node
	*/
	public function testFindLastSibling() {
		$node = $this->loadNode(1);
		$sibling = $node->lastSibling()->find();

		$this->assertTrue($sibling->isSiblingOf($node));
		$this->assertEquals(NI_CHILDREN,$sibling->getPosition());
		$this->assertEquals($sibling,$node->lastSibling);
	}

	/**
	* Test finding the nth sibling of a node
	*/
	public function testFindNthSibling() {
		$n = rand(1,NI_CHILDREN);
		$node = $this->loadNode(1,NI_LEVELS);
		$sibling = $node->sibling($n)->find();

		$this->assertTrue($sibling->isSiblingOf($node));
		$this->assertEquals($n,$sibling->getPosition());
	}

	/**
	* Test finding the next sibling of a node
	*/
	public function testFindNextSibling() {
		$node = $this->loadNode(1,NI_LEVELS,1,NI_CHILDREN-1);
		$sibling = $node->nextSibling()->find();

		// check it is the right sibling
		$this->assertTrue($sibling->isSiblingOf($node));
		$this->assertEquals($node->getPosition()+1,$sibling->getPosition());

		// check we get the same by accessing as a property
		$this->assertEquals($sibling,$node->nextSibling);
	}

	/**
	* Test finding the previous sibling of a node
	*/
	public function testFindPreviousSibling() {
		$node = $this->loadNode(1,NI_LEVELS,2,NI_CHILDREN);
		$sibling = $node->previousSibling()->find();

		$this->assertTrue($sibling->isSiblingOf($node));
		$this->assertEquals($node->getPosition()-1,$sibling->getPosition());
		$this->assertEquals($sibling,$node->previousSibling);
	}

	/**
	* Test finding all siblings of a node - including itself
	*/
	public function testFindAllSiblings() {
		$node = $this->loadNode(1);
		$siblings = $node->siblings(NestedIntervalBehavior::SIBLINGS_ALL)->findAll();

		list($psnv,$psdv,$pnv,$pdv) = $node->parentQuadruple();
		$pos  = $node->getPosition();

		$this->assertCount(NI_CHILDREN,$siblings);
		foreach ($siblings as $n=>$sibling) {
			$n++;
			$this->assertTrue($sibling->isSiblingOf($node));
			$this->assertEquals($n,$sibling->getPosition());
		}
	}

	/**
	* Test finding all siblings of a node - not including itself
	*/
	public function testFindSiblingsExcludingSelf() {
		$node = $this->loadNode(1);
		$siblings = $node->siblings()->findAll();

		list($psnv,$psdv,$pnv,$pdv) = $node->parentQuadruple();
		$pos  = $node->getPosition();

		$this->assertCount(NI_CHILDREN - 1,$siblings);
		foreach ($siblings as $n=>$sibling) {
			$n++;
			$this->assertTrue($sibling->isSiblingOf($node));
			$this->assertNotEquals($pos, $sibling->getPosition());
			$this->assertEquals($n+($n<$pos?0:1),$sibling->getPosition());
		}
	}

	/**
	* Test finding all earlier siblings of a node
	*/
	public function testFindEarlierSiblings() {
		$node = $this->loadNode(1,NI_LEVELS,2);
		$siblings = $node->siblings(NestedIntervalBehavior::SIBLINGS_BEFORE)->findAll();

		list($psnv,$psdv,$pnv,$pdv) = $node->parentQuadruple();
		$pos  = $node->getPosition();

		$this->assertCount($pos - 1,$siblings);
		foreach ($siblings as $n=>$sibling) {
			$n++;
			$this->assertTrue($sibling->isSiblingOf($node));
			$this->assertLessThan($pos,$sibling->getPosition());
			$this->assertEquals($n,$sibling->getPosition());
		}
	}

	/**
	* Test finding all later siblings of a node
	*/
	public function testFindLaterSiblings() {
		$node = $this->loadNode(1,NI_LEVELS,2);
		$siblings = $node->siblings(NestedIntervalBehavior::SIBLINGS_AFTER)->findAll();

		list($psnv,$psdv,$pnv,$pdv) = $node->parentQuadruple();
		$pos  = $node->getPosition();

		$this->assertCount(NI_CHILDREN - $pos,$siblings);
		foreach ($siblings as $n=>$sibling) {
			$n++;
			$this->assertTrue($sibling->isSiblingOf($node));
			$this->assertGreaterThan($pos,$sibling->getPosition());
			$this->assertEquals($n+$pos,$sibling->getPosition());
		}
	}

	/**
	* Test finding the descendants of a node
	*/
	public function testFindDescendants() {
		$node = $this->loadNode(1,1); // load a node high up a tree
		$count = 0;
		for ($l=1;$l<NI_LEVELS;$l++)
			$count += pow(NI_CHILDREN,$l);

		$descendants = $node->descendants()->findAll();
		$this->assertCount($count,$descendants);

		foreach ($descendants as $descendant)
			$this->assertTrue($descendant->isDescendantOf($node));
	}

	/**
	* Test getting the descendants of a node
	*/
	public function testCreateDescendantTree() {
		$node = $this->loadNode(1,1); // load a node high up a tree
		$node->createDescendantTree();
		$this->checkDescendantTree($node);
	}

	/**
	*
	*/
	private function checkDescendantTree($node) {
		$childNodes = ($node->hasRelated('childNodes')?$node->childNodes:array());
		$children = $node->children;

		$this->assertEquals(count($children),count($childNodes));

		foreach ($children as $c=>$child) {
			$childNode = $childNodes[$c];
			$this->assertKeyEquals($child,$childNode);
			$this->checkDescendantTree($childNode);
		}
	}

	/**
	* Test finding the parent of a node
	*/
	public function testFindParent() {
		$node = $this->loadNode(1,NI_LEVELS);
		$parent = $node->parent()->findAll();
		$this->assertCount(1,$parent);
		$parent = $parent[0];
		$this->assertTrue($parent->isParentOf($node));
		$this->assertEquals($parent,$node->parent);
	}

	/**
	* Test finding the ancestors of a node
	*/
	public function testFindAncestors() {
		$node = $this->loadNode(NI_LEVELS,NI_LEVELS); // load a node from the bottom level
		$ancestors = $node->ancestors()->findAll();
		$this->assertCount(NI_LEVELS,$ancestors);
		$tree = explode('.',$node->name);
		foreach ($ancestors as $ancestor)
			$this->assertTrue($ancestor->isAncestorOf($node));

		$parent = $node->ancestors;
		do {
			$this->assertTrue($parent->isParentOf($node));
			$node = $parent;
			$parent = $parent->parentNode;
		} while(!$parent->isRoot);
	}

	/**
	* get descendants of a node
	*/
	/**
	* get ancestors of a node
	*/
	/**
	* Add a new root node
	*/
	public function testCreateRoot() {
		$model = new NestedInterval();
		$model->setAttributes(array(
			'name'=>NI_TREES+1
		),false);
		$this->assertTrue($model->addAsRoot());
		$roots = $model->roots()->findAll();
		$this->assertCount(NI_TREES+1,$roots);
		$node = array_pop($roots);
		$this->assertEquals(NI_TREES+1,$node->getPosition());
		$this->assertTrue($node->getIsRoot());
		$this->assertEquals(0,$node->children()->count());
	}

	/**
	* Test appending a new node to a node (adding as the last child)
	* In this scenario no reindexing of the tree is needed
	*/
	public function testAppendTo() {
		$node = $this->loadNode(0,NI_LEVELS-2);
		$descendantCount = $node->descendants()->count();
		$new = new NestedInterval();
		$new->name = $node->name.'.'.(NI_CHILDREN+1);
		$new->appendTo($node,false);
		$children = $node->children()->findAll();

		// Check we have an extra descendant of the parent
		$this->assertEquals($descendantCount+1,$node->descendants()->count());

		// Check we have an extra child of the parent
		$this->assertCount(NI_CHILDREN+1,$children);

		// Check each child is a child of the parent
		foreach ($children as $n=>$child) {
			$n++;
			$this->assertTrue($child->isChildOf($node));
			$this->assertEquals($n,$child->getPosition());
		}

		// Check the node we added as the right name and no children
		$this->assertEquals($new->name,$child->name);
		$this->assertEquals(0,$child->children()->count());
	}

	/**
	* Test appending a new node (adding as the last child)
	* In this scenario no reindexing of the tree is needed
	*/
	public function testAppend() {
		$node = $this->loadNode(0,NI_LEVELS-2);
		$descendantCount = $node->descendants()->count();
		$new = new NestedInterval();
		$new->name = $node->name.'.'.(NI_CHILDREN+1);
		$node->append($new,false);
		$children = $node->children()->findAll();

		$this->assertEquals($descendantCount+1,$node->descendants()->count());
		$this->assertCount(NI_CHILDREN+1,$children);
		foreach ($children as $n=>$child) {
			$n++;
			$this->assertTrue($child->isChildOf($node));
			$this->assertEquals($n,$child->getPosition());
		}
		$this->assertEquals($new->name,$child->name);
		$this->assertEquals(0,$child->children()->count());
	}

	/**
	* Test prepending a new node to an existing node (adding as the first child)
	*/
	public function testPrependTo() {
		$node = $this->loadNode(0,NI_LEVELS-2);
		$descendantCount = $node->descendants()->count();
		$new = new NestedInterval();
		$new->name = $node->name.'.'.(NI_CHILDREN+1);
		$new->prependTo($node,false);
		$children = $node->children()->findAll();

		$this->assertEquals($descendantCount+1,$node->descendants()->count());
		$this->assertCount(NI_CHILDREN+1,$children);
		foreach ($children as $n=>$child) {
			if ($n===0) {
				$this->assertEquals($new->name,$child->name);
				$this->assertEquals(0,$child->children()->count());
			}
			$n++;
			$this->assertTrue($child->isChildOf($node));
			$this->assertEquals($n,$child->getPosition());
		}
	}

	/**
	* Test prepending a new node (adding as the first child)
	*/
	public function testPrepend() {
		$node = $this->loadNode(0,NI_LEVELS-2);
		$descendantCount = $node->descendants()->count();
		$new = new NestedInterval();
		$new->name = $node->name.'.'.(NI_CHILDREN+1);
		$node->prepend($new,false);
		$children = $node->children()->findAll();

		$this->assertEquals($descendantCount+1,$node->descendants()->count());
		$this->assertCount(NI_CHILDREN+1,$children);
		foreach ($children as $n=>$child) {
			if ($n===0) {
				$this->assertEquals($new->name,$child->name);
				$this->assertEquals(0,$child->children()->count());
			}
			$n++;
			$this->assertTrue($child->isChildOf($node));
			$this->assertEquals($n,$child->getPosition());
		}
	}

	/**
	* Test adding a new node among the children
	*/
	public function testAddAsChildOf() {
		$node = $this->loadNode(0,NI_LEVELS-2);
		$descendantCount = $node->descendants()->count();
		$new = new NestedInterval();
		$new->name = $node->name.'.'.(NI_CHILDREN+1);
		$p = rand(2,NI_CHILDREN);
		$new->addAsChildOf($node,$p,false);
		$children = $node->children()->findAll();

		$this->assertEquals($descendantCount+1,$node->descendants()->count());
		$this->assertCount(NI_CHILDREN+1,$children);
		foreach ($children as $n=>$child) {
			$n++;
			if ($n===$p) {
				$this->assertEquals($new->name,$child->name);
				$this->assertEquals(0,$child->children()->count());
			}
			$this->assertTrue($child->isChildOf($node));
			$this->assertEquals($n,$child->getPosition());
		}
	}

	/**
	* Test adding a new node among the children
	*/
	public function testAddAsChild() {
		$node = $this->loadNode(0,NI_LEVELS-1);
		$descendantCount = $node->descendants()->count();
		$new = new NestedInterval();
		$new->name = $node->name.'.'.(NI_CHILDREN+1);
		$p = rand(2,NI_CHILDREN);
		$node->addAsChild($new,$p,false);
		$children = $node->children()->findAll();

		$this->assertEquals($descendantCount+1,$node->descendants()->count());
		$this->assertCount(NI_CHILDREN+1,$children);
		foreach ($children as $n=>$child) {
			$n++;
			if ($n===$p) {
				$this->assertEquals($new->name,$child->name);
				$this->assertEquals(0,$child->children()->count());
			}
			$this->assertTrue($child->isChildOf($node));
			$this->assertEquals($n,$child->getPosition());
		}
	}

	/**
	* Test adding a node as the last sibling
	*/
	public function testInsertLast() {
		$node = $this->loadNode(1,NI_LEVELS);
		$new = new NestedInterval();
		$name = explode('.',$node->name);
		array_pop($name);
		array_push($name,NI_CHILDREN+1);
		$new->name = join('.',$name);
		$new->insertLast($node,false);

		$siblings = $node->siblings(NestedIntervalBehavior::SIBLINGS_ALL)->findAll();

		// Check we have an extra sibling of the node
		$this->assertCount(NI_CHILDREN+1,$siblings);

		// Check each sibling is a sibling
		foreach ($siblings as $n=>$sibling) {
			$n++;
			$this->assertTrue($sibling->isSiblingOf($node));
			$this->assertEquals($n,$sibling->getPosition());
		}
		$this->assertEquals($new->name,$sibling->name);
	}

	/**
	* Test adding a node as the last sibling
	*/
	public function testLast() {
		$node = $this->loadNode(1,NI_LEVELS);
		$new = new NestedInterval();
		$name = explode('.',$node->name);
		array_pop($name);
		array_push($name,NI_CHILDREN+1);
		$new->name = join('.',$name);
		$node->last($new,false);

		$siblings = $node->siblings(NestedIntervalBehavior::SIBLINGS_ALL)->findAll();

		// Check we have an extra sibling of the node
		$this->assertCount(NI_CHILDREN+1,$siblings);

		// Check each sibling is a sibling
		foreach ($siblings as $n=>$sibling) {
			$n++;
			$this->assertTrue($sibling->isSiblingOf($node));
			$this->assertEquals($n,$sibling->getPosition());
		}
		$this->assertEquals($new->name,$sibling->name);
	}

	/**
	* Test adding a node as the first sibling
	*/
	public function testInserFirst() {
		$node = $this->loadNode(1,NI_LEVELS);
		$new = new NestedInterval();
		$name = explode('.',$node->name);
		array_pop($name);
		array_push($name,NI_CHILDREN+1);
		$new->name = join('.',$name);
		$new->insertFirst($node,false);

		$siblings = $node->siblings(NestedIntervalBehavior::SIBLINGS_ALL)->findAll();

		// Check we have an extra sibling of the node
		$this->assertCount(NI_CHILDREN+1,$siblings);

		// Check each sibling is a sibling
		foreach ($siblings as $n=>$sibling) {
			if ($n===0)
				$this->assertEquals($new->name,$sibling->name);
			$n++;
			$this->assertTrue($sibling->isSiblingOf($node));
			$this->assertEquals($n,$sibling->getPosition());
		}
	}

	/**
	* Test adding a node as the first sibling
	*/
	public function testFirst() {
		$node = $this->loadNode(1,NI_LEVELS);
		$new = new NestedInterval();
		$name = explode('.',$node->name);
		array_pop($name);
		array_push($name,NI_CHILDREN+1);
		$new->name = join('.',$name);
		$node->first($new,false);

		$siblings = $node->siblings(NestedIntervalBehavior::SIBLINGS_ALL)->findAll();

		// Check we have an extra sibling of the node
		$this->assertCount(NI_CHILDREN+1,$siblings);

		// Check each sibling is a sibling
		foreach ($siblings as $n=>$sibling) {
			if ($n===0)
				$this->assertEquals($new->name,$sibling->name);
			$n++;
			$this->assertTrue($sibling->isSiblingOf($node));
			$this->assertEquals($n,$sibling->getPosition());
		}
	}

	/**
	* Test adding a node as the next sibling
	*/
	public function testInsertAfter() {
		$node = $this->loadNode(1,NI_LEVELS);
		$p = $node->getPosition() + 1;
		$new = new NestedInterval();
		$name = explode('.',$node->name);
		array_pop($name);
		array_push($name,NI_CHILDREN+1);
		$new->name = join('.',$name);
		$new->insertAfter($node,false);

		$siblings = $node->siblings(NestedIntervalBehavior::SIBLINGS_ALL)->findAll();

		// Check we have an extra sibling of the node
		$this->assertCount(NI_CHILDREN+1,$siblings);

		// Check each sibling is a sibling
		foreach ($siblings as $n=>$sibling) {
			$n++;
			if ($n===$p)
				$this->assertEquals($new->name,$sibling->name);
			$this->assertTrue($sibling->isSiblingOf($node));
			$this->assertEquals($n,$sibling->getPosition());
		}
	}

	/**
	* Test adding a node as the next sibling
	*/
	public function testAfter() {
		$node = $this->loadNode(1,NI_LEVELS);
		$p = $node->getPosition() + 1;
		$new = new NestedInterval();
		$name = explode('.',$node->name);
		array_pop($name);
		array_push($name,NI_CHILDREN+1);
		$new->name = join('.',$name);
		$node->after($new,false);

		$siblings = $node->siblings(NestedIntervalBehavior::SIBLINGS_ALL)->findAll();

		// Check we have an extra sibling of the node
		$this->assertCount(NI_CHILDREN+1,$siblings);

		// Check each sibling is a sibling
		foreach ($siblings as $n=>$sibling) {
			$n++;
			if ($n===$p)
				$this->assertEquals($new->name,$sibling->name);
			$this->assertTrue($sibling->isSiblingOf($node));
			$this->assertEquals($n,$sibling->getPosition());
		}
	}

	/**
	* Test adding a node as the previous sibling
	* The new node will be in position of the node
	*/
	public function testInsertBefore() {
		$node = $this->loadNode(1,NI_LEVELS);
		$p = $node->getPosition();
		$new = new NestedInterval();
		$name = explode('.',$node->name);
		array_pop($name);
		array_push($name,NI_CHILDREN+1);
		$new->name = join('.',$name);
		$new->insertBefore($node,false);

		$siblings = $node->siblings(NestedIntervalBehavior::SIBLINGS_ALL)->findAll();

		// Check we have an extra sibling of the node
		$this->assertCount(NI_CHILDREN+1,$siblings);

		// Check each sibling is a sibling
		foreach ($siblings as $n=>$sibling) {
			$n++;
			if ($n===$p)
				$this->assertEquals($new->name,$sibling->name);
			$this->assertTrue($sibling->isSiblingOf($node));
			$this->assertEquals($n,$sibling->getPosition());
		}
	}

	/**
	* Test adding a node as the previous sibling
	* The new node will be in position of the node
	*/
	public function testBefore() {
		$node = $this->loadNode(1,NI_LEVELS);
		$p = $node->getPosition();
		$new = new NestedInterval();
		$name = explode('.',$node->name);
		array_pop($name);
		array_push($name,NI_CHILDREN+1);
		$new->name = join('.',$name);
		$node->before($new,false);

		$siblings = $node->siblings(NestedIntervalBehavior::SIBLINGS_ALL)->findAll();

		// Check we have an extra sibling of the node
		$this->assertCount(NI_CHILDREN+1,$siblings);

		// Check each sibling is a sibling
		foreach ($siblings as $n=>$sibling) {
			$n++;
			if ($n===$p)
				$this->assertEquals($new->name,$sibling->name);
			$this->assertTrue($sibling->isSiblingOf($node));
			$this->assertEquals($n,$sibling->getPosition());
		}
	}

	/**
	* Test adding a node as a sibling of the node
	*/
	public function testAddAsSiblingOf() {
		$p = rand(0,NI_CHILDREN);
		$node = $this->loadNode(1,NI_LEVELS);
		$new = new NestedInterval();
		$name = explode('.',$node->name);
		array_pop($name);
		array_push($name,NI_CHILDREN+1);
		$new->name = join('.',$name);
		$new->addAsSiblingOf($node,$p,false);

		$siblings = $node->siblings(NestedIntervalBehavior::SIBLINGS_ALL)->findAll();

		// Check we have an extra sibling of the node
		$this->assertCount(NI_CHILDREN+1,$siblings);

		// Check each sibling is a sibling
		foreach ($siblings as $n=>$sibling) {
			$n++;
			if ($n===$p)
				$this->assertEquals($new->name,$sibling->name);
			$this->assertTrue($sibling->isSiblingOf($node));
			$this->assertEquals($n,$sibling->getPosition());
		}
	}

	/**
	* Test adding a node as a sibling of the node
	*/
	public function testAddAsSibling() {
		$p = rand(0,NI_CHILDREN);
		$node = $this->loadNode(1,NI_LEVELS);
		$new = new NestedInterval();
		$name = explode('.',$node->name);
		array_pop($name);
		array_push($name,NI_CHILDREN+1);
		$new->name = join('.',$name);
		$node->addAsSibling($new,$p,false);

		$siblings = $node->siblings(NestedIntervalBehavior::SIBLINGS_ALL)->findAll();

		// Check we have an extra sibling of the node
		$this->assertCount(NI_CHILDREN+1,$siblings);

		// Check each sibling is a sibling
		foreach ($siblings as $n=>$sibling) {
			$n++;
			if ($n===$p)
				$this->assertEquals($new->name,$sibling->name);
			$this->assertTrue($sibling->isSiblingOf($node));
			$this->assertEquals($n,$sibling->getPosition());
		}
	}

	/**
	* Delete the descendants of a node
	*/
	public function testDeleteDescendants() {
		$node = $this->loadNode(1,NI_LEVELS-1);
		$descendants = $node->descendants()->count();
		$this->assertNotEquals(0,$descendants);
		$deleted = $node->deleteDescendants();
		$this->assertEquals($descendants,$deleted);
		$this->assertEquals(0,$node->descendants()->count());
	}

	/**
	* Delete a node and all its descendants
	* @return integer the number of nodes deleted.
	*/
	public function testDeleteNode() {
		$node = $this->loadNode(1,NI_LEVELS-1);
		$parent = $node->parent()->find();
		$descendants = $parent->descendants()->count();
		$this->assertFalse($node->getIsDeleted());
		$this->assertNotEquals(0,$descendants);
		$deleted = $node->deleteNode();
		$this->assertTrue($node->getIsDeleted());
		$this->assertEquals($descendants - $deleted, $parent->descendants()->count());
	}

	/**
	* Moves the owner node to become the last child of the target node where node
	* is a child of target
	*/
	public function testMoveChildToLastChild() {
		// Get a node that is not a last child
		$node = $this->loadNode(1,NI_LEVELS-1,1,NI_CHILDREN-1);

		$target = $node->parent()->find();
		$descendants = $target->descendants()->count();

		$this->assertNotEquals($node->name,$target->lastChild()->find()->name);
		$this->assertEquals($node->descendants()->count()+1,$node->moveToLastChild($target));

		$this->assertEquals($descendants,$target->descendants()->count());
		$this->assertEquals($node->name,$target->lastChild()->find()->name);
		$this->assertKeyEquals($node,$target->lastChild);
	}

	/**
	* Moves the owner node to become the first child of the target node where node
	* is a child of target
	*/
	public function testMoveChildToFirstChild() {
		// Get a node that is not a first child
		$node = $this->loadNode(1,NI_LEVELS-1,2);

		$target = $node->parent()->find();
		$descendants = $target->descendants()->count();

		$this->assertNotEquals($node->name,$target->firstChild()->find()->name);
		$this->assertEquals($node->descendants()->count()+1,$node->moveToFirstChild($target));

		$this->assertEquals($descendants,$target->descendants()->count());
		$this->assertEquals($node->name,$target->firstChild()->find()->name);
		$this->assertKeyEquals($node,$target->firstChild);
	}

	/**
	* Moves the owner node to become the nth child of the target node where the
	* owner is a child of the target and its current position is above its
	* destination, i.e. it is being moved "left".
	*/
	public function testMoveChildToNthChild_Left() {
		$n = rand(1,NI_CHILDREN-2);
		// Get a node positioned above $n
		$node = $this->loadNode(1,NI_LEVELS-1,$n+1);

		$target = $node->parent()->find();
		$descendants = $target->descendants()->count();

		$this->assertNotEquals($node->name,$target->getChild($n)->name);
		$this->assertEquals($node->descendants()->count()+1,$node->moveToNthChild($target,$n));

		$this->assertEquals($descendants,$target->descendants()->count());
		$this->assertEquals($node->name,$target->child($n)->find()->name);
		$this->assertKeyEquals($node,$target->getChild($n));
	}

	/**
	* Moves the owner node to become the nth child of the target node where the
	* owner is a child of the target and its current position is below its
	* destination i.e. it is being moved "right".
	*/
	public function testMoveChildToNthChild_Right() {
		$n = rand(3,NI_CHILDREN);
		// Get a node positioned below $n
		$node = $this->loadNode(1,NI_LEVELS-1,1,$n-1);

		$target = $node->parent()->find();
		$descendants = $target->descendants()->count();

		$this->assertNotEquals($node->name,$target->child($n)->find()->name);
		$this->assertEquals($node->descendants()->count()+1,$node->moveToNthChild($target,$n));

		$this->assertEquals($descendants,$target->descendants()->count());
		$this->assertEquals($node->name,$target->child($n)->find()->name);
		$this->assertKeyEquals($node,$target->getChild($n));
	}

	/**
	* Moves the owner node to become the last child of the target node where the
	* node is originally a descendant of the target node
	*/
	public function testMoveToLastChild_isDescendant() {
		// Get a node and target such that they are not the same node, target is not
		// a descendant of node and node is a grandchild of target or lower
		$target = $this->loadNode(1,NI_LEVELS-2);
		$node = NestedInterval::model()->find('name LIKE "'.$target->name.'.'.rand(1,NI_CHILDREN).'.'.rand(1,NI_CHILDREN).'%"');

		$parent = $node->parent()->find();
		$nodeDescendants = $node->descendants()->count();
		$parentDescendants = $parent->descendants()->count();
		$targetChildren = $target->children()->count();
		$targetDescendants = $target->descendants()->count();

		if (!$target->isLeaf)
			$this->assertNotEquals($node->name,$target->lastChild->name);

		$moved = $node->moveToLastChild($target);

		$this->assertEquals($nodeDescendants+1,$moved);

		// refresh nodes to get new node key after the move
		$parent = NestedInterval::model()->findByPk($parent->id);
		$target = NestedInterval::model()->findByPk($target->id);

		$this->assertEquals(NI_CHILDREN-1,$parent->children()->count());
		$this->assertEquals($parentDescendants-($parent->isRoot?0:$moved),$parent->descendants()->count());
		$this->assertEquals($targetDescendants,$target->descendants()->count());
		$this->assertEquals($node->name,$target->lastChild()->find()->name);
		$this->assertKeyEquals($node,$target->lastChild);
	}

	/**
	* Moves the owner node to become the last child of the target node where the
	* node is not originally a descendant of the target node
	*/
	public function testMoveToLastChild_notDescendant() {
		// Get a node and target such that node is not a descendant of target
		// and target is not a descendant of node
		do {
			$target = $this->loadNode(1,NI_LEVELS);
			$node =  $this->loadNode(1,NI_LEVELS);
		} while ($target->isDescendantOf($node) || $node->isDescendantOf($target) || $target->equals($node));

		$parent = $node->parent()->find();
		$nodeDescendants = $node->descendants()->count();
		$parentDescendants = $parent->descendants()->count();
		$targetChildren = $target->children()->count();
		$targetDescendants = $target->descendants()->count();

		if (!$target->isLeaf)
			$this->assertNotEquals($node->name,$target->lastChild->name);

		$moved = $node->moveToLastChild($target);

		// refresh nodes to get new node key after the move
		$parent = NestedInterval::model()->findByPk($parent->id);
		$target = NestedInterval::model()->findByPk($target->id);

		$this->assertEquals($nodeDescendants+1,$moved);

		$this->assertEquals(NI_CHILDREN-1,$parent->children()->count());
		$this->assertEquals($parentDescendants-($parent->isRoot?0:$moved),$parent->descendants()->count());
		$this->assertEquals($targetDescendants+($target->isRoot?0:$moved),$target->descendants()->count());
		$this->assertEquals($node->name,$target->lastChild()->find()->name);
		$this->assertKeyEquals($node,$target->lastChild);
	}

	/**
	* Moves the owner node to become the last child of the target node where the
	* node is originally a descendant of the target node
	*/
	public function testMoveToFirstChild_isDescendant() {
		// Get a node and target such that they are not the same node, target is not
		// a descendant of node, and node is a grandchild of target or lower
		$target = $this->loadNode(0,NI_LEVELS-2);
		$node = NestedInterval::model()->find('name LIKE "'.$target->name.'.'.rand(1,NI_CHILDREN).'.'.rand(1,NI_CHILDREN).'%"');

		$parent = $node->parent()->find();
		$nodeDescendants = $node->descendants()->count();
		$parentDescendants = $parent->descendants()->count();
		$targetChildren = $target->children()->count();
		$targetDescendants = $target->descendants()->count();

		if (!$target->isLeaf)
			$this->assertNotEquals($node->name,$target->firstChild->name);

		$moved = $node->moveToFirstChild($target);

		$this->assertEquals($nodeDescendants+1,$moved);

		// refresh nodes to get new node key after the move
		$parent = NestedInterval::model()->findByPk($parent->id);
		$target = NestedInterval::model()->findByPk($target->id);

		$this->assertEquals(NI_CHILDREN-1,$parent->children()->count());
		$this->assertEquals($parentDescendants-($parent->isRoot?0:$moved),$parent->descendants()->count());
		$this->assertEquals($targetDescendants,$target->descendants()->count());
		$this->assertEquals($node->name,$target->firstChild()->find()->name);
		$this->assertKeyEquals($node,$target->firstChild);
	}

	/**
	* Moves the owner node to become the first child of the target node where the
	* node is not originally a descendant of the target node
	*/
	public function testMoveToFirstChild_notDescendant() {
		// Get a node and target such that node is not a descendant of target
		// and target is not a descendant of node
		do {
			$target = $this->loadNode(1,NI_LEVELS);
			$node =  $this->loadNode(1,NI_LEVELS);
		} while ($target->isDescendantOf($node) || $node->isDescendantOf($target) || $target->equals($node));

		$parent = $node->parent()->find();
		$nodeDescendants = $node->descendants()->count();
		$parentDescendants = $parent->descendants()->count();
		$targetChildren = $target->children()->count();
		$targetDescendants = $target->descendants()->count();

		if (!$target->isLeaf)
			$this->assertNotEquals($node->name,$target->firstChild->name);

		$moved = $node->moveToFirstChild($target);

		$this->assertEquals($nodeDescendants+1,$moved);

		// refresh nodes to get new node key after the move
		$parent = NestedInterval::model()->findByPk($parent->id);
		$target = NestedInterval::model()->findByPk($target->id);

		$this->assertEquals(NI_CHILDREN-1,$parent->children()->count());
		$this->assertEquals($parentDescendants-($parent->isRoot && $target->isDescendantOf($parent)?0:$moved),$parent->descendants()->count());
		$this->assertEquals($targetDescendants+($target->isRoot && $node->isDescendantOf($target)?0:$moved),$target->descendants()->count());
		$this->assertEquals($node->name,$target->firstChild()->find()->name);
		$this->assertKeyEquals($node,$target->firstChild);
	}

	/**
	* Moves the owner node to become the nth child of the target node where the
	* node being moved is not a descendant of the target node
	*/
	public function testMoveToNthChild_NotDescendant() {
		$n = rand(1,NI_CHILDREN-2);
		// Get a node and target such that node is not a descendant of target
		// and target is not a desscendant of node
		do {
			$target = $this->loadNode(1,NI_LEVELS);
			$node =  $this->loadNode(1,NI_LEVELS);
		} while ($target->isDescendantOf($node) || $node->isDescendantOf($target) || $target->isLeaf || $target->equals($node));

		$parent = $node->parent()->find();
		$nodeDescendants = $node->descendants()->count();
		$parentDescendants = $parent->descendants()->count();
		$targetChildren = $target->children()->count();
		$targetDescendants = $target->descendants()->count();

		$this->assertNotEquals($node->name,$target->getChild($n)->name);

		$moved = $node->moveToNthChild($target, $n);

		$this->assertEquals($nodeDescendants+1,$moved);

		// refresh nodes to get new node key after the move
		$parent = NestedInterval::model()->findByPk($parent->id);
		$target = NestedInterval::model()->findByPk($target->id);

		$this->assertEquals(NI_CHILDREN-1,$parent->children()->count());
		$this->assertEquals($parentDescendants-($parent->isRoot?0:$moved),$parent->descendants()->count());
		$this->assertEquals($targetDescendants+($target->isRoot?0:$moved),$target->descendants()->count());
		$this->assertEquals($node->name,$target->child($n)->find()->name);
		$this->assertKeyEquals($node,$target->getChild($n));
	}

	/**
	* Moves the owner node to become the nth child of the target node where the
	* node being moved is a descendant of the target node
	*/
	public function testMoveToNthChild_IsDescendant() {
		$n = rand(1,NI_CHILDREN-2);
		// Get a node and target such that they are not the same node, target is not
		// a descendant of node, and node is a grandchild of target or lower
		$target = $this->loadNode(0,NI_LEVELS-2);
		$node = NestedInterval::model()->find('name LIKE "'.$target->name.'.'.rand(1,NI_CHILDREN).'.'.rand(1,NI_CHILDREN).'%"');

		$parent = $node->parent()->find();
		$nodeDescendants = $node->descendants()->count();
		$parentDescendants = $parent->descendants()->count();
		$targetChildren = $target->children()->count();
		$targetDescendants = $target->descendants()->count();

		if (!$target->isLeaf)
			$this->assertNotEquals($node->name,$target->getChild($n)->name);

		$moved = $node->moveToNthChild($target, $n);

		$this->assertEquals($nodeDescendants+1,$moved);

		// refresh the parent node to get its new node key after the move
		$parent = NestedInterval::model()->findByPk($parent->id);

		$this->assertEquals(NI_CHILDREN-1,$parent->children()->count());
		$this->assertEquals($parentDescendants-($parent->isRoot?0:$moved),$parent->descendants()->count());
		$this->assertEquals($targetDescendants,$target->descendants()->count());
		$this->assertEquals($node->name,$target->child($n)->find()->name);
		$this->assertKeyEquals($node,$target->getChild($n));
	}

	// A "Sibling" move is the same as a "Child" move except that the target is
	// a sibling node. The entry methods "translate" the move to a child move;
	// so there is no need to test for node is/is not a sibling, is/is not a
	// descendant, etc. as the moveChild tests have done that - these just test
	// the entry methods translate correctly.
	/**
	* Moves the owner node to become the last sibling of the target node
	*/
	public function testMoveToLastSibling() {
		// Get a node and target such that target is not a descendant or sibling of node
		do {
			$node =  $this->loadNode(1,NI_LEVELS);
			$target = $this->loadNode(1,NI_LEVELS);
		} while ($target->isDescendantOf($node) || $target->isSiblingOf($node));

		$nodeDescendants = $node->descendants()->count();
		$targetSiblings = $target->siblings()->count();

		$this->assertNotEquals($node->name,$target->lastSibling->name);

		$moved = $node->moveToLastSibling($target);

		$this->assertEquals($nodeDescendants+1,$moved);
		$this->assertEquals($nodeDescendants,$node->descendants()->count());
		$this->assertEquals($targetSiblings+1,$target->siblings()->count());
		$this->assertEquals($node->name,$target->lastSibling()->find()->name);
		$this->assertKeyEquals($node,$target->lastSibling);
	}

	/**
	* Moves the owner node to become the first sibling of the target node
	*/
	public function testMoveToFirstSibling() {
		// Get a node and target such that target is not a descendant or sibling of node
		do {
			$target = $this->loadNode(1,NI_LEVELS);
			$node =  $this->loadNode(1,NI_LEVELS);
		} while ($target->isDescendantOf($node) || $target->isSiblingOf($node));

		$nodeDescendants = $node->descendants()->count();
		$targetSiblings = $target->siblings()->count();

		$this->assertNotEquals($node->name,$target->firstSibling->name);

		$moved = $node->moveToFirstSibling($target);

		$this->assertEquals($nodeDescendants+1,$moved);
		$this->assertEquals($nodeDescendants,$node->descendants()->count());
		$this->assertEquals($targetSiblings+1,$target->siblings()->count());
		$this->assertEquals($node->name,$target->firstSibling()->find()->name);
		$this->assertKeyEquals($node,$target->firstSibling);
	}

	/**
	* Moves the owner node to become the nth sibling of the target node
	*/
	public function testMoveToNthSibling() {
		$n = rand(1,NI_CHILDREN);
		// Get a node and target such that target is not a descendant or sibling of node
		do {
			$target = $this->loadNode(1,NI_LEVELS);
			$node =  $this->loadNode(1,NI_LEVELS);
		} while ($target->isDescendantOf($node) || $target->isSiblingOf($node));

		$nodeDescendants = $node->descendants()->count();
		$targetSiblings = $target->siblings()->count();

		$this->assertNotEquals($node->name,$target->getSibling($n)->name);

		$moved = $node->moveToNthSibling($target, $n);

		$this->assertEquals($nodeDescendants+1,$moved);
		$this->assertEquals($nodeDescendants,$node->descendants()->count());
		$this->assertEquals($targetSiblings+1,$target->siblings()->count());
		$this->assertEquals($node->name,$target->sibling($n)->find()->name);
		$this->assertKeyEquals($node,$target->getSibling($n));
	}

	/**
	* Moves the owner node to become the next sibling of the target node
	*/
	public function testMoveAfter() {
		// Get a node and target such that target is not a descendant or sibling of node
		do {
			$target = $this->loadNode(1,NI_LEVELS);
			$node =  $this->loadNode(1,NI_LEVELS);
		} while ($target->isDescendantOf($node) || $target->isSiblingOf($node));

		$nodeDescendants = $node->descendants()->count();
		$targetSiblings = $target->siblings()->count();

		if ($target->sibling($target->position + 1)->find()!==null) // it could be the last
			$this->assertNotEquals($node->name,$target->getSibling($target->position + 1)->name);

		$moved = $node->moveAfter($target);

		$this->assertEquals($nodeDescendants+1,$moved);
		$this->assertEquals($nodeDescendants,$node->descendants()->count());
		$this->assertEquals($targetSiblings+1,$target->siblings()->count());
		$this->assertEquals($node->name,$target->sibling($target->getPosition() + 1)->find()->name);
		$this->assertKeyEquals($node,$target->getSibling($target->position + 1));
	}

	/**
	* Moves the owner node to become the previous sibling of the target node
	*/
	public function testMoveBefore() {
		// Get a node and target such that target is not a descendant or sibling of node
		do {
			$target = $this->loadNode(1,NI_LEVELS);
			$node =  $this->loadNode(1,NI_LEVELS);
		} while ($target->isDescendantOf($node) || $target->isSiblingOf($node));

		// Get a node and target such that node is not a descendant of target
		// and target is not a desscendant of node
		do {
			$target = $this->loadNode(1,NI_LEVELS);
			$node =  $this->loadNode(1,NI_LEVELS);
		} while ($target->isDescendantOf($node));

		$nodeDescendants = $node->descendants()->count();
		$targetSiblings = $target->siblings()->count();

		$this->assertNotEquals($node->name,$target->getSibling($target->position)->name);

		$moved = $node->moveBefore($target);

		$this->assertEquals($nodeDescendants+1,$moved);
		$this->assertEquals($nodeDescendants,$node->descendants()->count());
		$this->assertEquals($targetSiblings+1,$target->siblings()->count());
		$this->assertEquals($node->name,$target->sibling($target->getPosition())->find()->name);
		$this->assertKeyEquals($node,$target->getSibling($target->position));
	}

	/**
	* Moves the owner node to become a new root node
	*/
	public function testMoveToRoot() {
		$node =  $this->loadNode(1,NI_LEVELS);
		$oldRoot = $node->root;
		$oldRootDescendants = $oldRoot->descendants()->count();
		$descendants = $node->descendants()->count();
		$roots = $node->roots()->count();
		$moved = $node->moveToRoot();
		$this->assertEquals($roots+1,$node->roots()->count());
		$root = array_pop($node->roots()->findAll());
		$this->assertEquals($oldRootDescendants-$moved,$oldRoot->descendants()->count());
		$this->assertEquals($node->name,$root->name);
		$this->assertEquals($moved-1,$root->descendants()->count());
		$this->assertKeyEquals($node,$root);
	}

	/**
	* Assert that the two nodes have the same key, i.e. are in the same position
	* Used to check nodes have updated following move operations
	*/
	private function assertKeyEquals($n0,$n1) {
		$this->assertEquals($n0->nv, $n1->nv);
		$this->assertEquals($n0->dv, $n1->dv);
		$this->assertEquals($n0->snv,$n1->snv);
		$this->assertEquals($n0->sdv,$n1->sdv);
	}

	// TEST EXCEPTIONS
	/**
	* call save
	* @expectedException NestedIntervalException
	* @expectedExceptionMessage Do not call NestedInterval::save() to add a record when NestedIntervalBehavior is attached; use NestedIntervalBehavior::saveNode()
	*/
	public function testSaveException() {
		$node = new NestedInterval();
		$node->name = 'Save';
		$node->save(false);
	}

	/**
	* call delete
	* @expectedException NestedIntervalException
	* @expectedExceptionMessage Do not call NestedInterval::delete() to delete a record when NestedIntervalBehavior is attached; use NestedIntervalBehavior::deleteNode()
	*/
	public function testDeleteException() {
		$this->loadNode()->delete();
	}

	/**
	* add a node that is not new
	* @expectedException NestedIntervalException
	* @expectedExceptionMessage Cannot add node; it is not a new node
	*/
	public function testAddNotNewException() {
		$this->loadNode()->addAsChildOf($this->loadNode());
	}

	/**
	* move a root node
	* @expectedException NestedIntervalException
	* @expectedExceptionMessage Cannot move node; it is a root node
	*/
	public function testMoveRootException() {
		$this->loadNode(0,0)->moveBefore($this->loadNode(1,NI_LEVELS));
	}

	/**
	* move a new node
	* @expectedException NestedIntervalException
	* @expectedExceptionMessage Cannot move node; it is a new node
	*/
	public function testMoveNewException() {
		$node = new NestedInterval();
		$node->moveBefore($this->loadNode(1,NI_LEVELS));
	}

	/**
	* move to self
	* @expectedException NestedIntervalException
	* @expectedExceptionMessage Cannot move node; target===node
	*/
	public function testMoveToSelfException() {
		$node = $this->loadNode(1,NI_LEVELS);
		$target = clone $node;
		$node->moveBefore($target);
	}

	/**
	* move to descendant
	* @expectedException NestedIntervalException
	* @expectedExceptionMessage Cannot move node; target node is a descendant node
	*/
	public function testMoveToDescendantException() {
		$node = $this->loadNode(1,NI_LEVELS);
		do {
			$target = $this->loadNode(1,NI_LEVELS);
		} while (!$target->isDescendantOf($node));

		$node->moveBefore($target);
	}

	/**
	* move a deleted node
	* @expectedException NestedIntervalException
	* @expectedExceptionMessage Cannot move node; it has been deleted
	*/
	public function testMoveDeletedNodeException() {
		$node =	$this->loadNode(1,1);
		$node->deleteNode();
		$node->moveBefore($this->loadNode(2,NI_LEVELS));
	}

	/**
	* move with a deleted target
	* @expectedException NestedIntervalException
	* @expectedExceptionMessage Cannot move node; target node has been deleted
	*/
	public function testMoveDeletedTargetException() {
		$target = $this->loadNode(2,NI_LEVELS);
		$target->deleteNode();
		$this->loadNode(1,1)->moveBefore($target);
	}

	/**
	* Loads a random node from the tree.
	* The level and child number can be constrained so that tests for ancestors,
	* descendants, and siblings will run.
	* @param int minimum level the node must be at
	* @param int maximum level the node must be at
	* @param int minimum child node at the deepest level
	* @param int maximum child node at the deepest level
	*/
	private function loadNode($minLevel=0, $maxLevel=NI_LEVELS, $minChild=1, $maxChild=NI_CHILDREN) {
		$tree = rand(1,NI_TREES);
		$level = rand($minLevel,$maxLevel);

		$node = array(
			'nv' => $tree,
			'dv' => 1,
			'snv' => $tree + 1,
			'sdv' => 1
		);

		if ($level) {
			$node = $this->calcChild($node,1,$level,$minChild,$maxChild);
		}

		return NestedInterval::model()->find("nv={$node['nv']} AND dv={$node['dv']}");
	}
	/**
	* Returns the nv,dv,snv,sdv for a random child node at the given depth
	*/
	private function calcChild($parent,$level,$depth,$minChild,$maxChild) {
		if ($level===$depth) {
			$child = rand($minChild,$maxChild);
			return array(
				'nv' => $parent['nv']+$child*$parent['snv'],
				'dv' => $parent['dv']+$child*$parent['sdv']
			);
		}
		$child = rand(1,NI_CHILDREN);
		$node = array(
				'nv' => $parent['nv']+$child*$parent['snv'],
				'dv' => $parent['dv']+$child*$parent['sdv'],
				'snv' => $parent['nv']+($child+1)*$parent['snv'],
				'sdv' => $parent['dv']+($child+1)*$parent['sdv']
		);
		return $this->calcChild($node,$level+1,$depth,$minChild,$maxChild);
	}
}