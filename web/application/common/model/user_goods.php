<?phpnamespace app\common\model;use think\db;use Move\ext\page;class user_goods extends common{	public function pages($where = null, $size = 10)	{		$count = db::table("user_goods")->where($where)->count();		$PageObj = new page($count, $size);		$show = $PageObj->show();		$list = db::table("user_goods")->where($where)->order("id desc")->limit($PageObj->firstRow, $PageObj->listRows)->select();		return ["list" => $list, "show" => $show];	}	public function lists($uid = NULL)	{		if ($uid === NULL) {			return db::table("user_goods")->select();		} else {			return db::table("user_goods")->where(["userid" => $uid])->select();		}	}	public function info_by_id($uid, $id)	{		return db::table("user_goods")->where(["userid" => $uid, "id" => $id])->select();	}	public static function info($uid, $id)	{		return db::table("user_goods")->where(["userid" => $uid, "id" => $id])->find();	}}