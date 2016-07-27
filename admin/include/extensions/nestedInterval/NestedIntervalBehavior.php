<?php
/* SVN FILE: $Id: NestedIntervalBehavior.php 199 2011-12-14 10:13:49Z mole1230 $*/
/**
* Nested Interval Behavior class file
* Hierarchical Data Management in Relational Databases using Nested Interval Node Key Encoding
*
* @copyright	Copyright &copy; 2011 PBM Web Development - All Rights Reserved
* @version		$Revision: 5 $
* @license		BSD License (see documentation)
*/
/**
* Nested Interval Behavior class.
* Implementation of @{link http://arxiv.org/PS_cache/arxiv/pdf/0806/0806.3115v1.pdf "Using rational numbers to key nested sets" by Dan Hazel}.
*/
class NestedIntervalBehavior extends CActiveRecordBehavior {
	const SIBLINGS_BEFORE = -1;
	const SIBLINGS_EX     =  0;
	const SIBLINGS_AFTER  =  1;
	const SIBLINGS_ALL    =  2;

	/**
	* @property string Attribute that stores the node denominator
	*/
	public $dv = 'dv';
	/**
	* @property string Attribute that stores the node numerator
	*/
	public $nv = 'nv';
	/**
	* @property string Attribute that stores the next sibling denominator
	*/
	public $sdv = 'sdv';
	/**
	* @property string Attribute that stores the next sibling numerator
	*/
	public $snv = 'snv';

	/**
	* @property string Name of child related models
	* @see createDescendantTree
	*/
	public $childRelatedRecords = 'childNodes';
	/**
	* @property string Name of the parent related model
	* @see createAncestorPath()
	*/
	public $parentRelatedRecord = 'parentNode';

	/**
	* @var boolean TRUE if this node has been deleted, FALSE if not
	*/
	private $_isDeleted = false;
	/**
	* @var array The transformation matrix used to move sub-trees
	*/
	private $_tm = array(array(1,0),array(0,1));
	/**
	* @var boolean TRUE when a db operation is originated by $this, FALSE when a
	* db operation is originated by the owner
	*/
	private $_this = false;
	/**
	* @var integer The level of the owner.
	* @see getLevel()
	*/
	private $_level = -1;

	/**
	* Creates a path of ancestors under the owner node.
	* After calling this method the owner will have its parent as related record,
	* the parent will have its parent as related record, and so on.
	* @param integer the number of levels. If NULL all ancestors are returned.
	* @return void
	*/
	public function createAncestorPath() {
		$ancestors = $this->ancestors()->findAll();
		$parent = $this->getOwner();
		foreach ($ancestors as $ancestor) {
			$parent->addRelatedRecord($this->parentRelatedRecord,$ancestor,false);
			$parent = $ancestor;
		}
	}

	/**
	* Creates a tree of descendants under the owner node.
	* After calling this method the owner will have its children as related records,
	* each of the children will have their children as related records, and so on.
	* @param integer the number of levels. If NULL all descendants are processed.
	* @return void
	*/
	public function createDescendantTree() {
		$this->createTree($this->getOwner(), $this->descendants()->findAll());
	}

	/**#@+
	* Getters
	*/
	/**
	* Returns the ancestors of the owner
	* @return array The ancestors of the owner.
	*/
	public function getAncestors() {
		return $this->ancestors()->findAll();
	}
	/**
	* Returns the number of ancestors of the owner
	* @return integer The number of ancestors.
	*/
	public function getAncestorCount() {
		return $this->ancestors()->count();
	}

	/**
	* Returns the nth child of the owner
	* @return CActiveRecord The nth child of the owner. NULL if it does not exist.
	*/
	public function getChild($n) {
		return $this->child($n)->find();
	}

	/**
	* Returns the children of the owner
	* @return array Children of the owner. An empty array is returned if the owner
	* has no children, i.e. is a leaf node.
	*/
	public function getChildren() {
		return $this->children()->findAll();
	}

	/**
	* Returns the number of children the owner has.
	* @return integer The number of children of the owner.
	*/
	public function getChildCount() {
		return $this->children()->count();
	}

	/**
	* Returns the descendants of the owner.
	* @return array The descendants of the owner.
	*/
	public function getDescendants() {
		return $this->descendants()->findAll();
	}

	/**
	* Returns the number of descendants the owner has.
	* @return integer The number of descendants of the owner.
	*/
	public function getDescendantCount() {
		return $this->descendants()->count();
	}

	/**
	* Returns a value indicating if this node has been deleted.
	* @return boolean TRUE if this node has been deleted, FALSE if not
	*/
	public function getIsDeleted() {
		return $this->_isDeleted;
	}

	/**
	* Returns the first child of the owner.
	* @return CActiveRecord The first child of the owner; NULL if the owner has no children
	*/
	public function getFirstChild() {
		return $this->firstChild()->find();
	}

	/**
	* Returns the last child of the owner.
	* @return CActiveRecord The last child of the owner; NULL if the owner has no children
	*/
	public function getLastChild() {
		return $this->lastChild()->find();
	}

	/**
	* Returns the first sibling of the owner.
	* @return CActiveRecord The first sibling of the owner
	*/
	public function getFirstSibling() {
		return $this->firstSibling()->find();
	}

	/**
	* Returns the last sibling of the owner.
	* @return CActiveRecord The last sibling of the owner
	*/
	public function getLastSibling() {
		return $this->lastSibling()->find();
	}

	/**
	* Returns the next sibling of the owner.
	* @return CActiveRecord The next sibling of the owner; NULL if the owner is the last sibling
	*/
	public function getNextSibling() {
		return $this->nextSibling()->find();
	}

	/**
	* Returns the previous sibling of the owner.
	* @return CActiveRecord The previous sibling of the owner; NULL if the owner is the first sibling
	*/
	public function getPreviousSibling() {
		return $this->previousSibling()->find();
	}

	/**
	* Returns the level of the owner.
	* @return integer The level of the owner.
	*/
	public function getLevel() {
		if ($this->_level===-1) {
			$nv = $snv = 0;
			$dv = $sdv = 1;
			$owner = $this->getOwner();
			$numerator = $owner->nv;
			$denominator = $owner->dv;
			while ($numerator > 0 && $denominator > 0) {
				$this->_level += 1;
				$div = $numerator / $denominator;
				$mod = $numerator % $denominator;
				$nv = $nv + $div * $snv;
				$dv = $dv + $div * $sdv;
				$snv = $nv + $snv;
				$sdv = $dv + $sdv;
				$numerator = $mod;
				if ($numerator!==0) {
					$denominator = $denominator % $mod;
					if ($denominator===0)
						$denominator = 1;
				}
			}
		}
		return $this->_level;
	}

	/**
	* Returns the parent of the owner
	* @return array The parent of the owner. Returns NULL is the owner is a root item.
	*/
	public function getParent() {
		return $this->getOwner()->parent()->find();
	}

