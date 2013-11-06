<?PHP

function xml2array($fname) {
	$sxi = new SimpleXmlIterator($fname, null, true);
	return sxi2array($sxi);
}

function sxi2array($sxi) {
	$a = array();

	for ($sxi->rewind(); $sxi->valid(); $sxi->next()) {
		if (!array_key_exists($sxi->key(), $a)) {
			$a[$sxi->key()] = array();
		}
		if ($sxi->hasChildren()) {
			$a[$sxi->key()][] = sxi2array($sxi->current());
		} else {
			$a[$sxi->key()] = strval($sxi->current());
		}
	}
	return $a;
}

function _fix_umlaut($text) {
	$fixme  = array('ä', 'ö', 'ü', 'ß');
	$new_array = array ('ae', 'oe', 'ue', 'ss');	
	return str_replace($fixme, $new_array, $text);
}


class ArrayToClass {
	protected $data = array();
	public function __construct($data) { $this->data = $data; }
	public function __get($property) { return $this->data[$property]; }
}


class Person extends ArrayToClass {
	protected $univis_url = "http://univis.uni-erlangen.de/prg?search=persons&";
	protected $location_kw = array("email", "fax", "ort", "office", "street", "tel", "url");
	private $_firstname = "";
	private $_lastname = "";

	public function __construct($firstname, $lastname, $id = null) {
		$this->_firstname = $firstname;
		$this->_lastname = $lastname;
		$url = $this->_query();
		if (fopen($url, "r")) {
			// Server available
			$persArray = xml2array($url);

			// If id is given, select this person only, else the first one
			if ($id) {
				foreach ($persArray["Person"] as $person)
					if ($person["id"] == $id) break;
			} else {
				$person = $persArray["Person"][0];
			}

			parent::__construct($person);
		} else {
			print ("Server unavailable");
			parent::__construct(array());
		}
	}

	public function __get($property) {
		if (in_array($property, $this->location_kw)) {
			// If more than one location, get first
			$location = new ArrayToClass($this->data["locations"][0]["location"][0]);
			return call_user_func(array($location, "__get"), $property);
		} else {
			return parent::__get($property);
		}
	}

	public function query() {
		return $this->univis_url."name="._fix_umlaut($this->_lastname)."&firstname="._fix_umlaut($this->_firstname);
	}

	private function _query() {
		return self::query()."&show=xml";
	}
}

?>
