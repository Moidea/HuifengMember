<?php
class HuifengMembers_Action extends Typecho_Widget implements Widget_Interface_Do
{
	private $db;
	private $options;
	private $prefix;


	/**
	 * 执行上传图片
	 *
	 * @access public
	 * @return void
	 */
	public function uploadImage()
	{
		if (!empty($_FILES)) {
			$file = array_pop($_FILES);
			if (0==$file['error']&&is_uploaded_file($file['tmp_name'])) {

				$file['image_upload'] = urldecode($file['image_upload']);

				$result = HuifengMembers_Plugin::uploadHandle($file);

				// if (false!==$result) {
				// 	$this->response->throwJson(array(array(
				// 		'image_upload'=>$result['image_upload'],
				// 		'title'=>$result['title'],
				// 		'bytes'=>number_format(ceil($result['size']/1024)).' Kb'
				// 		)));
				// }
			}
		}
		// $this->response->throwJson(false);

	}
			
	public function insertMember()
	{
		if (HuifengMembers_Plugin::form('insert')->validate()) {
			$this->response->goBack();
		}
		/** 取出数据 */
		$member = $this->request->from('name', 'position', 'tel', 'image', 'categories', 'is_onduty', 'field');
		$member['order'] = $this->db->fetchObject($this->db->select(array('MAX(order)' => 'maxOrder'))->from($this->prefix.'hf_members'))->maxOrder + 1;

		/** 插入数据 */
		$member['mid'] = $this->db->query($this->db->insert($this->prefix.'hf_members')->rows($member));

		/** 设置高亮 */
		$this->widget('Widget_Notice')->highlight('link-'.$member['mid']);

		/** 提示信息 */
		$this->widget('Widget_Notice')->set(_t('会员 <a href="%s">%s</a> 已经被增加',
		$member['position'], $member['name']), NULL, 'success');

		/** 转向原页 */
		$this->response->redirect(Typecho_Common::url('extending.php?panel=HuifengMembers%2Fmanage-members.php', $this->options->adminUrl));
	}

	public function updateMember()
	{
		if (HuifengMembers_Plugin::form('update')->validate()) {
			$this->response->goBack();
		}

		/** 取出数据 */
		$member = $this->request->from('mid', 'name', 'tel', 'image', 'position', 'categories', 'is_onduty', 'field');

		/** 更新数据 */
		$this->db->query($this->db->update($this->prefix.'hf_members')->rows($member)->where('mid = ?', $member['mid']));

		/** 设置高亮 */
		$this->widget('Widget_Notice')->highlight('member-'.$member['mid']);

		/** 提示信息 */
		$this->widget('Widget_Notice')->set(_t('会员 <a href="%s">%s</a> 已经被更新',
		$member['position'], $member['name']), NULL, 'success');

		/** 转向原页 */
		$this->response->redirect(Typecho_Common::url('extending.php?panel=HuifengMembers%2Fmanage-members.php', $this->options->adminUrl));
	}

    public function deleteMember()
    {
        $mids = $this->request->filter('int')->getArray('mid');
        $deleteCount = 0;
        if ($mids && is_array($mids)) {
            foreach ($mids as $mid) {
                if ($this->db->query($this->db->delete($this->prefix.'hf_members')->where('mid = ?', $mid))) {
                    $deleteCount ++;
                }
            }
        }
        /** 提示信息 */
        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('会员已经删除') : _t('没有会员被删除'), NULL,
        $deleteCount > 0 ? 'success' : 'notice');
        
        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('extending.php?panel=HuifengMembers%2Fmanage-members.php', $this->options->adminUrl));
    }

    public function orderMember()
    {
        $members = $this->request->filter('int')->getArray('mid');
        if ($members && is_array($members)) {
		foreach ($members as $order => $mid) {
			$this->db->query($this->db->update($this->prefix.'hf_members')->rows(array('order' => $order + 1))->where('mid = ?', $mid));
		}
        }
    }

	public function action()
	{
		$this->db = Typecho_Db::get();
		$this->prefix = $this->db->getPrefix();
		$this->options = Typecho_Widget::widget('Widget_Options');
		$this->on($this->request->is('do=upload'))->uploadImage();
		$this->on($this->request->is('do=insert'))->insertMember();
		$this->on($this->request->is('do=update'))->updateMember();
		$this->on($this->request->is('do=delete'))->deleteMember();
		$this->on($this->request->is('do=order'))->orderMember();
		$this->response->redirect($this->options->adminUrl);
	}
}