	/**
	* Returns the position of the owner relative to its siblings; i.e. 1=the 1st
	* child/root, 2=the 2nd child/root, ..., n=nth child/root
	* @return integer The position of the owner relative to its siblings
	*/
	public function getPosition() {
		$owner = $this->getOwner();
		if ($owner->{$this->dv}===1) // root node
			return $owner->{$this->nv};
		$psnv = $owner->{$this->snv} - $owner->{$this->nv};
		return ($owner->{$this->nv} - $owner->{$this->nv}%$psnv)/$psnv;
	}

	/**
	* Returns the nth root node. If $n==0 the root node of the owner is returned.
	* @return CActiveRecord The nth root, or root of the owner.
	*/
	public function getRoot($n=0) {
		return $this->root($n)->find();
	}

	/**
	* Returns the nth sibling of the owner
	* @return CActiveRecord The nth sibling of the owner. NULL if it does not exist.
	*/
	public function getSibling($n) {
		return $this->sibling($n)->find();
	}

	/**
	* Returns the siblings of the owner.
	* @param integer Which siblings to return:
	* + self::SIBLINGS_EX - all siblings excluding the owner (default)
	* + self::SIBLINGS_ALL - all siblings including the owner - equivalent to the parent's children
	* + self::SIBLINGS_AFTER - later siblings
	* + self::SIBLINGS_BEFORE - earlier siblings
	* @return array The siblings of the owner.
	*/
	public function getSiblings($which=self::SIBLINGS_EX) {
		return $this->siblings($which)->findAll();
	}

	/**
	* Returns the number of siblings the owner has.
	* @param integer Which siblings to count:
	* + self::SIBLINGS_EX - all siblings excluding the owner (default)
	* + self::SIBLINGS_ALL - all siblings including the owner - equivalent to the parent's children
	* + self::SIBLINGS_AFTER - later siblings
	* + self::SIBLINGS_BEFORE - earlier siblings
	* @return integer The number of siblings of the owner.
	*/
	public function getSiblingCount($which=self::SIBLINGS_EX) {
		return $this->siblings($which)->count();
	}
	/**#@-*/

	/**#@+
	* Named scopes
	*/
	/**
	* Named scope to find the ancestors of the owner.
	* The default order is from the parent up through the tree.
	* @return CActiveRecord The owner
	*/
	public function ancestors() {
		$owner = $this->getOwner();
		$db = $owner->getDbConnection();
		$alias = $owner->getTableAlias();

		$k = $owner->{$this->nv}.' * '.$db->quoteColumnName("$alias.".$this->dv).' * '.$db->quoteColumnName("$alias.".$this->sdv).' / '.$owner->{$this->dv}.' - '.$db->quoteColumnName("$alias.".$this->nv).' * '.$db->quoteColumnName("$alias.".$this->sdv);
		$owner->getDbCriteria()->mergeWith(array(
			'condition'=>"$k>0 AND $k<1",
			'order'=>$db->quoteColumnName("$alias.".$this->nv).' DESC'
		));
		return $owner;
	}

	/**
	* Named scope to find the children of a node.
	* The dataset is ordered in node key ascending order by default
	* @return CActiveRecord The owner
	*/
	public function children() {
		$owner = $this->getOwner();
		$db = $owner->getDbConnection();
		$alias = $owner->getTableAlias();

		$owner->getDbCriteria()->mergeWith(array(
			'condition'=>$db->quoteColumnName("$alias.".$this->snv)
				.'-'.$db->quoteColumnName("$alias.".$this->nv)
				.'='.$owner->{$this->snv}
				.' AND '
				.$db->quoteColumnName("$alias.".$this->sdv)
				.'-'.$db->quoteColumnName("$alias.".$this->dv)
				.'='.$owner->{$this->sdv},
			'order'=>$db->quoteColumnName("$alias.".$this->nv)
		));
		return $owner;
	}

	/**
	* Named scope to find the nth child of a node.
	* @param integer The child number to return; $n==0 returns the last child.
	* NULL will be returned if $n>lastChild
	* @return CActiveRecord The owner
	*/
	public function child($n=1) {
		if ($n==0)
			return $this->lastChild();

		$owner = $this->getOwner();
		$db = $owner->getDbConnection();
		$alias = $owner->getTableAlias();

		$owner->getDbCriteria()->mergeWith(array(
			'condition'=>$db->quoteColumnName("$alias.".$this->nv)
				.'='.($owner->{$this->nv} + $n * $owner->{$this->snv})
				.' AND '
				.$db->quoteColumnName("$alias.".$this->dv)
				.'='.($owner->{$this->dv} + $n * $owner->{$this->sdv})
		));
		return $owner;
	}

	/**
	* Named scope to find the descendants of the owner.
	* The dataset is ordered in node key ascending order unless overridden.
	* @return CActiveRecord The owner
	*/
	public function descendants() {
		$owner = $this->getOwner();
		$db = $owner->getDbConnection();
		$criteria = $owner->getDbCriteria();
		$alias = $owner->getTableAlias();

		$k = $db->quoteColumnName("$alias.".$this->nv).'*'.($owner->{$this->dv} * $owner->{$this->sdv}).'/'.$db->quoteColumnName("$alias.".$this->dv).'-'.($owner->{$this->nv} * $owner->{$this->sdv});
		$criteria->mergeWith(array(
			'condition'=>"$k>0 AND $k<1",
			'order'=>$db->quoteColumnName("$alias.".$this->nv).'/'.$db->quoteColumnName("$alias.".$this->dv)
		));

		return $owner;
	}

	/**
	* Named scope to find the first child of a node.
	* @return CActiveRecord The owner
	*/
	public function firstChild() {
		return $this->child();
	}

	/**
	* Named scope to find the first child of the parent of the curent node.
	* @return CActiveRecord The owner
	*/
	public function firstSibling() {
		return $this->sibling();
	}

	/**
	* Named scope to find the last child of a node.
	* @return CActiveRecord The owner
	*/
	public function lastChild() {
		$owner = $this->getOwner();
		$db = $owner->getDbConnection();
		$alias = $owner->getTableAlias();

		$owner->getDbCriteria()->mergeWith(array(
			'condition'=>$db->quoteColumnName("$alias.".$this->nv).'=(SELECT MAX('
				.$db->quoteColumnName($owner->tableName().'.'.$this->nv)
				.') FROM '.$db->quoteTableName($owner->tableName()).' WHERE '
				.$db->quoteColumnName($owner->tableName().'.'.$this->snv)
			.'-'.$db->quoteColumnName($owner->tableName().'.'.$this->nv)
			.'='.$owner->{$this->snv}
			.' AND '
			.$db->quoteColumnName($owner->tableName().'.'.$this->sdv)
			.'-'.$db->quoteColumnName($owner->tableName().'.'.$this->dv)
			.'='.$owner->{$this->sdv}
			.') AND '
			.$db->quoteColumnName("$alias.".$this->snv)
			.'-'.$db->quoteColumnName("$alias.".$this->nv)
			.'='.$owner->{$this->snv}
			.' AND '
			.$db->quoteColumnName("$alias.".$this->sdv)
			.'-'.$db->quoteColumnName("$alias.".$this->dv)
			.'='.$owner->{$this->sdv}
		));
		return $owner;
	}

