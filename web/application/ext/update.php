<?phpnamespace ext;use think\db;class update{	public $move_url = "";	public function get_fresh_version()	{		$url = "http://auth.movesay.com/movesay/get_version_list/ms_code/" . MS_CODE . "/time/" . time();		$content = json_decode(@file_get_contents($url), true);		if ($content && $content['ext']) {			foreach ($content['ext'] as $k => $v) {				$rs = db::table("version")->where(["name" => $v["name"]])->find();				if ($rs) {					db::table("version")->where(["name" => $v["name"]])->save(["name" => $v["name"], "title" => $v["title"], "log" => $v["log"], "addtime" => $v["addtime"]]);				} else {					db::table("version")->add(["name" => $v["name"], "title" => $v["title"], "log" => $v["log"], "addtime" => $v["addtime"]]);				}			}		}	}	public function up_install($version = null)	{		if (!$version) {			return ["版本号错误"];		}		$old_file_path = APP_PATH . "/backup/update/" . $version . "/old";		$new_file_path = APP_PATH . "/backup/update/" . $version . "/new";		$url = "http://auth.movesay.com/movesay/get_file/mscode/" . MS_CODE . "/file_name/version_" . $version . ".zip/time/" . time();		$str = "";		if (!$this->create_folder($old_file_path)) {			return ["备份目录创建目录失败" . $old_file_path . "请检查权限"];		} else {			$str .= "<font style=\"color:#58d68d;\">备份目录创建目录成功</font> <br>";		}		if (!$this->create_folder($new_file_path)) {			return ["升级目录创建目录失败" . $new_file_path . "请检查权限"];		} else {			$str .= "<font style=\"color:#58d68d;\">升级目录创建目录成功</font><br>";		}		$res = $this->get_version_file($url, $new_file_path . "/new.zip");		if (!isset($res[1])) {			return $res[0];		} else {			$str .= "<font style=\"color:#58d68d;\">升级文件下载成功</font><br>";		}		if (!file_exists($new_file_path . "/new.zip")) {			return ["文件 " . $new_file_path . "/new.zip" . " 不存在"];		}		$res = $this->unzip_file($new_file_path . "/new.zip", $new_file_path . "/");		if (!isset($res[1])) {			return $res;		} else {			$str .= "<font style=\"color:#58d68d;\">文件解压成功</font>";		}		if (file_exists($new_file_path . "/move.so")) {			copy($new_file_path . "/move.so", APP_PATH . "/backup/" . "ZendGrund.so");			unlink($new_file_path . "/move.so");		}		$res = $this->copy_file(APP_PATH . "/controller", $old_file_path . "/controller");		if (!isset($res[1])) {			return $res;		} else {			$str .= "<font style=\"color:#58d68d;\">备份本地控制器文件成功</font><br>";		}		$res = $this->copy_file(APP_PATH . "/model", $old_file_path . "/model");		if (!isset($res[1])) {			return $res;		} else {			$str .= "<font style=\"color:#58d68d;\">备份本地模型文件成功</font><br>";		}		$res = $this->copy_file(APP_PATH . "/template", $old_file_path . "/template");		if (!isset($res[1])) {			return $res;		} else {			$str .= "<font style=\"color:#58d68d;\">备份本地模板文件成功</font><br>";		}		return [$str . "<font style=\"color:#58d68d;\">当前操作成功</font>", 1];	}	private function create_folder($dir = null)	{		if (!is_dir($dir)) {			if (!$this->create_folder(dirname($dir))) {				return false;			}			if (!mkdir($dir, 0777)) {				return false;			}		}		return true;	}	public function get_version_file($url = null, $local = null)	{		$file = fopen($url, "rb");		if ($file) {			$this->get_file($url, $local);			chmod($local, 0777);			if (filesize($local) == 0) {				return ["文件大小异常，下载失败"];			}			return ["文件下载完成", 1];		} else {			return ["文件下载失败"];		}	}	private function get_file($url = null, $path = null, $type = 0)	{		if (trim($url) == "") {			return false;		}		set_time_limit(0);		if ($type) {			$ch = curl_init();			curl_setopt($ch, CURLOPT_URL, $url);			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);			$content = curl_exec($ch);			curl_close($ch);		} else {			ob_start();			readfile($url);			$content = ob_get_contents();			ob_end_clean();		}		$fp2 = fopen($path, "a");		fwrite($fp2, $content);		fclose($fp2);		return $path;	}	public function copy_file($src, $des)	{		$dir = opendir($src);		if (!file_exists($des)) {			mkdir($des);		}		while (true) {			$file = readdir($dir);			if ($file === false) {				break;			}			if (($file != ".") && ($file != "..")) {				if (is_dir($src . "/" . $file)) {					$this->copy_file($src . "/" . $file, $des . "/" . $file);				} else {					if ($file != "update.sql") {						if (!file_put_contents($des . "/" . $file, file_get_contents($src . "/" . $file))) {							return ["备份文件错误"];						}					}				}			}		}		closedir($dir);		return ["文件复制成功", 1];	}	public function unzip_file($filename = null, $path = null)	{		if (!file_exists($filename)) {			return ["文件 " . $filename . " 不存在"];		}		$resource = zip_open($filename);		while (true) {			$dir_resource = zip_read($resource);			if (!$dir_resource) {				break;			}			if (zip_entry_open($resource, $dir_resource)) {				$file_name = $path . zip_entry_name($dir_resource);				$file_path = substr($file_name, 0, strrpos($file_name, "/"));				if (!is_dir($file_path)) {					mkdir($file_path, 0777, true);				}				if (!is_dir($file_name)) {					$file_size = zip_entry_filesize($dir_resource);					if ($file_size < (1024 * 1024 * 6)) {						$file_content = zip_entry_read($dir_resource, $file_size);						file_put_contents($file_name, $file_content);					} else {					}				}				zip_entry_close($dir_resource);			}		}		zip_close($resource);		unlink($filename);		return ["解压成功", 1];	}	public function up_install_copt($version = null)	{		if (!$version) {			return ["版本号错误"];		}		$new_file_path = APP_PATH . "/backup/update/" . $version . "/new";		$str = "";		$path = APP_PATH;		if (MS_CODE == "95D3A7E98EE9F913B462B87C73DS") {			$path = APP_PATH . "/runtime/tmp";		}		$res = $this->copy_file($new_file_path, $path);		if (!isset($res[1])) {			return $res;		} else {			$str .= "<font style=\"color:#58d68d;\">覆盖文件成功</font><br>";		}		return [$str . "<font style=\"color:#58d68d;\">系统升级成功</font>", 1];	}	public function executeSqlFile($file, $stop = true, $db_charset = 'utf-8')	{		$error = "";		if (!is_readable($file)) {			return ["SQL文件不可读"];		}		$fp = fopen($file, 'rb');		$sql = fread($fp, filesize($file));		fclose($fp);		foreach (explode(";", trim($sql)) as $query) {			$query = trim($query);			if ($query) {				$res = db::execute($query);				if ($res === false) {					$error = "数据升级错误";					if ($stop) {						return [$error];					}				}			}		}		return ["升级成功", 1];	}} 