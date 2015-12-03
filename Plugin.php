<?php
/**
 * 会员列表插件
 * 
 * @package HuifengMembers
 * @author 回风
 * @version 0.1.1
 * @dependence 14.10.10-*
 * @link http://huifeng.me
 *

 * version 0.1.1 at 2015-12-1
 * 实现会员头像上传功能
 *
 * version 0.1.0 at 2015-11-19
 * 实现会员列表的基本功能
 * 包括: 添加 删除 修改 排序
 */
class HuifengMembers_Plugin implements Typecho_Plugin_Interface
{
	/**
	 * 激活插件方法,如果激活失败,直接抛出异常
	 * 
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function activate()
	{
		$info = HuifengMembers_Plugin::membersInstall();
		Helper::addPanel(3, 'HuifengMembers/manage-members.php', '会员列表', '管理会员列表', 'administrator');
		Helper::addAction('huifeng-members-edit', 'HuifengMembers_Action');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('HuifengMembers_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('HuifengMembers_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Comments')->contentEx = array('HuifengMembers_Plugin', 'parse');
		return _t($info);
	}

	/**
	 * 禁用插件方法,如果禁用失败,直接抛出异常
	 * 
	 * @static
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function deactivate()
	{
		Helper::removeAction('huifeng-members-edit');
		Helper::removePanel(3, 'HuifengMembers/manage-members.php');
	}

	/**
	 * 获取插件配置面板
	 * 
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form 配置面板
	 * @return void
	 */
	public static function config(Typecho_Widget_Helper_Form $form) {
		$gravatars = new Typecho_Widget_Helper_Form_Element_Text('image_upload', NULL, 'hf_members/', _t('默认头像上传路径'), '请输入默认头像上传路径（相对于插件物理路径）');
		$form->addInput($gravatars);
	}

	/**
	 * 个人用户的配置面板
	 * 
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form
	 * @return void
	 */
	public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