	/**
	* Named scope to find the last child of the parent of the owner node.
	* @return CActiveRecord The owner
	*/
	public function lastSibling() {
		$owner = $this->getOwner();
		$db = $owner->getDbConnection();
		$alias = $owner->getTableAlias();

		$psnv = $owner->{$this->snv} - $owner->{$this->nv};
		$psdv = $owner->{$this->sdv} - $owner->{$this->dv};

		$owner->getDbCriteria()->mergeWith(array(
			'condition'=>$db->quoteColumnName("$alias.".$this->nv).'=(SELECT MAX('
				.$db->quoteColumnName($owner->tableName().'.'.$this->nv)
				.') FROM '.$db->quoteTableName($owner->tableName()).' WHERE '
				.$db->quoteColumnName($owner->tableName().'.'.$this->snv)
			.'-'.$db->quoteColumnName($owner->tableName().'.'.$this->nv)
			.'='.$psnv
			.' AND '
			.$db->quoteColumnName($owner->tableName().'.'.$this->sdv)
			.'-'.$db->quoteColumnName($owner->tableName().'.'.$this->dv)
			.'='.$psdv
			.') AND '
			.$db->quoteColumnName("$alias.".$this->snv)
			.'-'.$db->quoteColumnName("$alias.".$this->nv)
			.'='.$psnv
			.' AND '
			.$db->quoteColumnName("$alias.".$this->sdv)
			.'-'.$db->quoteColumnName("$alias.".$this->dv)
			.'='.$psdv
		));
		return $owner;
	}

	/**
	* Named scope to find the parent of the owner.
	* @return CActiveRecord The owner
	*/
	public function parent() {
		$owner = $this->getOwner();
		$db = $owner->getDbConnection();
		$alias = $owner->getTableAlias();

		$owner->getDbCriteria()->mergeWith(array(
			'condition'=>$db->quoteColumnName("$alias.".$this->snv).'='
				.($owner->{$this->snv} - $owner->{$this->nv}).' AND '
				.$db->quoteColumnName("$alias.".$this->sdv).'='
				.($owner->{$this->sdv} - $owner->{$this->dv})
		));
		return $owner;
	}

	/**
	* Named scope to find a root node.
	* If $n is a +ve integer the $nth root is found; anything else, the root node
	* of the current node is found
	* @param integer $n The root node number to find
	* @return CActiveRecord The owner
	*/
	public function root($n=0) {
		$owner = $this->getOwner();
		$db = $owner->getDbConnection();
		$alias = $owner->getTableAlias();

		if (is_integer($n) && $n>0)
			$condition = $db->quoteColumnName("$alias.".$this->nv)."=$n";
		else {
			$k = $owner->{$this->nv}.' * '.$db->quoteColumnName("$alias.".$this->dv).' * '.$db->quoteColumnName("$alias.".$this->sdv).' / '.$owner->{$this->dv}.' - '.$db->quoteColumnName("$alias.".$this->nv).' * '.$db->quoteColumnName("$alias.".$this->sdv);
			$condition = "$k>0 AND $k<1";
		}

		$condition .=' AND '.$db->quoteColumnName("$alias.".$this->dv).'=1';

		$owner->getDbCriteria()->mergeWith(compact('condition'));
		return $owner;
	}

	/**
	* Named scope to find root nodes.
	* @return CActiveRecord The owner
	*/
	public function roots() {
		$owner = $this->getOwner();
		$db = $owner->getDbConnection();
		$alias = $owner->getTableAlias();

		$owner->getDbCriteria()->mergeWith(array(
			'condition'=>$db->quoteColumnName("$alias.".$this->dv).'=1'
		));
		return $owner;
	}

	/**
	* Named scope to find the current node.
	* Merged using OR, so that (for example) $model->descendants()->self()->deleteAll()
	* deletes the current node and its descendants
	* @return CActiveRecord The owner
	*/
	public function self() {
		$owner = $this->getOwner();
		$db = $owner->getDbConnection();
		$alias = $owner->getTableAlias();

		$owner->getDbCriteria()->mergeWith(array(
			'condition'=>$db->quoteColumnName("$alias.".$this->nv).'='.$owner->{$this->nv}
				.' AND '.$db->quoteColumnName("$alias.".$this->dv).'='.$owner->{$this->dv}
		),false);
		return $owner;
	}

	/**
	* Named scope to find the nth child of the parent of the current node.
	* @param integer The sibling number to return; $n==0 returns the last sibling.
	* NULL will be returned if $n>lastSibling
	* @return CActiveRecord The owner
	*/
	public function sibling($n=1) {
		if ($n==0)
			return $this->lastSibling();

		$owner = $this->getOwner();
		$db = $owner->getDbConnection();
		$alias = $owner->getTableAlias();

		list($pnv,$pdv,$psnv,$psdv) = $owner->parentQuadruple();

		$owner->getDbCriteria()->mergeWith(array(
			'condition'=>$db->quoteColumnName("$alias.".$this->nv)
				.'='.($pnv + $n * $psnv)
				.' AND '
				.$db->quoteColumnName("$alias.".$this->dv)
				.'='.($pdv + $n * $psdv)
		));
		return $owner;
	}

	/**
	* Named scope to find the siblings of the owner.
	* Can find all excluding (default), all including, earlier, or later siblings.
	* By default the nodes are returned in sibling order
	* @param integer Which siblings:
	* + self::SIBLINGS_EX - all siblings excluding the owner (default)
	* + self::SIBLINGS_ALL - all siblings including the owner - equivalent to the parent's children
	* + self::SIBLINGS_AFTER - later siblings
	* + self::SIBLINGS_BEFORE - earlier siblings
	* @return CActiveRecord The owner
	*/
	public function siblings($which=self::SIBLINGS_EX) {
		$owner = $this->getOwner();
		$db = $owner->getDbConnection();
		$alias = $owner->getTableAlias();

		switch ($which) {
			case self::SIBLINGS_BEFORE:
				$condition = $db->quoteColumnName("$alias.".$this->nv).'<'.$owner->{$this->nv}.' AND ';
				break;
			case self::SIBLINGS_EX:
				$condition = $db->quoteColumnName("$alias.".$this->nv).'<>'.$owner->{$this->nv}.' AND ';
				break;
			case self::SIBLINGS_AFTER:
				$condition = $db->quoteColumnName("$alias.".$this->nv).'>'.$owner->{$this->nv}.' AND ';
				break;
			default:
				$condition = ''; // all siblings including this one
		}

		$owner->getDbCriteria()->mergeWith(array(
			'condition'=>$condition.$db->quoteColumnName("$alias.".$this->snv)
				.'-'.$db->quoteColumnName("$alias.".$this->nv)
				.'='.($owner->{$this->snv} - $owner->{$this->nv})
				.' AND '.$db->quoteColumnName("$alias.".$this->sdv)
				.'-'.$db->quoteColumnName("$alias.".$this->dv)
				.'='.($owner->{$this->sdv} - $owner->{$this->dv}),
			'order'=>$db->quoteColumnName("$alias.".$this->nv)
		));
		return $owner;
	}

