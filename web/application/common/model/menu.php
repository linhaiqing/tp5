<?phpnamespace app\common\model;use think\db;class menu extends common{	public function get_lists()	{		return db::table("sys_menu")->order("sort asc")->select();	}	public function find($where = [])	{		return db::table("sys_menu")->where($where)->find();	}	public function daohang()	{		$res["top_lists"] = db::table("sys_menu")->where(["pid" => 0, "hide" => 0])->order("sort asc")->select();		$controller_name = config("controller_name");		$action_name = config("action_name");		$cur_menu = db::table("sys_menu")->where(["url" => $controller_name . "/" . $action_name, "pid#>" => 0])->find();		if ($cur_menu) {			$p_menu = db::table("sys_menu")->where(["id" => $cur_menu["pid"]])->find();			if (!$p_menu) {				exit("当前菜单未指定父菜单");			}			if ($p_menu["pid"] == 0) {				$res["cur"]["pname"] = $cur_menu["group"];				$res["cur"]["pid"] = $p_menu["id"];				$res["cur"]["left_id"] = $cur_menu["id"];				$left_lists = db::table("sys_menu")->where(["pid" => $cur_menu["pid"], "hide" => 0])->order("sort asc")->select();			} else {				$pp_menu = db::table("sys_menu")->where(["id" => $p_menu["pid"]])->find();				if ($pp_menu["pid"] != 0) {					exit("当前菜单层次超过三层");				}				$res["cur"]["pname"] = $p_menu["group"];				$res["cur"]["pid"] = $pp_menu["id"];				$res["cur"]["left_id"] = $p_menu["id"];				$left_lists = db::table("sys_menu")->where(["pid" => $p_menu["pid"], "hide" => 0])->order("sort asc")->select();			}			foreach ($left_lists as $val) {				$res["left_lists"][$val["group"]][] = $val;			}		} else {			$res["left_lists"] = false;		}		return $res;	}}