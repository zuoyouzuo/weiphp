<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
namespace Home\Controller;

use Think\Controller;

/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class UserTagController extends Controller {
	var $model = '';
	var $syc_wechat = false; // 是否需要与微信端同步，目前只有认证的订阅号和认证的服务号可以同步
	function _initialize() {
		$this->model = $this->getModel ( 'user_tag' );
                
	}
	// 通用插件的列表模型
	public function lists() {
//	    $map['token']=get_token();
//	    session ( 'common_condition' ,$map);
//	    $this->assign('search_url',U('lists',array('mdm'=>$_GET['mdm'])));
//		parent::common_lists ( $this->model, 0, 'Addons/lists' );
            $normal_tips = '';
		if ($this->syc_wechat) {
			$this->updateWechatTag ();

			$normal_tips = '温馨提示：当前标签数据会与微信端的标签实时同步，需要删除标签请到微信后台删除。';
			// 搜索按钮
			$this->assign('search_url',U('lists',array('mdm'=>$_GET['mdm'])));

			$this->assign ( 'check_all', false );
			$this->assign ( 'del_button', false );
		}
		$map ['token'] = get_token ();
		$map ['manager_id'] = $this->mid;
		session ( 'common_condition', $map );

		$list_data = $this->_get_model_list ( $this->model, 0, 'id asc' );
		
		if ($this->syc_wechat) {
			$grid = array_pop ( $list_data ['list_grids'] );
			$grid ['href'] = str_replace ( ',[DELETE]|删除', '', $grid ['href'] );
			$grid ['href'] = $grid['href'].',toGroupDetail&group_id=[id]|查看详情';
			array_push ( $list_data ['list_grids'], $grid );
		}

		$this->assign ( $list_data );
		$this->assign ( 'normal_tips', $normal_tips );

		$this->display ( 'Addons/lists' );
	}
	
	// 通用插件的编辑模型
	public function edit($id = 0) {
		//parent::common_edit ( $this->model, 0, 'Addons/edit' );
                $model = $this->model;
		$id || $id = I ( 'id' );

		// 获取数据
		$data = M ( get_table_name ( $model ['id'] ) )->find ( $id );
		$data || $this->error ( '数据不存在！' );
                if (IS_POST) {
			$act = 'save';
			$has=$this->checkTitle($_POST['title'],$id);
			if ($has > 0){
			    $this->error('该分组名已经存在！');
			}
			$Model = D ( parse_name ( get_table_name ( $model ['id'] ), 1 ) );
			// 获取模型的字段信息
			$Model = $this->checkAttr ( $Model, $model ['id'] );
			if ($Model->create () && $Model->$act ()) {

				$title = I ( 'title' );
				if ($this->syc_wechat && $title != $data ['title'] && ! empty ( $data ['wechat_group_id'] )) {
					// 修改的用户组名同步到微信端
					$url = 'https://api.weixin.qq.com/cgi-bin/tags/update?access_token=' . $access_token;

					$param ['group'] ['id'] = $data ['wechat_group_id'];
					$param ['group'] ['name'] = $title;
					$param = JSON ( $param );
					$res = post_data ( $url, $param );
				}

				$this->success ( '保存' . $model ['title'] . '成功！', U ( 'lists?model=' . $model ['name'] ) );
			} else {
				$this->error ( $Model->getError () );
			}
		} else {
			$fields = get_model_attribute ( $model ['id'] );

			$this->assign ( 'fields', $fields );
			$this->assign ( 'data', $data );
			$this->meta_title = '编辑' . $model ['title'];

			$this->display ( 'Addons/edit' );
		}
	}
	
	// 通用插件的增加模型
	public function add($model = null, $templateFile = '') {
		//parent::common_add ( $this->model, 'Addons/add' );
            is_array ( $model ) || $model = $this->model;
		if (IS_POST) {			
			$_POST ['manager_id'] = $this->mid;
			$_POST ['token'] = get_token ();
			$has=$this->checkTitle($_POST['title']);
			if ($has > 0){
			    $this->error('该标签已经存在！');
			}
			$Model = D ( parse_name ( get_table_name ( $model ['id'] ), 1 ) );
			// 获取模型的字段信息
			$Model = $this->checkAttr ( $Model, $model ['id'] );
			if ($Model->create () && $id = $Model->add ()) {
				$this->success ( '添加' . $model ['title'] . '成功！', U ( 'lists?model=' . $model ['name'], $this->get_param ) );
			} else {
				$this->error ( $Model->getError () );
			}
		} else {
			$fields = get_model_attribute ( $model ['id'] );
			$this->assign ( 'fields', $fields );
			$this->meta_title = '新增' . $model ['title'];

			$this->display ( 'Addons/add' );
		}
	}
	
	// 通用插件的删除模型
	public function del() {
		parent::common_del ( $this->model );
	}
        function checkTitle($title,$id=0){           
            $tLen = strlen($title);
            if ($tLen > 60) {
                $this->error('标签名称不能超过60个字符，或20个汉字！');
            }
            $zStr = preg_replace('/[^\x{4e00}-\x{9fa5}]/u', '', $title);
            $zLen=strlen($zStr);
            $zStr = preg_replace('/[^A-Za-z0-9]/u', '', $title);
            $yLen=strlen($zStr);
            if ($zLen + $yLen != $tLen){
                $this->error('标签名称不能有特殊字符！');
            }
            $map['title']=$title;
            $map['manager_id']=$this->mid;
            $map['token']=get_token();
            if ($id){
                $map['id']=array('neq',$id);
            }
            $count=M('user_tag')->where($map)->count();
            return intval($count);
        }
        // 与微信的标签保持同步
	function updateWechatTag() {
		// 先取当前用户组数据
		$map ['token'] = get_token ();
		$map ['manager_id'] = $this->mid;
		$map ['type'] = 1;
		$group_list = M ( 'auth_group' )->where ( $map )->field ( 'id,title,wechat_group_id,wechat_group_name,wechat_group_count' )->select ();
		foreach ( $group_list as $g ) {
			if ($g['wechat_group_id']==-1){
			    $ournew[]=$g;
			}else{
			    $groups [$g ['wechat_group_id']] = $g;
			}
		}
		$url = 'https://api.weixin.qq.com/cgi-bin/groups/get?access_token=' . get_access_token ();
		$data = wp_file_get_contents ( $url );
		$data = json_decode ( $data, true );
		if (!isset($data['errcode']) && $data){
		    foreach ( $data ['groups'] as $d ) {
		        $save ['wechat_group_id'] = $map ['wechat_group_id'] = $d ['id'];
		        $save ['wechat_group_name'] = $d ['name'];
		        $save ['wechat_group_count'] = $d ['count'];
		        	
		        if (isset ( $groups [$d ['id']] )) {
		            // 更新本地数据
		            $old = $groups [$d ['id']];
		            if ($old['title'] != $d['name']){
		                $old['wechat_group_name']=$old['title'];
		                $save ['wechat_group_name']=$old['title'];
		                //修改微信端的数据
		                $updateUrl="https://api.weixin.qq.com/cgi-bin/groups/update?access_token=".get_access_token();
		                $newGroup['group']['id']=$d['id'];
		                $newGroup['group']['name']=$save ['wechat_group_name'];
		                $res= post_data($updateUrl, $newGroup);
		            }
		            if ($old ['wechat_group_name'] != $d ['name'] || $old ['wechat_group_count'] != $d ['count']) {
		                // 					$save['title']=$save['wechat_group_name'];
		                M ( 'auth_group' )->where ( $map )->save ( $save );
		            }
		            unset ( $groups [$d ['id']] );
		        } else {
		            // 增加本地数据
		            $save = array_merge ( $save, $map );
		            $save ['title'] = $d ['name'];
		            $save ['qr_code'] = '';
		            M ( 'auth_group' )->add ( $save );
		        }
		    }
		    foreach ($ournew as $v){
		        $map2 ['id'] = $map3 ['group_id'] = $v ['id'];
		        // 增加微信端的数据
		        $url = 'https://api.weixin.qq.com/cgi-bin/groups/create?access_token=' . get_access_token ();
		        if(strlen($v['title'])>30){
		            $v['title']=substr($v ['title'], 0, 30);
		            $save['title']=$v['title'];
		        }
		        $param ['group'] ['name'] = $v ['title'];
		        // 		            $param = JSON ( $param );
		        $res = post_data ( $url, $param );
		        if (! empty ( $res ['group'] ['id'] )) {
		            $info ['wechat_group_id'] = $save ['wechat_group_id'] = $res ['group'] ['id'];
		            $save ['wechat_group_name'] = $res ['group'] ['name'];
		            M ( 'auth_group' )->where ( $map2 )->save ( $save );
		        }
		    }
		    foreach ( $groups as $v ) {
		         $map2 ['id'] =  $map3 ['group_id'] = $v ['id'];
		        $wechat_group_id = intval ( $v ['wechat_group_id'] );
		        if ($wechat_group_id == -1) {
// 		            // 增加微信端的数据
		            $url = 'https://api.weixin.qq.com/cgi-bin/groups/create?access_token=' . get_access_token ();
    		         if(strlen($v['title'])>30){
    		            $v['title']=substr($v ['title'], 0, 30);
    		            $save['title']=$v['title'];
    		        }
		            $param ['group'] ['name'] = $v ['title'];
// 		            $param = JSON ( $param );
		            $res = post_data ( $url, $param );
		            if (! empty ( $res ['group'] ['id'] )) {
		                $info ['wechat_group_id'] = $save ['wechat_group_id'] = $res ['group'] ['id'];
		                $save ['wechat_group_name'] = $res ['group'] ['name'];
		                M ( 'auth_group' )->where ( $map2 )->save ( $save );
		            }
		        } else {
		            // 删除本地数据
		            M ( 'auth_group' )->where ( $map2 )->delete ();
		            M ( 'auth_group_access' )->where ( $map3 )->delete ();
		        }
		    }
		}

		if (isset ( $_GET ['need_return'] )) {
			redirect ( addons_url ( 'UserCenter://UserCenter/syc_openid' ) );
		}
	}
}