	/**
	* Named scope to find the previous sibling of the owner.
	* @return CActiveRecord The owner
	*/
	public function previousSibling() {
		$owner = $this->getOwner();
		$db = $owner->getDbConnection();
		$alias = $owner->getTableAlias();

		$owner->getDbCriteria()->mergeWith(array(
			'condition'=>$db->quoteColumnName("$alias.".$this->snv).'='.$owner->{$this->nv}
				.' AND '.$db->quoteColumnName("$alias.".$this->sdv).'='.$owner->{$this->dv}
		));
		return $owner;
	}

	/**
	* Named scope to find the next sibling of the owner.
	* @return CActiveRecord The owner
	*/
	public function nextSibling() {
		$owner = $this->getOwner();
		$db = $owner->getDbConnection();
		$alias = $owner->getTableAlias();

		$owner->getDbCriteria()->mergeWith(array(
			'condition'=>$db->quoteColumnName("$alias.".$this->nv).'='.$owner->{$this->snv}
				.' AND '.$db->quoteColumnName("$alias.".$this->dv).'='.$owner->{$this->sdv}
		));
		return $owner;
	}
	/**#@-*/


	/**#@+
	* Add methods
	*/

	/**
	* Adds the owner node as a root node.
	* @param boolean Whether to validate attributes
	* @param array Attributes to save
	* @return boolean TRUE if the node was added, FALSE if not
	*/
	public function addAsRoot($validate=true,$attributes=null) {
		return $this->addNode(null,0,$validate,$attributes);
	}

	/**
	* Adds the target node as a child of the owner node.
	* @param CActiveRecord Parent node
	* @param integer Position to add at: 1 = 1st child, 2 = 2nd child,
	* n = nth child; 0 (default) = last child - this provides the best performance
	* as it minimises reindexing
	* @param boolean Whether to validate attributes
	* @param array Attributes to save
	* @return boolean TRUE if the node was appended, FALSE if not
	*/
	public function addAsChild($target,$n=0,$validate=true,$attributes=null) {
		return $target->addAsChildOf($this->getOwner(),$n,$validate,$attributes);
	}

	/**
	* Adds the owner node as a child of the target node.
	* @param CActiveRecord Target node
	* @param integer Position to add at: 1 = 1st child, 2 = 2nd child,
	* n = nth child; 0 (default) = last child - this provides the best performance
	* as it minimises reindexing
	* @param boolean Whether to validate attributes
	* @param array Attributes to save
	* @return boolean TRUE if the node was appended, FALSE if not
	*/
	public function addAsChildOf($target,$n=0,$validate=true,$attributes=null) {
		return $this->addNode($target,$n,$validate,$attributes);
	}

	/**
	* Appends the target node to the owner node; the target node becomes the
	* last child of the owner node.
	* @param CActiveRecord Target node
	* @param boolean Whether to validate attributes
	* @param array Attributes to save
	* @return boolean TRUE if the node was appended, FALSE if not
	*/
	public function append($target,$validate=true,$attributes=null) {
		return $target->appendTo($this->getOwner(),0,$validate,$attributes);
	}

	/**
	* Appends the owner node to the target node; the owner node becomes the last
	* child of the target node.
	* @param CActiveRecord Target node
	* @param boolean Whether to validate attributes
	* @param array Attributes to save
	* @return boolean TRUE if the node was appended, FALSE if not
	*/
	public function appendTo($target,$validate=true,$attributes=null) {
		return $this->addNode($target,0,$validate,$attributes);
	}

	/**
	* Adds the target node as the next sibling of the owner node.
	* @param CActiveRecord Target node
	* @param boolean Whether to validate attributes
	* @param array Attributes to save
	* @return boolean TRUE if the node was inserted, FALSE if not
	* @see insertAfter()
	*/
	public function after($target,$validate=true,$attributes=null) {
		$owner = $this->getOwner();
		if ($owner->getIsRoot())
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot insert node; owner is a root node'));
		return $target->insertAfter($owner,$validate,$attributes);
	}

	/**
	* Adds the owner node as the next sibling of the target node.
	* @param CActiveRecord Target node
	* @param boolean Whether to validate attributes
	* @param array Attributes to save
	* @return boolean TRUE if the node was inserted, FALSE if not
	*/
	public function insertAfter($target,$validate=true,$attributes=null) {
		if ($target->getIsRoot())
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot insert node; target is a root node'));
		return $this->addNode($this->mockParent($target),$target->getPosition() + 1,$validate,$attributes);
	}

	/**
	* Adds the target node as the previous sibling of the owner node.
	* @param CActiveRecord Target node
	* @param boolean Whether to validate attributes
	* @param array Attributes to save
	* @return boolean TRUE if the node was inserted, FALSE if not
	* @see insertBefore()
	*/
	public function before($target,$validate=true,$attributes=null) {
		$owner = $this->getOwner();
		if ($owner->getIsRoot())
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot insert node; owner is a root node'));
		return $target->insertBefore($owner,$validate,$attributes);
	}

	/**
	* Adds the owner node as the previous sibling of the target node.
	* @param CActiveRecord Target node
	* @param boolean Whether to validate attributes
	* @param array Attributes to save
	* @return boolean TRUE if the node was inserted, FALSE if not
	*/
	public function insertBefore($target,$validate=true,$attributes=null) {
		if ($target->getIsRoot())
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot insert node; target is a root node'));
		return $this->addNode($this->mockParent($target),$target->getPosition(),$validate,$attributes);
	}

	/**
	* Adds the target node as the first sibling of the owner node.
	* @param CActiveRecord Target node
	* @param boolean Whether to validate attributes
	* @param array Attributes to save
	* @return boolean TRUE if the node was inserted, FALSE if not
	* @see insertFirst()
	*/
	public function first($target,$validate=true,$attributes=null) {
		$owner = $this->getOwner();
		if ($owner->getIsRoot())
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot insert node; owner is a root node'));
		return $target->insertFirst($owner,$validate,$attributes);
	}

	/**
	* Adds the owner node as the first sibling of the target node.
	* @param CActiveRecord Target node
	* @param boolean Whether to validate attributes
	* @param array Attributes to save
	* @return boolean TRUE if the node was inserted, FALSE if not
	*/
	public function insertFirst($target,$validate=true,$attributes=null) {
		if ($target->getIsRoot())
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot insert node; target is a root node'));
		return $this->addNode($this->mockParent($target),1,$validate,$attributes);
	}

	/**
	* Adds the target node as the last sibling of the owner node.
	* @param CActiveRecord Target node
	* @param boolean Whether to validate attributes
	* @param array Attributes to save
	* @return boolean TRUE if the node was inserted, FALSE if not
	* @see insertLast()
	*/
	public function last($target,$validate=true,$attributes=null) {
		$owner = $this->getOwner();
		if ($owner->getIsRoot())
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot insert node; owner is a root node'));
		return $target->insertLast($owner,$validate,$attributes);
	}

	/**
	* Adds the owner node as the last sibling of the target node.
	* @param CActiveRecord Target node
	* @param boolean Whether to validate attributes
	* @param array Attributes to save
	* @return boolean TRUE if the node was inserted, FALSE if not
	*/
	public function insertLast($target,$validate=true,$attributes=null) {
		if ($target->getIsRoot())
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot insert node; target is a root node'));
		return $this->addNode($this->mockParent($target),0,$validate,$attributes);
	}

