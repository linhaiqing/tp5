<?phpnamespace home;use Move\db;use Move\ext\page;class help extends home{	public function __construct()	{		parent::__construct();		$this->is_game('help');	}	public function index()	{		$this->display();	}}