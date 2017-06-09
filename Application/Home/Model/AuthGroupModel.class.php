<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com> <code-tech.diandian.com>
// +----------------------------------------------------------------------
namespace Home\Model;

use Think\Model;

/**
 * 插件模型
 *
 * @author yangweijie <yangweijiester@gmail.com>
 */
class AuthGroupModel extends Model {
	protected $tableName = 'auth_group';
	// 给用户打标签
	function move_group($id, $group_id) {
		is_array ( $id ) || $id = explode ( ',', $id );
		
		$data ['uid'] = $map ['uid'] = array (
				'in',
				$id 
		);
		// $data ['group_id'] = $group_id; //TODO 前端微信用户只能有一个微信组
		//$res = M ( 'auth_group_access' )->where ( $data )->delete ();
		
		//$data ['group_id'] = $group_id;
                $openid = array();
		foreach ( $id as $uid ) {
                    foreach ( $group_id as $tid ) {
                            $data ['uid'] = $uid;
                            $data ['group_id'] = $tid;
                            M ( 'auth_group_access' )->add ( $data ,'',true);
                    }
                     
			// 更新用户缓存
			D ( 'Common/User' )->getUserInfo ( $uid, true );
		}
              //  $group = $this->find ( $group_id );
		foreach ($group_id as $value) {
                    $group = $this->find ( $value );
                    // 同步到微信端
                    if (C ( 'USER_GROUP' ) && ! is_null ( $group ['wechat_group_id'] ) && $group ['wechat_group_id'] != -1) {

                            $url = 'https://api.weixin.qq.com/cgi-bin/tags/members/batchtagging?access_token=' . get_access_token ();
                            $map ['token'] = get_token ();
                            $follow = M ( 'public_follow' )->where ( $map )->field ( 'openid, uid' )->select ();
                            foreach ( $follow as $v ) {
                                    if (empty ( $v ['openid'] ))
                                            continue;

                                    $param ['openid_list'][] = $v ['openid'];                                  
                            }
                            $param ['tagid'] = $group ['wechat_group_id'];
                            $param = JSON ( $param );
                            $res = post_data ( $url, $param );
                            unset($param);
                    }
                }		
		return $group;
	}
        //给用户取消标签
        function remove_tag($id, $group_id){
            is_array ( $id ) || $id = explode ( ',', $id );
		
		$data ['uid'] = $map ['uid'] = array (
				'in',
				$id 
		);
		// $data ['group_id'] = $group_id; //TODO 前端微信用户只能有一个微信组
		//$res = M ( 'auth_group_access' )->where ( $data )->delete ();
		
		//$data ['group_id'] = $group_id;
                $openid = array();
		foreach ( $id as $uid ) {
                    foreach ( $group_id as $tid ) {
                            $data ['uid'] = $uid;
                            $data ['group_id'] = $tid;
                            M ( 'auth_group_access' )->where ( $data )->delete ();
                    }
                       
			// 更新用户缓存
			D ( 'Common/User' )->getUserInfo ( $uid, true );
		}
              //  $group = $this->find ( $group_id );
		foreach ($group_id as $value) {
                    $group = $this->find ( $value );
                    // 同步到微信端
                    if (C ( 'USER_GROUP' ) && ! is_null ( $group ['wechat_group_id'] ) && $group ['wechat_group_id'] != -1) {

                            $url = 'https://api.weixin.qq.com/cgi-bin/tags/members/batchuntagging?access_token=' . get_access_token ();
                            $map ['token'] = get_token ();
                            $follow = M ( 'public_follow' )->where ( $map )->field ( 'openid, uid' )->select ();
                            foreach ( $follow as $v ) {
                                    if (empty ( $v ['openid'] ))
                                            continue;

                                    $param ['openid_list'][] = $v ['openid'];                                  
                            }
                            $param ['tagid'] = $group ['wechat_group_id'];
                            $param = JSON ( $param );
                            $res = post_data ( $url, $param );
                            unset($param);
                    }
                }		
		return $group;
        }
}