	/**
	* Adds the target node as the nth sibling of the owner node.
	* @param CActiveRecord Target node
	* @param integer Position to add at: 1 = 1st sibling, 2 = 2nd sibling,
	* n = nth sibling; 0 (default) = last sibling - this provides the best performance
	* as it minimises reindexing
	* @param boolean Whether to validate attributes
	* @param array Attributes to save
	* @return boolean TRUE if the node was inserted, FALSE if not
	*/
	public function addAsSibling($target,$n=0,$validate=true,$attributes=null) {
		$owner = $this->getOwner();
		if ($owner->getIsRoot())
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot insert node; owner is a root node'));
		return $target->addAsSiblingOf($owner,$n,$validate,$attributes);
	}

	/**
	* Adds the owner node as the nth sibling of the target node.
	* @param CActiveRecord Target node
	* @param integer Position to add at: 1 = 1st sibling, 2 = 2nd sibling,
	* n = nth sibling; 0 (default) = last sibling - this provides the best performance
	* as it minimises reindexing
	* @param boolean Whether to validate attributes
	* @param array Attributes to save
	* @return boolean TRUE if the node was inserted, FALSE if not
	*/
	public function addAsSiblingOf($target,$n=0,$validate=true,$attributes=null) {
		if ($target->getIsRoot())
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot insert node; target is a root node'));
		return $this->addNode($this->mockParent($target),$n,$validate,$attributes);
	}

	/**
	* Prepends the target node to the owner node, i.e. the target node becomes the
	* first child of the owner node.
	* @param CActiveRecord Target node
	* @param boolean Whether to validate attributes
	* @param array Attributes to save
	* @return boolean TRUE if the node was prepended, FALSE if not
	*/
	public function prepend($target,$validate=true,$attributes=null) {
		return $target->prependTo($this->getOwner(),1,$validate,$attributes);
	}

	/**
	* Prepends the owner node to the target node, i.e. the owner node becomes the
	* first child of the target node.
	* @param CActiveRecord Target node
	* @param boolean Whether to validate attributes
	* @param array Attributes to save
	* @return boolean TRUE if the node was prepended, FALSE if not
	*/
	public function prependTo($target,$validate=true,$attributes=null) {
		return $this->addNode($target,1,$validate,$attributes);
	}

	/**
	* Delete the descendants of a node
	* @return integer the number of nodes deleted.
	*/
	public function deleteDescendants() {
		$owner = $this->getOwner();
		$db = $owner->getDbConnection();
		$transaction = ($db->getCurrentTransaction()===null
			?$db->beginTransaction():null
		);

		try {
			$this->_this = true;
			$n = $owner->deleteAll(str_replace(
				$db->quoteTableName($owner->getTableAlias()).'.',
				$db->quoteTableName($owner->tableName()).'.',
				$owner->descendants()->getDbCriteria()->condition
			));
			$this->_this = false;

			if ($transaction!==null)
				$transaction->commit();
			return $n;
		}
		catch (CException $e) {
			if ($transaction!==null)
				$transaction->rollback();
			throw $e;
		}
	}

	/**
	* Delete a node and all its descendants
	* @return integer the number of nodes deleted.
	*/
	public function deleteNode() {
		$owner = $this->getOwner();
		$db = $owner->getDbConnection();
		$transaction = ($db->getCurrentTransaction()===null
			?$db->beginTransaction():null
		);

		try {
			$this->_this = true;
			$d = $owner->deleteAll(str_replace(
				$db->quoteTableName($owner->getTableAlias()).'.',
				$db->quoteTableName($owner->tableName()).'.',
				$owner->descendants()->self()->getDbCriteria()->condition
			));
			$this->_this = false;
			$this->_isDeleted = true;

			$this->shift($this->mockParent(), $this->getPosition()+1, 0, false);

			if ($transaction!==null)
				$transaction->commit();
			return $d;
		}
		catch (CException $e) {
			if ($transaction!==null)
				$transaction->rollback();
			throw $e;
		}
	}

	/**
	* Moves the owner node to become the next sibling of the target node
	* @param CActiveRecord The target node
	* @return integer The number of nodes moved
	*/
	public function moveAfter($target) {
		return $this->moveNode($target,$target->getPosition() + 1,true);
	}

	/**
	* Moves the owner node to become the previous sibling of the target node
	* @param CActiveRecord The target node
	* @return integer The number of nodes moved
	*/
	public function moveBefore($target) {
		return $this->moveNode($target,$target->getPosition(),true);
	}

	/**
	* Moves the owner node to become the first sibling of the target node
	* @param CActiveRecord The target node
	* @return integer The number of nodes moved
	*/
	public function moveToFirstSibling($target) {
		return $this->moveNode($target,1,true);
	}

	/**
	* Moves the owner node to become the last sibling of the target node
	* @param CActiveRecord The target node
	* @return integer The number of nodes moved
	*/
	public function moveToLastSibling($target) {
		return $this->moveNode($target,0,true);
	}

	/**
	* Moves the owner node to become the nth sibling of the target node
	* @param CActiveRecord The target node
	* @return boolean TRUE if the move was successful, FALSE if not
	*/
	public function moveToNthSibling($target,$n) {
		return $this->moveNode($target,$n,true);
	}

	/**
	* Moves the owner node to become the first child of the target node
	* @param CActiveRecord The target node
	* @return integer The number of nodes moved
	*/
	public function moveToFirstChild($target) {
		return $this->moveNode($target,1);
	}

	/**
	* Moves the owner node to become the last child of the target node
	* @param CActiveRecord The target node
	* @return integer The number of nodes moved
	*/
	public function moveToLastChild($target) {
		return $this->moveNode($target);
	}

	/**
	* Moves the owner node to become the nth child of the target node.
	* Alias for {@link moveNode()}
	* @param CActiveRecord The target node
	* @param integer Position to move to: 1 = 1st child, 2 = 2nd child,
	* n = nth child; 0 (default) = last child - this provides the best performance
	* as it minimises reindexing
	* @return integer The number of nodes moved
	*/
	public function moveToNthChild($target,$n) {
		return $this->moveNode($target,$n);
	}

	/**
	* Moves the owner node to become a new root node.
	* @return integer The number of nodes moved
	*/
	public function moveToRoot() {
		return $this->moveNode();
	}

