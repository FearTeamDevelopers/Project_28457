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
            $view->warningMessage(self::ERROR_MESSAGE_2);
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

        if ($this->checkCSRFToken()) {
            $security = Registry::get('security');

            if ($security->isGranted('role_admin')) {
                $attachment = App_Model_Attachment::first(array('id = ?' => (int) $id));
            } else {
                $attachment = App_Model_Attachment::first(
                                array('id = ?' => (int) $id, 'userId = ?' => $this->getUser()->getId()));
            }

            if ($attachment === null) {
                echo self::ERROR_MESSAGE_2.' or '.self::ERROR_MESSAGE_4;
            }
            
            $status1 = App_Model_ProjectAttachment::deleteAll(array('attachmentId = ?' => $attachment->getId()));
            $status2 = App_Model_TaskAttachment::deleteAll(array('attachmentId = ?' => $attachment->getId()));

            if ($status1 != -1 && $status2 != -1 && 
                    $attachment->delete() && unlink($attachment->getUnlinkPath())) {
                Event::fire('app.log', array('success', 'File id: ' . $attachment->getId()));
                echo 'success';
            } else {
                Event::fire('app.log', array('fail', 'File id: ' . $attachment->getId()));
                echo self::ERROR_MESSAGE_1;
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

}
