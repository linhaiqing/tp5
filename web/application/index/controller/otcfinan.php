<?phpnamespace home;use Move\db;use Move\ext\client;use Move\ext\page;class otcfinan extends home{	public function __construct()	{		parent::__construct();		$this->is_login();		$this->is_goole();	}	public function index()	{		$this->set_title('委托管理');		$area = iv('get.area', 'd', $this->ajax_lang('地区参数错误'), 1);		$type = iv('get.type', 'd', $this->ajax_lang('类型参数错误'), 1);		$coin = iv('get.coin', 'w', $this->ajax_lang('币种参数错误'), 1);		$where['userid'] = $this->userid;		if ($area) {			$where['area'] = $area;		}		if ($type) {			$where['type'] = $type;		}		if ($coin) {			$where['coin'] = $coin;		}		$count = db::table('otc_trade')->where($where)->count();		$page_obj = new page($count, 10);		$show = $page_obj->show();		$list = db::table('otc_trade')->where($where)->order('sort asc ,id desc')->limit($page_obj->firstRow, $page_obj->listRows)->select();		$pages = ["list" => $list, "show" => $show];		if (!$trade_area = mc('trade_area')) {			$trade_area = db::table('otc_trade_area')->where(['status' => 1])->order('sort asc,id desc')->select();			mc('trade_area', $trade_area);		}		$mymc_coin = new \mymc\otccoin();		$coin_list = $mymc_coin->all_xnb();		$this->coin_list = $coin_list;		$this->assign('trade_area', $trade_area);		$this->assign('pages', $pages);		$this->assign('type', $type);		$this->assign('area', $area);		$this->assign('coin', $coin);		$this->display();	}	public function status()	{		$id = iv('post.id', 'd', $this->ajax_lang('编号错误'));		$type = iv('post.type', 'd', $this->ajax_lang('状态参数错误'), 1);		$trade = db::table('otc_trade')->where(['id' => $id])->find();		if (empty($trade)) {			ajax($this->ajax_lang('广告不存在'));		}		if ($trade['userid'] != session('userid')) {			ajax($this->ajax_lang('非法访问'));		}		if ($trade['status'] == 1 && $type == 1) {			ajax($this->ajax_lang('广告不可用'));		}		if ($trade['status'] == 0 && $type == 0) {			ajax($this->ajax_lang('广告不可用'));		}		$res = db::table('otc_trade')->where(['id' => $id])->save(['status' => $type]);		if ($res) {			ajax($this->ajax_lang('操作成功'), 1);		} else {			ajax($this->ajax_lang('操作失败'));		}	}	public function get_log()	{		$this->set_title('成交记录');		$type = iv('get.type', 'd', $this->ajax_lang('类型参数错误'), 1);		$coin = iv('get.coin', 'w', $this->ajax_lang('币种错误'), 1);		$where['userid'] = $this->userid;		if ($coin > 0) {			$where['coin'] = $coin;		}		if ($type > 0) {			$where['type'] = $type;		}		$mymc_coin = new \mymc\otccoin();		$count = db::table('otc_trade_log')->where($where)->count();		$page_obj = new page($count, 10);		$show = $page_obj->show();		$list = db::table('otc_trade_log')->where($where)->order('sort asc ,id desc')->limit($page_obj->firstRow, $page_obj->listRows)->select();		$pages = ["list" => $list, "show" => $show];		$this->assign('pages', $pages);		$this->assign('type', $type);		$this->assign('coin', $coin);		$coin_list = $mymc_coin->all_xnb();		$this->coin_list = $coin_list;		$this->display();	}	public function get_status()	{		$id = iv('post.id', 'd', $this->ajax_lang('参数错误'));		$type = iv('post.type', 'd', $this->ajax_lang('状态错误'), 1);		$paypassword = iv('post.paypassword', 'password', $this->ajax_lang('密码错误'));		if (hashs($paypassword) != $this->user['paypassword']) {			ajax($this->ajax_lang('密码错误'));		}		$trade_log = db::table('otc_trade_log')->where(['id' => $id])->find();		if (empty($trade_log)) {			ajax($this->ajax_lang('交易不存在'));		}		if ($trade_log['userid'] != session('userid')) {			ajax($this->ajax_lang('非法访问'));		}		if ($trade_log['status'] == 0) {			ajax($this->ajax_lang('订单已取消'));		}		if ($type == 2 || $type == 0) {			if ($trade_log['status'] != 1) {				ajax($this->ajax_lang('状态异常'));			}		}		if ($type == 3) {			if ($trade_log['status'] != 2 || $trade_log['type'] != 1) {				ajax($this->ajax_lang('状态异常'));			}		}		if ($type == 4) {			if ($trade_log['status'] != 3 || $trade_log['type'] != 2) {				ajax($this->ajax_lang('状态异常'));			}		}		if ($type == 5) {			if ($trade_log['status'] != 3 || $trade_log['type'] != 2) {				ajax($this->ajax_lang('状态异常'));			}		}		db::exec("set autocommit=0");		db::exec("lock tables #pre#otc_trade_log write ,#pre#user_coin write,#pre#user write");		$rs[] = db::table('otc_trade_log')->where(['id' => $id])->save(['status' => $type, 'endtime' => time()]);		if ($type == 2 && $trade_log['type'] == 2) {			$user_coin = db::table('user_coin')->where(['userid' => session('userid')])->find($trade_log['coin']);			if ($user_coin < $trade_log['mum']) {				db::exec("rollback");				db::exec("unlock tables");				ajax($this->ajax_lang('余额不足'));			}			$rs[] = db::table('user_coin')->where(['userid' => session('userid')])->set([$trade_log['coin'] . '#-' => $trade_log['mum']]);		}		if ($type == 4) {			$mum = $trade_log['num'] - $trade_log['fee_buy'];			$rs[] = db::table('user_coin')->where(['userid' => $trade_log['peerid']])->set([$trade_log['coin'] . '#+' => $mum]);			$rs[] = db::table('user')->where(['id' => $trade_log['userid']])->set(['tradetimes#+' => 1]);			$rs[] = db::table('user')->where(['id' => $trade_log['peerid']])->set(['tradetimes#+' => 1]);		}		if (check_arr($rs)) {			db::exec("commit");			db::exec("unlock tables");			if ($type == 2 && $trade_log['type'] == 2) {				$email = new verify();				$email->send_email($trade_log['peerid'], $moban = 'paymoney', $trade_log['id']);			}			if ($type == 3 && $trade_log['type'] == 1) {				$email = new verify();				$email->send_email($trade_log['peerid'], $moban = 'suremoney', $trade_log['id']);			}			ajax($this->ajax_lang('操作成功'), 1);		} else {			db::exec("rollback");			db::exec("unlock tables");			ajax($this->ajax_lang('操作失败'));		}	}	public function send_log()	{		$this->set_title('成交记录');		$type = iv('get.type', 'd', $this->ajax_lang('类型错误'), 1);		$coin = iv('get.coin', 'w', $this->ajax_lang('币种错误'), 1);		$where['peerid'] = $this->userid;		if ($coin > 0) {			$where['coin'] = $coin;		}		if ($type > 0) {			$where['type'] = $type;		}		$mymc_coin = new \mymc\otccoin();		$count = db::table('otc_trade_log')->where($where)->count();		$page_obj = new page($count, 10);		$show = $page_obj->show();		$list = db::table('otc_trade_log')->where($where)->order('sort asc ,id desc')->limit($page_obj->firstRow, $page_obj->listRows)->select();		$pages = ["list" => $list, "show" => $show];		$this->assign('pages', $pages);		$this->assign('type', $type);		$this->assign('coin', $coin);		$coin_list = $mymc_coin->all_xnb();		$this->coin_list = $coin_list;		$this->display();	}	public function send_status()	{		$id = iv('post.id', 'd', $this->ajax_lang('参数错误'));		$type = iv('post.type', 'd', $this->ajax_lang('状态错误'), 1);		$paypassword = iv('post.paypassword', 'password', $this->ajax_lang('密码错误'));		if (hashs($paypassword) != $this->user['paypassword']) {			ajax($this->ajax_lang('密码错误'));		}		$trade_log = db::table('otc_trade_log')->where(['id' => $id])->find();		if (empty($trade_log)) {			ajax($this->ajax_lang('交易不存在'));		}		if ($trade_log['peerid'] != session('userid')) {			ajax($this->ajax_lang('非法访问1'));		}		if ($trade_log['status'] == 0) {			ajax($this->ajax_lang('订单已取消'));		}		if ($type == 0) {			if ($trade_log['status'] != 1) {				ajax($this->ajax_lang('状态异常'));			}		}		if ($type == 2) {			ajax($this->ajax_lang('非法访问2'));		}		if ($type == 3) {			if ($trade_log['status'] != 2 || $trade_log['type'] != 2) {				ajax($this->ajax_lang('状态异常'));			}		}		if ($type == 4) {			if ($trade_log['status'] != 3 || $trade_log['type'] != 1) {				ajax($this->ajax_lang('状态异常'));			}		}		if ($type == 5) {			if ($trade_log['status'] != 3 || $trade_log['type'] != 1) {				ajax($this->ajax_lang('状态异常'));			}		}		db::exec("set autocommit=0");		db::exec("lock tables #pre#otc_trade_log write ,#pre#user_coin write,#pre#user write");		$rs[] = db::table('otc_trade_log')->where(['id' => $id])->save(['status' => $type, 'endtime' => time()]);		if ($type == 4) {			$mum = $trade_log['num'] - $trade_log['fee_buy'];			$rs[] = db::table('user_coin')->where(['userid' => $trade_log['userid']])->set([$trade_log['coin'] . '#+' => $mum]);			$rs[] = db::table('user')->where(['id' => $trade_log['userid']])->set(['tradetimes#+' => 1]);			$rs[] = db::table('user')->where(['id' => $trade_log['peerid']])->set(['tradetimes#+' => 1]);		}		if (check_arr($rs)) {			db::exec("commit");			db::exec("unlock tables");			if ($type == 3 && $trade_log['type'] == 2) {				$email = new verify();				$email->send_email($trade_log['userid'], $moban = 'suremoney', $trade_log['id']);			}			ajax($this->ajax_lang('操作成功'), 1);		} else {			db::exec("rollback");			db::exec("unlock tables");			ajax($this->ajax_lang('操作失败'));		}	}	public function bank()	{		$this->set_title('银行卡管理');		$coin = iv("get.coin", 'w', $this->ajax_lang('币种错误'), 1);		$mymc_coin = new \mymc\otccoin();		$coins = $mymc_coin->all_rmb(1);		$coin_select = [];		if ($coins) {			foreach ($coins as $k => $v) {				$coin_select[$v['name']] = $v;			}		}		if (!$coin) {			$coin = 'cny';		}		$where = ['userid' => $this->userid, 'coinname' => $coin];		$count = db::table("otc_bank")->where($where)->count();		$PageObj = new page($count, 11);		$show = $PageObj->show();		$list = db::table("otc_bank")->where($where)->order("id desc")->limit($PageObj->firstRow, $PageObj->listRows)->select();		foreach ($list as $k => $v) {			$list[$k]['bankcard'] = substr_replace($v['bankcard'], '****', 4, 4);		}		$pages = ['show' => $show, 'list' => $list];		$this->assign('pages', $pages);		$this->assign('coin', $coin);		$this->assign('coin_select', $coin_select);		$this->display();	}	public function gettype()	{		$coin = iv("get.coin", 'w', $this->ajax_lang('币种错误'), 1);		$payway = db::table('otc_payway')->where(['coinname' => $coin])->select();		ajax($payway, 1);	}	public function bank_up()	{		$this->check_up('添加银行卡');		$name = iv('post.name', 'a', $this->ajax_lang('备注名称格式错误'));		$type = iv('post.type', 'w', $this->ajax_lang('账户类型格式错误'));		$bankinfo = iv('post.bankinfo', 'a', $this->ajax_lang('账户信息格式错误'));		$bankcard = iv('post.bankcard', 'a', $this->ajax_lang('账户号码格式错误'));		$coinname = iv('post.bank_coinname', 'w', $this->ajax_lang('币种格式错误'));		$paypassword = iv('post.paypassword', 'password', $this->ajax_lang('交易密码格式错误'));		$user = db::table("user")->where(["id" => $this->userid])->find();		if ($user['paypassword'] != hashs($paypassword)) {			ajax($this->ajax_lang('密码错误'));		}		if (isset($user['truename']) && !$user['truename']) {			ajax($this->ajax_lang('请完善实名信息'));		}		$user_bank = db::table('otc_bank')->where(['userid' => $this->userid, 'coinname' => $coinname])->select();		foreach ($user_bank as $k => $v) {			if ($v['name'] == $name) {				ajax($this->ajax_lang('备注名称重复'));			}			if ($v['bankcard'] == $bankcard) {				ajax($this->ajax_lang('卡号已存在'));			}		}		if (count($user_bank) >= 10) {			ajax($this->ajax_lang('最多只能添加10个'));		}		$res = db::table("otc_bank")->add(['userid' => session('userid'), 'name' => $name, 'coinname' => $coinname, 'truename' => $user['truename'], 'bankinfo' => $bankinfo, 'type' => $type, 'bankcard' => $bankcard, 'addtime' => time(), 'status' => 1,]);		if ($res) {			ajax($this->ajax_lang('操作成功'), 1);		} else {			ajax($this->ajax_lang('操作失败'));		}	}	function luhm($no)	{		$arr_no = str_split($no);		$last_n = $arr_no[count($arr_no) - 1];		krsort($arr_no);		$i = 1;		$total = 0;		foreach ($arr_no as $n) {			if ($i % 2 == 0) {				$ix = $n * 2;				if ($ix >= 10) {					$nx = 1 + ($ix % 10);					$total += $nx;				} else {					$total += $ix;				}			} else {				$total += $n;			}			$i++;		}		$total -= $last_n;		$total *= 9;		if ($last_n == ($total % 10)) {			return 1;		} else {			return 0;		}	}	public function bank_del()	{		$this->check_up('删除银行卡');		$id = iv('post.id', 'd', $this->ajax_lang('参数错误'));		$paypassword = iv('post.paypassword', 'password', $this->ajax_lang('密码错误'));		$user = db::table("user")->where(["id" => $this->userid])->find();		if (!$user) {			ajax($this->ajax_lang('用户异常'));		}		($user['paypassword'] != hashs($paypassword)) && ajax($this->ajax_lang('密码错误'));		$user_bank = db::table('otc_bank')->where(["id" => $id])->find();		if (!$user_bank || $user_bank["userid"] != $user['id']) {			ajax($this->ajax_lang('非法访问'));		}		$res = db::table("otc_bank")->where(["id" => $id])->delete();		if ($res) {			ajax($this->ajax_lang('操作成功'), 1);		} else {			ajax($this->ajax_lang('操作失败'));		}	}} 