	/**
	* Move the owner to become the nth child of the target node.
	* If target is NULL the owner becomes the nth root node.
	* @param CActiveRecodrd The target node
	* @param integer Position to move to: 1 = 1st child, 2 = 2nd child,
	* n = nth child; 0 (default) = last child - this provides the best performance
	* as it minimises reindexing
	* @param boolean If true the owner is to be a sibling of the target
	* @return integer The number of nodes moved
	*/
	private function moveNode($target=null,$n=0,$sibling=false) {
		$owner = $this->getOwner();
		if ($owner->getIsRoot())
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot move node; it is a root node (node Primary Key: {nodePK})', array('{nodePK}'=>print_r($owner->getPrimaryKey()))));
		if ($owner->getIsNewRecord())
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot move node; it is a new node'));
		if ($owner->getIsDeleted())
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot move node; it has been deleted (node Primary Key: {nodePK})', array('{nodePK}'=>print_r($owner->getPrimaryKey()))));
		if ($target && $owner->equals($target))
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot move node; target===node (node Primary Key: {nodePK})', array('{nodePK}'=>print_r($owner->getPrimaryKey()))));
		if ($target && $target->getIsDeleted())
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot move node; target node has been deleted (node Primary Key: {nodePK}, target Primary Key: {targetPK})', array('{nodePK}'=>print_r($owner->getPrimaryKey()), '{targetPK}'=>print_r($target->getPrimaryKey()))));
		if ($target && $target->isDescendantOf($owner))
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot move node; target node is a descendant node (node Primary Key: {nodePK}, target Primary Key: {targetPK})', array('{nodePK}'=>print_r($owner->getPrimaryKey()), '{targetPK}'=>print_r($target->getPrimaryKey()))));
		if ($target && get_class($target)!==get_class($owner))
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot move node; owner and target are different classes (owner: {owner}, target: {target})', array('{owner}'=>get_class($owner), '{target}'=>get_class($target))));

		if ($sibling)
			$target = $this->mockParent($target);

		$db = $owner->getDbConnection();
		$table = $owner->tableName();
/*		$transaction = ($db->getCurrentTransaction()===null
			?$db->beginTransaction():null
		);*/

		try {
			$p = $this->getPosition();
			$parent = $this->mockParent();
			$shiftDown = false;

			if ($target===null) {
				$node = clone $owner;
				// make owner be a root node
				$owner->{$this->nv}  = $this->roots()->count() + 1;
				$owner->{$this->snv} = $owner->{$this->nv} + 1;
				$owner->{$this->dv}  = 1;
				$owner->{$this->sdv} = 1;

				$this->_this = true;
				$owner->update(array($this->nv,$this->snv,$this->dv,$this->sdv));
				$this->_this = false;

				// Move the owner's descendants to be under the root node
				$moved = $this->move($node, 0, $owner, 0) + 1;
			}
			else {
				$c = $target->children()->count();
				if ($n===0 || $n>$c)
					$n = $c + 1;
				else
					// Make room for the moving node by shifting siblings up at the new position and above
					$this->shift($target,$n);

				// moving under the same parent
				if ($parent->{$this->nv}===(int)$target->{$this->nv}
					&& $parent->{$this->dv}===(int)$target->{$this->dv}) {
					// if old position is above new position, node will have moved up one
					if ($p>$n) {
						$owner->{$this->nv}  += $parent->{$this->snv};
						$owner->{$this->snv} += $parent->{$this->snv};
						$owner->{$this->dv}  += $parent->{$this->sdv};
						$owner->{$this->sdv} += $parent->{$this->sdv};
						$p++;
					}
					// if old position is below new position, move position to one higher
					// as nodes are shifted down after move to fill the gap
					else {
						$n++;
						$shiftDown = true;
					}
				}
				else {
					// if the owner is descendant of target and to the right of the new position
					// get the new key as it will have been moved
					if ($owner->isDescendantOf($target) && ($owner->{$this->nv} * ($target->{$this->dv} + $n * $target->{$this->sdv}) * ($target->{$this->dv} + ($n + 1) * $target->{$this->sdv}) / $owner->{$this->dv} - ($target->{$this->nv} + $n * $target->{$this->snv}) * ($target->{$this->dv} + ($n + 1) * $target->{$this->sdv})) > 0) {
						$this->moveOwner();

						list($nv,$dv,$snv,$sdv) = $this->parentQuadruple($owner);

						$parent->{$this->nv}  = $nv;
						$parent->{$this->dv}  = $dv;
						$parent->{$this->snv} = $snv;
						$parent->{$this->sdv} = $sdv;
					}
					// else if the target is in a sub-tree of a sibling to the right of the
					// owner, the node will be shifted down after the move
					elseif ($target->isDescendantOf($parent) && ($target->{$this->nv} * ($parent->{$this->dv} + $p * $parent->{$this->sdv}) * ($parent->{$this->dv} + ($p + 1) * $parent->{$this->sdv}) / $target->{$this->dv} - ($parent->{$this->nv} + $p * $parent->{$this->snv}) * ($parent->{$this->dv} + ($p + 1) * $parent->{$this->sdv})) > 0) {
						$shiftDown = true;
					}
				}

				// Move the owner node and subtree to its new position
				$k = $db->quoteColumnName("$table.".$this->nv).'*'.($owner->{$this->dv} * $owner->{$this->sdv}).'/'.$db->quoteColumnName("$table.".$this->dv).'-'.($owner->{$this->nv} * $owner->{$this->sdv});

				$moved = $this->move($parent, $p, $target, $n, "$k>=0 AND $k<1");
				$this->moveOwner();
			}

			// Move old later siblings down
			$this->shift($parent, $p, false);

			if ($shiftDown)
				$this->moveOwner();

/*			if ($transaction!==null)
				$transaction->commit();*/
			return $moved;
		}
		catch (CException $e) {
/*			if ($transaction!==null)
				$transaction->rollback();*/
			throw $e;
		}
	}

	/**
	* Returns a value indicating if the node has children.
	* @return boolean TRUE if the node has children; FALSE if not.
	*/
	public function hasChildren() {
		$owner = $this->getOwner();
		$db = $owner->getDbConnection();
		$alias = $owner->getTableAlias();
		return $owner->exists($db->quoteColumnName("$alias.".$this->snv)
			.'-'.$db->quoteColumnName("$alias.".$this->nv)
			.'='.$owner->{$this->snv}
			.' AND '
			.$db->quoteColumnName("$alias.".$this->sdv)
			.'-'.$db->quoteColumnName("$alias.".$this->dv)
			.'='.$owner->{$this->sdv}
		);
	}

	/**
	* Returns a value indicating if the owner is an ancestor of the node.
	* @return boolean TRUE if the owner is an ancestor of the node; FALSE if not.
	*/
	public function isAncestorOf($node) {
		$owner = $this->getOwner();
		$k = $node->{$this->nv} * $owner->{$this->dv} * $owner->{$this->sdv} /
			$node->{$this->dv} - $owner->{$this->nv} * $owner->{$this->sdv};
		return $k>0 && $k<1;
	}

	/**
	* Returns a value indicating if the owner is a child of the node.
	* @return boolean TRUE if the owner is a chils of the node; FALSE if not.
	*/
	public function isChildOf($node) {
		$owner = $this->getOwner();
		return ($owner->{$this->snv}-$owner->{$this->nv})===(integer)$node->{$this->snv}
		&& ($owner->{$this->sdv}-$owner->{$this->dv})===(integer)$node->{$this->sdv};
	}

