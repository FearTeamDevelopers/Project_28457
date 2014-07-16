<?php

use App\Etc\Controller;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

/**
 * Description of App_Controller_File
 *
 * @author Tomy
 */
class App_Controller_File extends Controller
{

    /**
     * @before _secured, _client
     */
    public function download($id)
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();
        
        $attachment = App_Model_Attachment::first(
                array('active = ?' => true, 'id = ?'=> (int)$id));
        
        if($attachment === null){
            $view->warningMessage('File not found');
            self::redirect('/');
        }
        
        Event::fire('app.log', array('success', 'File id: ' . $attachment->getId()));
        
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: application/octet-stream');
        header('Content-Transfer-Encoding: Binary');
        header("Content-Disposition: attachment; filename=\"" . basename($attachment->getUnlinkPath()) . "\"");
        header('Content-Length: ' . $attachment->getSize());
        ob_clean();
        readfile($attachment->getUnlinkPath());
        exit;
    }

    /**
     * @before _secured, _client
     */
    public function delete($id)
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = false;

        if ($this->checkTokenAjax()) {
            $security = Registry::get('security');

            if ($security->isGranted('role_admin')) {
                $attachment = App_Model_Attachment::first(array('id = ?' => (int) $id));
            } else {
                $attachment = App_Model_Attachment::first(
                                array('id = ?' => (int) $id, 'userId = ?' => $this->getUser()->getId()));
            }

            if ($attachment === null) {
                echo 'File not found or you dont have permission to delete it';
            }
            
            $status1 = App_Model_ProjectAttachment::deleteAll(array('attachmentId = ?' => $attachment->getId()));
            $status2 = App_Model_TaskAttachment::deleteAll(array('attachmentId = ?' => $attachment->getId()));

            if ($status1 != -1 && $status2 != -1 && 
                    $attachment->delete() && unlink($attachment->getUnlinkPath())) {
                Event::fire('app.log', array('success', 'File id: ' . $attachment->getId()));
                echo 'success';
            } else {
                Event::fire('app.log', array('fail', 'File id: ' . $attachment->getId()));
                echo 'An error occured while deleting the file';
            }
        } else {
            echo 'Security token is not valid';
        }
    }

}