	public static function membersInstall()
	{
		$installDb = Typecho_Db::get(); // 获取数据库实例化对象
		$type = explode('_', $installDb->getAdapterName()); // 用'_'分割字符串:$installDb->getAdapterName()
		$type = array_pop($type); // 弹出最后一个元素
		$prefix = $installDb->getPrefix(); // 获取前缀
		$scripts = file_get_contents('usr/plugins/HuifengMembers/'.$type.'.sql'); // 将整个文件读入字符串
		$scripts = str_replace('typecho_', $prefix, $scripts); // 用'typecho_' 替换 $scripts 里的 $prefix
		$scripts = str_replace('%charset%', 'utf8', $scripts); // 替换字符串里的 ‘%charset%' 为 'utf8'
		$scripts = explode(';', $scripts); // 用' ; ' 分割
		try {
			foreach ($scripts as $script) {
				$script = trim($script);
				if ($script) {
					$installDb->query($script, Typecho_Db::WRITE);
				}
			}
			return '建立会员列表插件数据表，插件启用成功';
		} catch (Typecho_Db_Exception $e) {
			$code = $e->getCode();
			if(('Mysql' == $type && 1050 == $code) ||
			('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
				try {
					$script = 'SELECT `mid`, `name`, `position`, `tel`, `image`, `categories`, `is_onduty`, `field`, `order` from `' . $prefix . 'hf_members`';
					$installDb->query($script, Typecho_Db::READ);
					return '检测到会员列表插件数据表，会员列表插件启用成功';
				} catch (Typecho_Db_Exception $e) {
					$code = $e->getCode();
					if(('Mysql' == $type && 1054 == $code) ||
					('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
						return HuifengMembers_Plugin::membersUpdate($installDb, $type, $prefix);
					}
					throw new Typecho_Plugin_Exception('数据表检测失败，会员列表插件启用失败。错误号：'.$code);
				}
			} else {
				throw new Typecho_Plugin_Exception('数据表建立失败，会员列表插件启用失败。错误号：'.$code);
			}
		}
	}

	// public static function membersUpdate($installDb, $type, $prefix)
	// {
	// 	$scripts = file_get_contents('usr/plugins/HuifengMembers/Update_'.$type.'.sql');
	// 	$scripts = str_replace('typecho_', $prefix, $scripts);
	// 	$scripts = str_replace('%charset%', 'utf8', $scripts);
	// 	$scripts = explode(';', $scripts);
	// 	try {
	// 		foreach ($scripts as $script) {
	// 			$script = trim($script);
	// 			if ($script) {
	// 				$installDb->query($script, Typecho_Db::WRITE);
	// 			}
	// 		}
	// 		return '检测到旧版本会员列表数据表，升级成功';
	// 	} catch (Typecho_Db_Exception $e) {
	// 		$code = $e->getCode();
	// 		if(('Mysql' == $type && 1060 == $code) ) {
	// 			return '会员列表数据表已经存在，插件启用成功';
	// 		}
	// 		throw new Typecho_Plugin_Exception('会员列表插件启用失败。错误号：'.$code);
	// 	}
	// }

	public static function form($action = NULL)
	{
		/** 构建表格 */
		$options = Typecho_Widget::widget('Widget_Options');
		$form = new Typecho_Widget_Helper_Form(Typecho_Common::url('/action/huifeng-members-edit', $options->index),
		Typecho_Widget_Helper_Form::POST_METHOD);
		$form->setAttribute('enctype','multipart/form-data'); // 表示可以上传数据

		/* 添加隐藏域限制上传大小 */
		$maxfilesize= new Typecho_Widget_Helper_Form_Element_Hidden('MAX_FILE_SIZE', NULL, '1000000', NULL, NULL);
		$form->addInput($maxfilesize); // 限制大小的隐藏域应在上传标签之前

		/** 头像 */
		$image_upload = new Typecho_Widget_Helper_Form_Element_Text('image_upload', NULL, NULL, _t('上传头像'), NULL); // 用于头像图片上传
		$image_upload->input->setAttribute('type','file');
		$image = new Typecho_Widget_Helper_Form_Element_Text('image', NULL, NULL, _t('头像'), NULL); // 获取数据库里的头像地址
		$img_show = new Typecho_Widget_Helper_Layout('img', NULL);
		$img_src = '/usr/plugins/HuifengMembers/nopic.jpg';
		if ($image->getAttribute('src'!='')) {
			$img_src=$image->getAttribute('src');
		}
		$img_show->setAttribute('src', $img_src);
		$img_show->setAttribute('height', '180');
		$img_show->setAttribute('style', 'margin-top: 4em;max-width: 180px;max-height: 180px;');
		$form->addItem($img_show);
		$form->addInput($image_upload);
		$form->addInput($image);
		
		/** 会员名称 */
		// Typecho_Widget_Helper_Form_Element_Text ($name=NULL, array $options=NULL, $value=NULL, $label=NULL, $categories=NULL)
		$name = new Typecho_Widget_Helper_Form_Element_Text('name', NULL, NULL, _t('会员名称*'), _t('请填写真实姓名'));
		$form->addInput($name);
		
		/** 所在部门 */
		$position = new Typecho_Widget_Helper_Form_Element_Text('position', NULL, NULL, _t('所在部门*'), _t('请正确填写所在部门科室'));
		$form->addInput($position);
		
		/** 联系电话 */
		$tel = new Typecho_Widget_Helper_Form_Element_Text('tel', NULL, NULL, _t('联系电话'), NULL);
		$form->addInput($tel);

		/** 分类 */
		$categories =  new Typecho_Widget_Helper_Form_Element_Text('categories', NULL, NULL, _t('分类'));
		$form->addInput($categories);

		/** 是否值班 */
		$is_onduty = new Typecho_Widget_Helper_Form_Element_Radio('is_onduty',
			array('否'=>_t('否'),'是'=>_t('是')),'否',_t('是否值班'));
		$form->addInput($is_onduty);

		/** 自定义数据 */
		$field = new Typecho_Widget_Helper_Form_Element_Text('field', NULL, NULL, _t('自定义数据'), _t('该项用于用户自定义数据扩展'));
		$form->addInput($field);
		
		/** 会员动作 */
		$do = new Typecho_Widget_Helper_Form_Element_Hidden('do');
		$form->addInput($do);
		
		/** 会员主键 */
		$mid = new Typecho_Widget_Helper_Form_Element_Hidden('mid');
		$form->addInput($mid);
		
		/** 提交按钮 */
		$submit = new Typecho_Widget_Helper_Form_Element_Submit();
		$submit->input->setAttribute('class', 'btn primary');
		$form->addItem($submit);
		$request = Typecho_Request::getInstance();
		if (isset($request->mid) && 'insert' != $action) {
			/** 更新模式 */
			$db = Typecho_Db::get();
			$prefix = $db->getPrefix();
			$member = $db->fetchRow($db->select()->from($prefix.'hf_members')->where('mid = ?', $request->mid));
			if (!$member) {
				throw new Typecho_Widget_Exception(_t('会员不存在'), 404);
			}
			$name->value($member['name']);
			$position->value($member['position']);
			$tel->value($member['tel']);
			$image->value($member['image']);
			$categories->value($member['categories']);
			$is_onduty->value($member['is_onduty']);
			$field->value($member['field']);
			$do->value('update');
			$mid->value($member['mid']);
			$submit->value(_t('编辑会员'));
			$_action = 'update';
		} else {
			$do->value('insert');
			$submit->value(_t('增加会员'));
			$_action = 'insert';
		}
		if (empty($action)) {
			$action = $_action;
		}
		/** 给表单增加规则 */
		if ('insert' == $action || 'update' == $action) {
			$name->addRule('required', _t('必须填写会员名称'));
			$name->addRule('xssCheck', _t('请勿在会员名称栏输入特殊字符'));
			$position->addRule('required', _t('必须填写所在部门'));
			$position->addRule('xssCheck', _t('请勿在所在部门栏输入特殊字符'));
			// $position->addRule('url', _t('不是一个合法的url'));
			// $image->addRule('image', _t('不是一个合法的图片地址'));
			$tel->addRule('required', _t('必须填写联系电话'));
			$tel->addRule('isInteger', _t('电话号码必须是无符号整数'));
			$tel->addRule('minLength', _t('电话号码不得小于6位'), 6);
			$tel->addRule('maxLength', _t('电话号码不得大于11位'), 12);
			// $image->addRule('required', _t('必须上传头像图片'));
		}
		if ('update' == $action) {
			$mid->addRule('required', _t('会员主键不存在'));
			$mid->addRule(array(new HuifengMembers_Plugin, 'HuifengMembersExists'), _t('会员不存在'));
		}
		return $form;
	}

	public static function HuifengMembersExists($mid)
	{
		$db = Typecho_Db::get();
		$prefix = $db->getPrefix();
		$member = $db->fetchRow($db->select()->from($prefix.'hf_members')->where('mid = ?', $mid)->limit(1));
		return $member ? true : false;
	}

	/**
	 * 图片上传处理
	 *
	 * @access public
	 * @param array $file 上传的文件
	 * @return mixed
	 */
	public static function uploadHandle($file)
	{
		if (empty($file['image_upload'])) {
			return false;
		}

		$imgname = preg_split("(\/|\\|:)",$file['image_upload']);
		$file['image_upload'] = array_pop($imgname);

		//扩展名
		$ext = Typecho_Widget_Upload::getSafeName($file['image_upload']);
		if (!Typecho_Widget_Upload::checkFileType($ext) || Typecho_Common::isAppEngine()) {
			return false;
		}

		//创建上传目录
		$imgdir = Typecho_Widget::widget('Widget_Options')->plugin('HuifengMembers')->imagepath;
		if (!is_dir($imgdir)) {
			Typecho_Widget_Upload::makeUploadDir($imgdir);
		}

		//获取文件名
		$imgname = sprintf('%u',crc32(uniqid())).'.'.$ext;
		$imgpath = $imgdir.$imgname;

		if (!isset($file['tmp_name'])) {
			return false;
		}

		//本地上传		
		if (!@move_uploaded_file($file['tmp_name'],$imgpath)) {
			return false;
		}


		return array(
			'image_upload'=>$imgname,
			'title'=>$file['image_upload'],
			'size'=>$file['size']
		);
	}

	/**
	 * 控制输出格式
	 */
	public static function output_str($pattern=NULL, $members_num=0, $position=NULL)
	{
		$options = Typecho_Widget::widget('Widget_Options');
		if (!isset($options->plugins['activated']['HuifengMembers'])) {
			return '会员列表插件未激活';
		}
		if (!isset($pattern) || $pattern == "" || $pattern == NULL || $pattern == "SHOW_NAME") {
			$pattern = "{name}";
		} else if ($pattern == "SHOW_IMG") {
			$pattern = "{image}";
		} else if ($pattern == "SHOW_TEL") {
			$pattern = "{tel}";
		} else if ($pattern == "SHOW_POSITION") {
			$pattern = "{categories}";
		} else if ($pattern == "SHOW_MIX") {
			$pattern = "
			<div class=\"row\">
			<div class=\"col-md-1\"><a href=\"{image}\" title=\"点击放大\" target=\"_blank\"><img class=\"img-circle\" width=\"60\" height=\"60\" src=\"{image}\" data-html=\"true\" alt=\"{name}\" /></a></div>
			<div class=\"col-md-2 bhl_field_inner\"><p>姓名：{name}</p></div>
			<div class=\"col-md-3 bhl_field_inner\"><p>所在单位：{position}</p></div>
			<div class=\"col-md-3 bhl_field_inner\"><p>联系电话：{tel}</p></div>
			<div class=\"col-md-3 bhl_field_inner\"><p>{categories}</p></div>
			</div>
			<hr class=\"bhl_hr\">
			";
		} else if ($pattern == "SHOW_POP") {
			$pattern = "
			<a href=\"javascript:;\">
			<img class=\"img-circle\" src=\"{image}\" data-toggle=\"popover\" data-trigger=\"hover\" data-placement=\"bottom\" data-html=\"true\" title=\"\" data-content=\"单位:{position}<br>联系:{tel}\" data-original-title=\"姓名：{name}\">
			</a>
			";
		}
		$db = Typecho_Db::get();
		$prefix = $db->getPrefix();
		$options = Typecho_Widget::widget('Widget_Options');
		$nopic_url = Typecho_Common::url('/usr/plugins/HuifengMembers/nopic.jpg', $options->siteUrl);
		$sql = $db->select()->from($prefix.'hf_members');
		if (!isset($position) || $position == "") {
			$position = NULL;
		}
		if ($position) {
			$sql = $sql->where('position=?', $position);
		}
		$sql = $sql->order($prefix.'hf_members.order', Typecho_Db::SORT_ASC);
		$members_num = intval($members_num);
		if ($members_num > 0) {
			$sql = $sql->limit($members_num);
		}
		$members = $db->fetchAll($sql);
		$str = "";
		foreach ($members as $member) {
			if ($member['image'] == NULL) {
				$member['image'] = $nopic_url;
			}
			$str .= str_replace(
				array('{mid}', '{name}', '{position}', '{tel}', '{title}', '{categories}', '{image}', '{is_onduty}', '{field}'),
				array($member['mid'], $member['name'], $member['position'], $member['tel'], $member['categories'], $member['categories'], $member['image'], $member['is_onduty'], $member['field']),
				$pattern
			);
		}
		return $str;
	}

	//输出
	public static function output($pattern=NULL, $members_num=0, $position=NULL)
	{
		echo HuifengMembers_Plugin::output_str($pattern, $members_num, $position);
	}
}