	/**
	* Returns a value indicating if the owner is a descendant of the node.
	* @return boolean TRUE if the owner is a descendant of the node; FALSE if not.
	*/
	public function isDescendantOf($node) {
		$owner = $this->getOwner();
		$k = $owner->{$this->nv} * $node->{$this->dv} * $node->{$this->sdv} /
			$owner->{$this->dv} - $node->{$this->nv} * $node->{$this->sdv};
		return $k>0 && $k<1;
	}

	/**
	* Returns a value indicating if the owner is the parent of the node.
	* @return boolean TRUE if the owner is a descendant of the node; FALSE if not.
	*/
	public function isParentOf($node) {
		$owner = $this->getOwner();
		return ($node->{$this->snv}-$node->{$this->nv})===(integer)$owner->{$this->snv}
		&& ($node->{$this->sdv}-$node->{$this->dv})===(integer)$owner->{$this->sdv};
	}

	/**
	* Returns a value indicating if the owner is a descendant of the node.
	* @return boolean TRUE if the owner is a descendant of the node; FALSE if not.
	*/
	public function isSiblingOf($node) {
		$owner = $this->getOwner();
		return $owner->{$this->snv}-$owner->{$this->nv}===$node->{$this->snv}-$node->{$this->nv}
		&& $owner->{$this->sdv}-$owner->{$this->dv}===$node->{$this->sdv}-$node->{$this->dv};
	}

	/**
	* Returns a value indicating if the node is a leaf node, i.e. it has no children.
	* @return boolean TRUE if the node is a leaf node; FALSE if not.
	*/
	public function isLeaf() {
		return $this->getIsLeaf();
	}

	/**
	* Returns a value indicating if the node is a root node.
	* @return boolean TRUE if the node is a root node; FALSE if not.
	*/
	public function isRoot() {
		return $this->getIsRoot();
	}

	/**
	* Returns a value indicating if the node is a leaf node, i.e. it has no children.
	* @return boolean TRUE if the node is a leaf node; FALSE if not.
	*/
	public function getIsLeaf() {
		return !$this->hasChildren();
	}

	/**
	* Returns a value indicating if the node is a root node.
	* @return boolean TRUE if the node is a root node; FALSE if not.
	*/
	public function getIsRoot() {
		return $this->getOwner()->{$this->dv}==1;
	}

	/**#@+
	* Events
	*/

	/**#@+
	* Events
	*/
	/**
	* Handle the owner's 'beforeDelete' event.
	* @param CEvent The event
	* @return boolean TRUE if the operation should continue, FALSE if not
	* @throws NestedIntervalException If CActiveRecord::delete() called directly on a node
	*/
	public function beforeDelete($event) {
		if (!$this->_this)
			throw new NestedIntervalException(Yii::t(__CLASS__.'.'.__CLASS__,
				'Do not call {owner}::{method}() to {operation} a record when {class} is attached; use {class}::{method}Node()',
				array(
					'{owner}'=>get_class($event->sender),
					'{method}'=>Yii::t(__CLASS__.'.'.__CLASS__,'delete'),
					'{operation}'=>Yii::t(__CLASS__.'.'.__CLASS__,'delete'),
					'{class}'=>__CLASS__,
				)
			));
		return true;
	}

	/**
	* Handle the owner's 'beforeSave' event.
	* @param CEvent The event.
	* @return boolean TRUE if the operation should continue, FALSE if not
	* @throws NestedIntervalException If CActiveRecord::save() called directly on a new node
	*/
	public function beforeSave($event) {
		if ($event->sender->getIsNewRecord() && !$this->_this)
			throw new NestedIntervalException(Yii::t(__CLASS__.'.'.__CLASS__,
				'Do not call {owner}::{method}() to {operation} a record when {class} is attached; use {class}::{method}Node()',
				array(
					'{owner}'=>get_class($event->sender),
					'{method}'=>Yii::t(__CLASS__.'.'.__CLASS__,'save'),
					'{operation}'=>Yii::t(__CLASS__.'.'.__CLASS__,'add'),
					'{class}'=>__CLASS__,
				)
			));
		return true;
	}

