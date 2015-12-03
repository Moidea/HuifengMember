<?php
// include 'common.php';
include 'header.php';
include 'menu.php';
?>


<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main manage-metas">
                <!-- <div class="col-mb-12">
                    <ul class="typecho-option-tabs clearfix">
                        <li class="current"><a href="<?php $options->adminUrl('extending.php?panel=HuifengMembers%2Fmanage-members.php'); ?>"><?php _e('会员列表'); ?></a></li>
                    </ul>
                </div> -->

                <div class="col-mb-12 col-tb-8" role="main">                  
                    <?php
			$prefix = $db->getPrefix();
			$members = $db->fetchAll($db->select()->from($prefix.'hf_members')->order($prefix.'hf_members.order', Typecho_Db::SORT_ASC));
                    ?>
                    <form method="post" name="manage_categories" class="operate-form">
                    <div class="typecho-list-operate clearfix">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a lang="<?php _e('你确认要删除这些会员吗?'); ?>" href="<?php $options->index('/action/huifeng-members-edit?do=delete'); ?>"><?php _e('删除'); ?></a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="typecho-table-wrap">
                        <table class="typecho-list-table">
                            <colgroup>
                                <col width="5%"/>
                                <col width="17%"/>
                                <col width="17%"/>
                                <col width="20%"/>
                                <col width="17%"/>
                                <col width="14%"/>
                                <col width="10%"/>
                            </colgroup>
                            <thead>
                                <tr>
                                          <th></th>
				<th><?php _e('会员名称'); ?></th>
				<th><?php _e('所在部门'); ?></th>
				<th><?php _e('联系电话'); ?></th>
                                          <th><?php _e('分类'); ?></th>
				<th><?php _e('是否值班'); ?></th>
                                          <th><?php _e('头像'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
			<?php if(!empty($members)): $alt = 0;?>
			<?php foreach ($members as $member): ?>
                                <tr id="mid-<?php echo $member['mid']; ?>">
                                          <td><input type="checkbox" value="<?php echo $member['mid']; ?>" name="mid[]"/></td>
				<td><a href="<?php echo $request->makeUriByRequest('mid=' . $member['mid']); ?>" title="点击编辑"><?php echo $member['name']; ?></a>
				<td><?php echo $member['position']; ?></td>
				<td><?php echo $member['tel']; ?></td>
                                          <td><?php echo $member['categories']; ?></td>
                                          <td><?php echo $member['is_onduty']; ?></td>
				<td><?php
					if ($member['image']) {
						echo '<a href="'.$member['image'].'" title="点击放大" target="_blank"><img class="avatar" src="'.$member['image'].'" alt="'.$member['name'].'" width="32" height="32"/></a>';
					} else {
						$options = Typecho_Widget::widget('Widget_Options');
						$nopic_url = Typecho_Common::url('/usr/plugins/HuifengMembers/nopic.jpg', $options->siteUrl);
						echo '<img class="avatar" src="'.$nopic_url.'" alt="NOPIC" width="32" height="32"/>';
					}
				?></td>

                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="7"><h6 class="typecho-list-table-title"><?php _e('没有任何会员'); ?></h6></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    </form>
                </div>
                <div class="col-mb-12 col-tb-4" role="form">
                    <?php HuifengMembers_Plugin::form()->render(); ?>

                    <script type="text/javascript" src="/usr/plugins/HuifengMembers/fullAvatarEditor/scripts/swfobject.js"></script>
                    <script type="text/javascript" src="/usr/plugins/HuifengMembers/fullAvatarEditor/scripts/fullAvatarEditor.js"></script>
                    <script type="text/javascript">
                        swfobject.addDomLoadEvent(function () {
                            var swf = new fullAvatarEditor("fullAvatarEditor.swf", "expressInstall.swf", "swfContainer", {
                                    id : "swf",
                                    upload_url : "/upload.php?userid=999&username=looselive",
                                    method : "post",
                                    src_url : "/samplePictures/Default.jpg",
                                    src_upload : 2
                                }, function (msg) {
                                    switch(msg.code)
                                    {
                                        case 1 : alert("页面成功加载了组件！");break;
                                        case 2 : alert("已成功加载图片到编辑面板。");break;
                                        case 3 :
                                            if(msg.type == 0)
                                            {
                                                alert("摄像头已准备就绪且用户已允许使用。");
                                            }
                                            else if(msg.type == 1)
                                            {
                                                alert("摄像头已准备就绪但用户未允许使用！");
                                            }
                                            else
                                            {
                                                alert("摄像头被占用！");
                                            }
                                        break;
                                        case 5 : 
                                            if(msg.type == 0)
                                            {
                                                if(msg.content.sourceUrl)
                                                {
                                                    alert("原图片已成功保存至服务器，url为：\n" +　msg.content.sourceUrl);
                                                }
                                                alert("头像已成功保存至服务器，url为：\n" + msg.content.avatarUrls.join("\n"));
                                            }
                                        break;
                                    }
                                }
                            );
                            document.getElementById("upload").onclick=function(){
                                swf.call("upload");
                            };
                        });
                    </script>
                    
                </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
?>

<script type="text/javascript">
(function () {
    $(document).ready(function () {
        var table = $('.typecho-list-table').tableDnD({
            onDrop : function () {
                var ids = [];

                $('input[type=checkbox]', table).each(function () {
                    ids.push($(this).val());
                });

                $.post('<?php $options->index('/action/huifeng-members-edit?do=order'); ?>', 
                    $.param({mid : ids}));

                $('tr', table).each(function (i) {
                    if (i % 2) {
                        $(this).addClass('even');
                    } else {
                        $(this).removeClass('even');
                    }
                });
            }
        });

        table.tableSelectable({
            checkEl     :   'input[type=checkbox]',
            rowEl       :   'tr',
            selectAllEl :   '.typecho-table-select-all',
            actionEl    :   '.dropdown-menu a'
        });

        $('.btn-drop').dropdownMenu({
            btnEl       :   '.dropdown-toggle',
            menuEl      :   '.dropdown-menu'
        });

        $('.dropdown-menu button.merge').click(function () {
            var btn = $(this);
            btn.parents('form').attr('action', btn.attr('rel')).submit();
        });

        <?php if (isset($request->mid)): ?>
        $('.typecho-mini-panel').effect('highlight', '#AACB36');
        <?php endif; ?>
    });
})();
</script>
<?php include 'footer.php'; ?>
