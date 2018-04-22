<?phpnamespace app\admin\Controller;use think\Db;class Index extends admin{	public function __construct()	{		parent::__construct();	}	public function index()	{		$arr['sum_reg'] = db::table('user')->count();		$arr['sum_zzc'] = db::table('user')->sum('zzc');		$arr['sum_art'] = db::table('article')->count();		$arr['sum_trade'] = db::table('trade_log')->sum('mum');		$fangwen = db::table('sys_stats')->where(['name' => 'fangwen'])->limit(30)->order('addtime asc')->select();		if ($fangwen) {			foreach ($fangwen as $k => $v) {				$data[] = ['date' => addtime($v['addtime'] - 1), 'fangwen' => json_decode($v['data'], true)];			}			$arr['stats_fangwen'] = json_encode($data);		} else {			$arr['stats_fangwen'] = json_encode([]);		}		$xingneng = db::table('sys_stats')->where(['name' => 'xingneng'])->limit(30)->order('addtime asc')->select();		if ($xingneng) {			foreach ($xingneng as $k => $v) {				$tmp = json_decode($v['data'], true);				$data[] = ['date' => addtime($v['addtime'] - 1), 'cpu_usage' => $tmp[0], 'mem_usage' => $tmp[1]];			}			$arr['stats_xingneng'] = json_encode($data);		} else {			$arr['stats_xingneng'] = json_encode([]);		}		$this->assign('arr', $arr);		$this->assign('mm', mm());		$this->assign('current_version', file_get_contents(APP_PATH . '/backup/version.ini'));		$this->assign('system_info_mysql', db::query("select version() as v;"));		return view();	}	public function indexs()	{		$arr = [];		$arr['reg_sum'] = db::table('user')->count();		$arr['finan_num'] = db::table('user_coin')->sum('cny') + db::table('user_coin')->sum('cnyd');		$arr['finan_num_title'] = $arr['finan_num'];		if ($arr['finan_num'] > 100000000) {			$arr['finan_num'] = round($arr['finan_num'] / 100000000, 2) . '亿';		}		if ($arr['finan_num'] > 10000) {			$arr['finan_num'] = round($arr['finan_num'] / 10000, 2) . '万';		}		$arr['trance_mum'] = db::table('trade_log')->sum('mum');		if (!$arr['trance_mum']) {			$arr['trance_mum'] = 0;		}		$arr['trance_mum_title'] = $arr['trance_mum'];		if ($arr['trance_mum'] > 100000000) {			$arr['trance_mum'] = round($arr['trance_mum'] / 100000000, 2) . '亿';		}		if ($arr['trance_mum'] > 10000) {			$arr['trance_mum'] = round($arr['trance_mum'] / 10000, 2) . '万';		}		$arr['art_sum'] = db::table('article')->count([]);		$data['reg'] = [];		$reg = db::table('sys_stats')->where(['name' => 'reg', 'type' => 'reg'])->limit(30)->select();		foreach ($reg as $k => $v) {			$tmp = json_decode($v['data']);			if (!$tmp[0]) {				$tmp[0] = 0;			}			if (!$tmp[1]) {				$tmp[1] = 0;			}			$data['reg'][] = ['date' => addtime($v['addtime'] - 1), 'xin' => $tmp[0], 'fee' => $tmp[1]];		}		$data['cztx'] = [];		$pay = db::table('sys_stats')->where(['name' => 'pay'])->order('id asc')->limit(100)->select();		$out = db::table('sys_stats')->where(['name' => 'out'])->order('id asc')->limit(100)->select();		$new_data = [];		$new_pay = [];		if ($pay) {			foreach ($pay as $k => $v) {				$new_pay[$v['addtime']]['pay'] = $v;			}		}		if ($out) {			foreach ($out as $k => $v) {				$new_pay[$v['addtime']]['out'] = $v;			}		}		if ($new_pay) {			foreach ($new_pay as $k => $v) {				if (!isset($v['pay'])) {					$v['pay']['data'] = [];					$v['pay']['fee'] = [];				}				if (!isset($v['out'])) {					$v['out']['data'] = [];					$v['out']['fee'] = [];				}				$data['cztx'][] = ['date' => addtime($k), 'pay' => $v['pay']['data'], 'pay_fee' => $v['pay']['fee'], 'out' => $v['out']['data'], 'out_fee' => $v['out']['fee']];			}		}		$this->assign('cztx', json_encode($data['cztx']));		$this->assign('regs', json_encode($data['reg']));		$this->assign('arr', $arr);		$this->assign('mm', mm());		$this->assign('current_version', file_get_contents(APP_PATH . '/backup/version.ini'));		$this->assign('system_info_mysql', db::query("select version() as v;"));		$this->display();	}	public function sms()	{		$builder = new builder('sys_err');		$builder->title('安全中心', '<a href="http://www.movesay.com/article/detail/id/19.html" target="_blank"> 功能说明 http://www.movesay.com/article/detail/id/19.html</a>', '安全中心', '/index/sms')->key('id', 'ID', 'text')->key('name', '名称', 'text')->key('title', '标示', 'text')->key('value', '说明', 'text')->key('addtime', '添加时间', 'time')->key('status', '状态', 'select', [0 => '<span style="color:#DA9151;">未处理</span>', 1 => '<span style="color:#3498db;">已解决</span>'])->key("ext", '操作', 'link', [['type' => 'button2', 'url' => '/index/sms_chuli/id/###', 'title' => '确认已处理', 'field' => 'id', 'color' => '', 'ajax' => 'get', 'exts' => ["status" => [0]],]])->lists();	}	public function sms_chuli()	{		if (APP_DEMO) {			ajax('测试站暂时不能修改');		}		$id = iv('get.id', 'd', '参数错误');		$res = db::table('sys_err')->where(['id' => $id])->save(['status' => 1, "endtime" => time()]);		if ($res) {			ajax('操作成功', 1);		} else {			ajax('操作失败');		}	}}