	/**
	* Adds a node.
	* All other methods to add a node are wrappers for this method.
	* @param CActiveRecord Parent node. If NULL the owner is added as a root
	* node, if not NULL the owner is added as a child of the parent.
	* @param integer. Position to add the node at. 1 = 1st child, 2 = 2nd child,
	* n = nth child; 0 (default) = last child - this provides the best performance
	* as it minimises reindexing.
	* Only used when the owner is not being added as a root node - $parent is not
	* empty.
	* @param boolean Whether to perform validation on the owner.
	* @param array Owner attributes to validate.
	* @return boolean TRUE if the save was successful, FALSE if not.
	* @throws NestedIntervalException If the owner node is not a new node.
	*/
	private function addNode($parent=null,$n=0,$validate=true,$attributes=null) {
		$owner = $this->getOwner();
		if (!$owner->getIsNewRecord())
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot add node; it is not a new node(node Primary Key: {nodePK})', array('{nodePK}'=>print_r($owner->getPrimaryKey()))));
		if ($parent && get_class($parent)!==get_class($owner))
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,'Cannot add node; owner and parent are different classes (owner: {owner}, parent: {parent})', array('{owner}'=>get_class($owner), '{parent}'=>get_class($parent))));

		if ($validate && !$owner->validate($attributes))
			return false;

		if (is_array($attributes))
			$attributes = array_merge($attributes,array(
				$this->nv,$this->snv,$this->dv,$this->sdv
			));

		if (empty($parent)) { // add as a root
			$owner->{$this->nv}  = $this->roots()->count() + 1;
			$owner->{$this->dv}  = 1;
			$owner->{$this->snv} = $owner->{$this->nv} + 1;
			$owner->{$this->sdv} = 1;

			$this->_this = true;
			$result = $owner->insert();
			$this->_this = false;
		}
		else {
			$db = $owner->getDbConnection();
			$transaction = ($db->getCurrentTransaction()===null
				?$db->beginTransaction():null
			);

			try {
				$c = $parent->children()->count();
				if ($n && $n<=$c)
					// Move children of the parent node up, at and above the insert position
					$this->shift($parent, $n);
				else
					$n = $c + 1;

				$owner->{$this->nv}  = $parent->{$this->nv} + $n   * $parent->{$this->snv};
				$owner->{$this->dv}  = $parent->{$this->dv} + $n++ * $parent->{$this->sdv};
				$owner->{$this->snv} = $parent->{$this->nv} + $n   * $parent->{$this->snv};
				$owner->{$this->sdv} = $parent->{$this->dv} + $n   * $parent->{$this->sdv};

				$this->_this = true;
				$result = $owner->insert();
				$this->_this = false;

				if ($transaction!==null)
					$transaction->commit();
			}
			catch (CException $e) {
				if ($transaction!==null)
					$transaction->rollback();
				throw $e;
			}
		}

 		return $result;
	}

	/**
	* Creates a tree of descendant nodes by recursively adding child nodes of the
	* parent as related nodes.
	* @param CActiveRecord The parent node
	* @param array Descendants of the owner to process
	*/
	private function createTree($parent,&$descendants) {
		while ($descendants) {
			$node = array_shift($descendants);
			if ($node->isChildOf($parent)) {
				$child = clone $node;
				$parent->addRelatedRecord($this->childRelatedRecords,$child,true);
			}
			elseif ($node->isChildOf($child)) {
				$child->addRelatedRecord($this->childRelatedRecords,$node,true);
				$this->createTree($child,$descendants);
			}
			else {
				array_unshift($descendants,$node);
				return;
			}
		}
	}

	/**
	* Moves a subtree from the $nth child of $p0 to the $mth child of $p1
	* Implements @{link http://arxiv.org/PS_cache/arxiv/pdf/0806/0806.3115v1.pdf (3.18)}
	* If the current parent and new parent are the same, $m-$n represents the
	* amount to shift child nodes by.
	* @param CActiveRecord Current parent node
	* @param integer Current position
	* @param CActiveRecord New parent node
	* @param integer New position
	* @param mixed Condition to apply to the move, i.e. which nodes to move. Used
	* when shifting child nodes to restrict which children are moved.
	* If NULL all children under $p0 are moved.
	* @return integer Number of nodes moved
	*/
	private function move($p0,$n,$p1,$m,$condition=null) {
		$owner = $this->getOwner();
		$db = $owner->getDbConnection();

		$this->_tm = $this->mMultiply($this->mMultiply(array(
			array($p1->{$this->nv},$p1->{$this->snv}),
			array($p1->{$this->dv},$p1->{$this->sdv}),
		),array(
			array(1,0),array($m-$n,1)
		)),array(
			array($p0->{$this->sdv}*-1,$p0->{$this->snv}),
			array($p0->{$this->dv},    $p0->{$this->nv}*-1),
		));

		if ($condition===null) {
			$k = $db->quoteColumnName($this->nv).'*'.($p0->{$this->dv} * $p0->{$this->sdv}).'/'.$db->quoteColumnName($this->dv).'-'.($p0->{$this->nv} * $p0->{$this->sdv});
			$condition = "$k>0 AND $k<1";
		}

		return $db->createCommand()->update($owner->tableName(),array(
			'nv'=>new CDbExpression(':tm00*(@onv:=nv)+:tm01*dv',array(
				'tm00'=>$this->_tm[0][0],
				'tm01'=>$this->_tm[0][1]
			)),
			'snv'=>new CDbExpression(':tm00*(@osnv:=snv)+:tm01*sdv',array(
				'tm00'=>$this->_tm[0][0],
				'tm01'=>$this->_tm[0][1]
			)),
			'dv'=>new CDbExpression(':tm10*@onv+:tm11*dv',array(
				'tm10'=>$this->_tm[1][0],
				'tm11'=>$this->_tm[1][1]
			)),
			'sdv'=>new CDbExpression(':tm10*@osnv+:tm11*sdv',array(
				'tm10'=>$this->_tm[1][0],
				'tm11'=>$this->_tm[1][1]
			)),
		),
		$condition
		);
	}

	/**
	* Multiplies two matricies
	* @param array Matrix to multiply
	* @param array Matrix to multiply by
	* @return array Resulting matrix
	*/
	private function mMultiply($m0,$m1) {
		return array(
			array($m0[0][0]*$m1[0][0]+$m0[0][1]*$m1[1][0],$m0[0][0]*$m1[0][1]+$m0[0][1]*$m1[1][1]),
			array($m0[1][0]*$m1[0][0]+$m0[1][1]*$m1[1][0],$m0[1][0]*$m1[0][1]+$m0[1][1]*$m1[1][1])
		);
	}

	/**
	* Updates the owner quadruple following a move
	*/
	private function moveOwner() {
		$owner = $this->getOwner();
		$ownerKey = $this->mMultiply($this->_tm, array(
			array($owner->{$this->nv},$owner->{$this->snv}),
			array($owner->{$this->dv},$owner->{$this->sdv})
		));
		$owner->{$this->nv}   = $ownerKey[0][0];
		$owner->{$this->snv}  = $ownerKey[0][1];
		$owner->{$this->dv}   = $ownerKey[1][0];
		$owner->{$this->sdv}  = $ownerKey[1][1];
	}

	/**
	* Returns object of node's class with the attributes nv, dv, snv, sdv set
	* to the node's parent quadruple and a new instance of this behavior attached.
	* Saves going to the DB.
	*/
	private function mockParent($node=null) {
		if ($node===null)
			$node = $this->getOwner();
		$model = get_class($node);
		$parent = new $model;
		$parent->attachBehavior('nestedInterval',array(
			'class'=>__CLASS__,
			'nv' =>$this->nv,
			'dv' =>$this->dv,
			'snv'=>$this->snv,
			'sdv'=>$this->sdv,
		));

		list($nv,$dv,$snv,$sdv) = $this->parentQuadruple($node);

		$parent->{$this->nv}  = $nv;
		$parent->{$this->dv}  = $dv;
		$parent->{$this->snv} = $snv;
		$parent->{$this->sdv} = $sdv;

		return $parent;
	}

	/**
	* Returns an array with the node's quadruple
	* @param CActiveRecord The node to return the parent quadruple of. If NULL the
	* owner's parent quadruple is returned
	* @return array The parent quadruple array(nv,dv,snv,sdv)
	*/
	public function parentQuadruple($node = null) {
		if ($node===null)
			$node = $this->getOwner();
		if ($node->{$this->dv}==1)
			throw new NestedIntervalException(Yii::t(__CLASS__.__CLASS__,"Cannot get node's parent quadruple; node is a root note"));
		$snv = $node->{$this->snv} - $node->{$this->nv};
		$sdv = $node->{$this->sdv} - $node->{$this->dv};
		$nv  = $node->{$this->nv} % $snv;
		$dv  = ($sdv===1?1:$node->{$this->dv} % $sdv);
		return array($nv,$dv,$snv,$sdv);
	}

	/**
	* Shift child nodes, and their sub-trees, of the parent
	* @param CActiveRecord Parent node
	* @param integer 1 based child node offset to shift; nodes at and above are shifted
	* @param boolean TRUE to shift up, FALSE to shift down
	* @return integer Number of nodes affected
	*/
	private function shift($parent,$n,$up=true) {
		if ($n===0)
			$condition = null;
		else {
			$db  = $this->getOwner()->getDbConnection();
			$nv  = $parent->{$this->nv} + $n   * $parent->{$this->snv};
			$dv  = $parent->{$this->dv} + $n++ * $parent->{$this->sdv};
			$sdv = $parent->{$this->dv} + $n   * $parent->{$this->sdv};

			$j = $db->quoteColumnName($this->nv).'*'.($dv * $sdv).'/'
				.$db->quoteColumnName($this->dv).'-'.($nv * $sdv);
			$k = $db->quoteColumnName($this->nv).'*'.($parent->{$this->dv} * $parent->{$this->sdv}).'/'
				.$db->quoteColumnName($this->dv).'-'.($parent->{$this->nv} * $parent->{$this->sdv});
			$condition = "$j>=0 AND $k<1";
		}
		return $this->move($parent, 0, $parent, ($up?1:-1), $condition);
	}
}

/**
* NestedIntervalException class.
* Useful for testing
*/
class NestedIntervalException extends CException {}