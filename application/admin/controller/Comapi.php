<?php
namespace app\admin\controller;

use admin\controller\MyController;

class Comapi extends MyController
{

   //主要写一下公共接口
    public function upImage(){

        if(request()->isAjax()){

            $image=$this->upload($_FILES);
            return $image;
        }
    }

    /*
 * 传入上传的$_FILe 返回的状态0为失败  1为成功
 */
    public function upload($upload){
        //$file = request()->file($upload);
        $allowType=array('jpg','png','jpeg');
        $allowSize=100;//规定单位是M
        $uploadDir='uploads/';
        $msg=array();

        if(!empty($upload)){
            $upfile=$upload['file'];
            if($upfile['error']!=0){
                $msg['status']=0;
                $msg['error']='上传图片出错';
                return $msg;
            }
            $last=substr($upfile['name'],strrpos($upfile['name'],'.'));
            if(!in_array(trim($last,'.'),$allowType)){
                $msg['status']=0;
                $msg['error']='上传文件格式不支持,文件上传类型只限于:'.implode('.',$allowType);
                return $msg;
            }
            //限制大小
            if($upfile['size']>100*1024*1024){
                $msg['status']=0;
                $msg['error']='上传图片过大，最大只能上传100M';
                return $msg;
            }

            //随机生成一个文件夹名
            $uploadDir.=date('Ymd').'/';
            //检查文件是否存在
            if(!file_exists($uploadDir)){
                //如果不存在了就新建
                if(!mkdir($uploadDir)){
                    $msg['status']=0;
                    $msg['error']='生成文件夹失败';
                    return $msg;
                }
            }
            $fileName=uniqid().$last;
            $Path=$uploadDir.$fileName;
            if(!move_uploaded_file($upfile['tmp_name'],$Path)){
                $msg['status']=0;
                $msg['error']='上传文件路径移动失败';
                return $msg;
            }
        }
        $msg['status']=1;
        $msg['msg']='上传成功';
        $msg['savePath']=$Path;
        $msg['name']=$fileName;
        $msg['lastfix']=$last;
        $msg['size']=$upfile['size'];
        return $msg;

    }

}
