<?php
namespace app\admin\controller;

use admin\controller\MyController;

class Comapi extends MyController
{

   //Mainly write about the public interface
    public function upImage(){

        if(request()->isAjax()){

            $image=$this->upload($_FILES);
            return $image;
        }
    }

    /*
 * The status returned by the $_FILe passed in and uploaded is 0 for failure and 1 for success
 */
    public function upload($upload){
        //$file = request()->file($upload);
        $allowType=array('jpg','png','jpeg');
        $allowSize=100;//The prescribed unit is M
        $uploadDir='/public_html/public/uploads/';
        $msg=array();

        if(!empty($upload)){
            $upfile=$upload['file'];
            if($upfile['error']!=0){
                $msg['status']=0;
                $msg['error']='Error uploading picture';
                return $msg;
            }
            $last=substr($upfile['name'],strrpos($upfile['name'],'.'));
            if(!in_array(trim($last,'.'),$allowType)){
                $msg['status']=0;
                $msg['error']='The upload file format is not supported, and the file upload type is limited to:'.implode('.',$allowType);
                return $msg;
            }
            //Limit size
            if($upfile['size']>100*1024*1024){
                $msg['status']=0;
                $msg['error']='The uploaded image is too large, and the maximum upload size is 100M';
                return $msg;
            }

            //Randomly generate a folder name
            $uploadDir.=date('Ymd').'/';
            //Check if the file exists
            if(!file_exists($uploadDir)){
                //If it does not exist, create a new one
                if(!mkdir($uploadDir)){
                    $msg['status']=0;
                    $msg['error']='Failed to generate folder';
                    return $msg;
                }
            }
            $fileName=uniqid().$last;
            $Path=$uploadDir.$fileName;
            if(!move_uploaded_file($upfile['tmp_name'],$Path)){
                $msg['status']=0;
                $msg['error']='Failed to move the upload file path';
                return $msg;
            }
        }
        $msg['status']=1;
        $msg['msg']='Uploaded successfully';
        $msg['savePath']=substr($Path,12);
        $msg['name']=$fileName;
        $msg['lastfix']=$last;
        $msg['size']=$upfile['size'];
        return $msg;

    }

}
