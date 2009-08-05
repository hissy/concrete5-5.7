<?
defined('C5_EXECUTE') or die(_("Access Denied."));
class AttributeKey extends Object {

	/** 
	 * Returns the name for this attribute key
	 */
	public function getAttributeKeyName() { return $this->akName;}

	/** 
	 * Returns the handle for this attribute key
	 */
	public function getAttributeKeyHandle() { return $this->akHandle;}
	
	/** 
	 * Returns the ID for this attribute key
	 */
	public function getAttributeKeyID() {return $this->akID;}
	
	/** 
	 * Returns whether the attribute key is searchable */
	public function isAttributeKeySearchable() {return $this->akIsSearchable;}
	
	/** 
	 * Loads the required attribute fields for this instantiated attribute
	 */
	protected function load($akID) {
		$db = Loader::db();
		$row = $db->GetRow('select akID, akHandle, akName, akCategoryID, akIsEditable, akIsSearchable, AttributeKeys.atID, atHandle, AttributeKeys.pkgID from AttributeKeys inner join AttributeTypes on AttributeKeys.atID = AttributeTypes.atID where akID = ?', array($akID));
		$this->setPropertiesFromArray($row);
	}

	/** 
	 * Returns an attribute type object 
	 */
	public function getAttributeType() {
		return AttributeType::getByID($this->atID);
	}

	/** 
	 * Returns a list of all attributes of this category
	 */
	protected static function getList($akCategoryHandle) {
		$db = Loader::db();
		$r = $db->Execute('select akID from AttributeKeys inner join AttributeKeyCategories on AttributeKeys.akCategoryID = AttributeKeyCategories.akCategoryID where akCategoryHandle = ?', array($akCategoryHandle));
		$list = array();
		$txt = Loader::helper('text');
		$className = $txt->camelcase($akCategoryHandle);
		while ($row = $r->FetchRow()) {
			$c1 = $className . 'AttributeKey';
			$c1a = new $c1();
			$c1a->load($row['akID']);
			$list[] = $c1a;
		}
		return $list;
	}
	
	/** 
	 * Adds an attribute key. 
	 */
	protected function add($akCategoryHandle, $akHandle, $akName, $akIsSearchable, $atID) {
		if (!$akIsSearchable) {
			$akIsSearchable = 0;
		}
		$db = Loader::db();
		$akCategoryID = $db->GetOne("select akCategoryID from AttributeKeyCategories where akCategoryHandle = ?", $akCategoryHandle);
		$a = array($akHandle, $akName, $akIsSearchable, $atID, $akCategoryID);
		$r = $db->query("insert into AttributeKeys (akHandle, akName, akIsSearchable, atID, akCategoryID) values (?, ?, ?, ?, ?)", $a);
		
		if ($r) {
			$akID = $db->Insert_ID();
			$className = $akCategoryHandle . 'AttributeKey';
			$ak = new $className();
			$ak->load($akID);
			return $ak;
		}
	}

	/** 
	 * Updates an attribute key. 
	 */
	public function update($akHandle, $akName, $akIsSearchable) {
		if (!$akIsSearchable) {
			$akIsSearchable = 0;
		}
		$db = Loader::db();
		$akCategoryID = $db->GetOne("select akCategoryHandle from AttributeKeyCategories inner join AttributeKeys on AttributeKeys.akCategoryID = AttributeKeyCategories.akCategoryID where akID = ?", $this->getAttributeKeyID());
		$a = array($akHandle, $akName, $akIsSearchable, $this->getAttributeKeyID());
		$r = $db->query("update AttributeKeys set akHandle = ?, akName = ?, akIsSearchable = ? where akID = ?", $a);
		
		if ($r) {
			$className = $akCategoryHandle . 'AttributeKey';
			$ak = new $className();
			$ak->load($ak->getAttributeKeyID());
			return $ak;
		}
	}

	public function delete() {
		$at = $this->getAttributeType();
		$at->controller->setAttributeKey($this);
		$at->controller->deleteKey();
		
		$db = Loader::db();
		$db->Execute('delete from AttributeKeys where akID = ?', array($this->getAttributeKeyID()));
		$db->Execute('delete from AttributeSetKeys where akID = ?', array($this->getAttributeKeyID()));
	}
	
	public function getAttributeValueIDList() {
		$db = Loader::db();
		$ids = array();
		$r = $db->Execute('select avID from AttributeValues where akID = ?', array($this->getAttributeKeyID()));
		while ($row = $r->FetchRow()) {
			$ids[] = $row['avID'];
		}
		return $ids;
	}

	/** 
	 * Adds a generic attribute record (with this type) to the AttributeValues table
	 */
	public function addAttributeValue() {
		$db = Loader::db();
		$u = new User();
		$dh = Loader::helper('date');
		$uID = $u->isRegistered() ? $u->getUserID() : 0;
		$avDate = $dh->getLocalDateTime();
		$v = array($this->atID, $this->akID, $uID, $avDate);
		$db->Execute('insert into AttributeValues (atID, akID,  uID, avDateAdded) values (?, ?, ?, ?)', $v);
		$avID = $db->Insert_ID();
		return AttributeValue::getByID($avID);
	}
	
	
	/** 
	 * Renders a view for this attribute key. If no view is default we display it's "view"
	 * Valid views are "view", "form" or a custom view (if the attribute has one in its directory)
	 * Additionally, an attribute does not have to have its own interface. If it doesn't, then whatever
	 * is printed in the corresponding $view function in the attribute's controller is printed out.
	 */
	public function render($view = 'view', $value = false) {
		$at = AttributeType::getByHandle($this->atHandle);
		$at->render($view, $this, $value);
	}
	
	/** 
	 * Calls the functions necessary to save this attribute to the database. If no passed value is passed, then we save it via the stock form.
	 */
	protected function saveAttribute($attributeValue, $passedValue = false) {
		$at = $this->getAttributeType();
		$at->controller->setAttributeKey($this);
		$at->controller->setAttributeValue($attributeValue);
		if ($passedValue) {
			$at->controller->saveValue($passedValue);
		} else {
			$at->controller->saveForm($at->controller->post());
		}
		return $av;
	}
}
