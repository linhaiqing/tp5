<?phpnamespace app\common\model;use think\db;class back extends common{	public function shop_data()	{		return [1];		$rs = [];		db::exec('set autocommit=0');		db::exec('lock tables  #pre#shop_log write ,#pre#back_data write');		$shop_log = db::table('shop_log')->where(['status' => 1, 'back' => 2])->limit(100)->select();		if (empty($shop_log)) {			db::exec('commit');			db::exec('unlock tables');			return ['没有统计项目'];		}		foreach ($shop_log as $k => $v) {			$shop = db::table('shop')->where(['id' => $v['shopid']])->field('id,backtime,backbili')->find();			if (empty($shop)) {				continue;			}			if (isset($v['endtime']) && isset($shop['backtime']) && (($v['endtime'] + $shop['backtime'] * 86400) < time())) {				$rs[] = db::table('shop_log')->where(['id' => $v['id']])->save(['back' => 1]);				$rs[] = db::table('back_data')->add(['type' => '购物返现', 'userid' => $v['userid'], 'logid' => $v['id'], 'backbili' => $shop['backbili'], 'status' => 0, 'addtime' => time()]);			} else {				continue;			}		}		if (check_arr($rs)) {			return ['处理成功', 1];			db::exec('commit');			db::exec('unlock tables');		} else {			db::exec('rollback');			db::exec('unlock tables');			return ['处理失败'];		}	}	public function sale_data()	{		return [1];		$rs = [];		db::exec('set autocommit=0');		db::exec('lock tables  #pre#sale_log write ,#pre#back_data write');		$sale_log = db::table('sale_log')->where(['status' => 3, 'back' => 2])->limit(100)->select();		if (empty($sale_log)) {			db::exec('rollback');			db::exec('unlock tables');			return ['没有统计项目'];		}		foreach ($sale_log as $k => $v) {			$sale = db::table('sale')->where(['id' => $v['saleid']])->field('id,backtime,backbili')->find();			if (isset($v['endtime']) && isset($shop['backtime']) && (($v['endtime'] + $shop['backtime'] * 86400) < time())) {				$rs[] = db::table('sale_log')->where(['id' => $v['id']])->save(['back' => 1]);				$rs[] = db::table('back_data')->add(['type' => '拍卖返现', 'userid' => $v['userid'], 'logid' => $v['id'], 'backbili' => $sale['backbili'], 'status' => 0, 'addtime' => time()]);			} else {				continue;			}		}		if (check_arr($rs)) {			db::exec('commit');			db::exec('unlock tables');			return ['处理成功', 1];		} else {			$check[] = 0;			db::exec('rollback');			db::exec('unlock tables');			return ['处理失败'];		}	}	public function back()	{		return [1];		$rs = [];		db::exec('set autocommit=0');		db::exec('lock tables  #pre#user_coin write ,#pre#back_cny_log write,#pre#back_cny write');		if (!$id_arr = mc('back')) {			$all_back = db::table('back_cny')->where(['status' => 2])->select();			$id_arr = [];			foreach ($all_back as $k => $v) {				$id_arr[$v['id']] = $v['id'];			}		}		if (empty($id_arr)) {			mc('back', null);			db::exec('rollback');			db::exec('unlock tables');			return ['没有处理项目'];		}		$back = db::table('back_cny')->where(['status' => 2, 'id#in' => $id_arr])->find();		unset($id_arr[$back['id']]);		mc('back', $id_arr, 100);		if (empty($back)) {			mc('back', null);			db::exec('rollback');			db::exec('unlock tables');			return ['没有处理项目'];		}		$str = rtrim($back['week'], ',');		$arr = explode(',', $str);		if (!in_array(date('w'), $arr)) {			db::exec('rollback');			db::exec('unlock tables');			return ['还没开始'];		}		if ($back['ci'] <= $back['jd']) {			db::exec('rollback');			db::exec('unlock tables');			return ['已经返现结束'];		}		if ((time() - $back['endtime']) <= 82800) {			db::exec('rollback');			db::exec('unlock tables');			return ['频繁处理'];		}		$dstr = rtrim($back['data_arr'], ',');		if (!$dstr) {			db::exec('rollback');			db::exec('unlock tables');			return ['无数据'];		}		$data_arr = explode(',', $dstr);		$back_data = db::table('back_cny_data')->where(['status' => 1, 'id#in' => $data_arr])->select();		$ckcoin = $back['ckcoin'];		$fhcoin = $back['fhcoin'];		$mum = 0;		$check = [];		foreach ($back_data as $k => $v) {			$user_coin = db::table('user_coin')->where(['userid' => $v['userid']])->find();			$usernum = $user_coin[$ckcoin];			$num = $usernum * $v['backbili'] / 100;			$rs[] = db::table('user_coin')->where(['userid' => $v['userid']])->set([$fhcoin . '#+' => $num]);			$rs[] = db::table('back_cny_log')->add(['data_id' => $back['id'], 'userid' => $v['userid'], 'title' => $back['title'], 'ckcoin' => $back['ckcoin'], 'fhcoin' => $back['fhcoin'], 'num' => $num, 'mum' => $usernum, 'addtime' => time(), 'status' => 1, 'content' => '', 'ci' => $back['ci'], 'jd' => $back['jd'] + 1, 'sort' => 1, 'bili' => $v['backbili'],]);			$mum += $num;		}		$status = 2;		if ($back['ci'] <= $back['jd'] + 1) {			$status = 1;		}		$rs[] = db::table('back_cny')->where(['id' => $back['id']])->save(['status' => $status, 'endtime' => time(), 'mum' => $back['mum'] + $mum, 'jd' => $back['jd'] + 1,]);		if (check_arr($rs)) {			$check[] = 1;			db::exec('commit');			db::exec('unlock tables');			return ['返现成功'];		} else {			db::exec('rollback');			db::exec('unlock tables');			return ['返现失败'];		}	}	public function fenhong_tsb()	{		$rs = [];		db::exec('set autocommit=0');		db::exec('lock tables  #pre#user_coin write ,#pre#fenhong_tsb_log write,#pre#fenhong_tsb write,#pre#fenhong_tsb_bili write');		if (!$id_arr = mc('fenhong_tsb')) {			$all_newfenhong = db::table('fenhong_tsb')->where(['status' => 2])->select();			$id_arr = [];			foreach ($all_newfenhong as $k => $v) {				$id_arr[$v['id']] = $v['id'];			}		}		if (empty($id_arr)) {			mc('fenhong_tsb', null);			db::exec('rollback');			db::exec('unlock tables');			return ['没有处理项目'];		}		$newfenhong = db::table('fenhong_tsb')->where(['status' => 2, 'id#in' => $id_arr])->find();		unset($id_arr[$newfenhong['id']]);		mc('fenhong_tsb', $id_arr, 10);		if (empty($newfenhong)) {			mc('fenhong_tsb', null);			db::exec('rollback');			db::exec('unlock tables');			return ['没有处理项目'];		}		$str = rtrim($newfenhong['week'], ',');		$arr = explode(',', $str);		if (!in_array(date('w'), $arr)) {			db::exec('rollback');			db::exec('unlock tables');			return ['还没开始'];		}		if ((time() - $newfenhong['endtime']) <= 82800) {			db::exec('rollback');			db::exec('unlock tables');			return ['频繁处理'];		}		$coinname = $newfenhong['ckcoin'];		$newfenhong_bili = db::table('fenhong_tsb_bili')->where(['pid' => $newfenhong['id']])->select();		if (!$newfenhong_bili) {			db::exec('rollback');			db::exec('unlock tables');			return ['未设置比例'];		}		$user_coin = db::table('user_coin')->where([$newfenhong['ckcoin'] . '#!=' => 0])->field('userid,$coinname')->select();		$i = 0;		$check[] = 0;		foreach ($user_coin as $k => $v) {			$num = 0;			$bili = 0;			foreach ($newfenhong_bili as $kk => $vv) {				if ($vv['min'] < $v[$coinname] && $vv['max'] > $v[$coinname]) {					$bili = $vv['bili'] / 100;					$num = $bili * $v[$coinname];					$i += $num;				} else {					continue;				}			}			if ($num == 0) {				continue;			}			$rs[] = db::table('user_coin')->where(['userid' => $v['userid']])->set([$newfenhong['fhcoin'] . '#+' => $num]);			$rs[] = db::table('fenhong_tsb_log')->add(['fenhong_id' => $newfenhong['id'], 'userid' => $v['userid'], 'title' => $newfenhong['title'], 'ckcoin' => $newfenhong['ckcoin'], 'fhcoin' => $newfenhong['fhcoin'], 'num' => $num, 'mum' => $v[$coinname], 'addtime' => time(), 'status' => 1, 'content' => '', 'ci' => $newfenhong['ci'], 'jd' => $newfenhong['jd'] + 1, 'sort' => 1, 'bili' => $bili * 100,]);		}		$status = 2;		if ($newfenhong['ci'] <= $newfenhong['jd'] + 1) {			$status = 1;		}		$rs[] = db::table('fenhong_tsb')->where(['id' => $newfenhong['id']])->save(['status' => $status, 'endtime' => time(), 'mum' => $newfenhong['mum'] + $i, 'jd' => $newfenhong['jd'] + 1,]);		if ($rs) {			db::exec('commit');			db::exec('unlock tables');			return ['分发成功'];		} else {			db::exec('rollback');			db::exec('unlock tables');			return ['分发失败'];		}	}	public function back_cny()	{		db::exec('set autocommit=0');		db::exec('lock tables  #pre#user_coin write ,#pre#back_cny_log write,#pre#back_cny write');		$back_cny = db::table('back_cny')->where(['status' => 1])->select();		if (empty($back_cny)) {			db::exec('rollback');			db::exec('unlock tables');			return ['没有处理项目'];		}		$rs[] = 1;		foreach ($back_cny as $k => $v) {			$str = rtrim($v['week'], ',');			$arr = explode(',', $str);			if (!in_array(date('w'), $arr)) {				continue;			}			if ($v['ci'] <= $v['jd']) {				continue;			}			if ((time() - $v['endtime']) <= 82800) {				continue;			}			$user_coin = db::table("user_coin")->where(["userid" => $v["userid"]])->find($v["coin"]);			if (!$user_coin) {				continue;			}			$num = $v["num"] / $v["ci"];			$jd = $v["jd"] + 1;			$status = 1;			if ($jd >= $v["ci"]) {				$status = 2;			}			$rs[] = db::table("user_coin")->where(["userid" => $v["userid"]])->set([$v["coin"] . "#+" => $num]);			$rs[] = db::table("back_cny")->where(["id" => $v["id"]])->save(["endtime" => time(), "jd" => $jd, "status" => $status]);			$rs[] = db::table("back_cny_log")->add(["userid" => $v["userid"], "title" => $v["title"], "coin" => $v["coin"], "num" => $num, "ci" => $jd, "addtime" => time(), "status" => 1, "pid" => $v["id"]]);		}		if (check_arr($rs)) {			db::exec('commit');			db::exec('unlock tables');			return ['返现成功'];		} else {			db::exec('rollback');			db::exec('unlock tables');			return ['返现失败'];		}	}}