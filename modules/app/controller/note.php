<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

/**
 * Description of App_Controller_Note
 *
 * @author Tomy
 */
class App_Controller_Note extends Controller
{

    /**
     * @before _secured, _developer
     */
    public function index()
    {
        $view = $this->getActionView();
        
        $notes = App_Model_Note::all(array('userId = ?' => $this->getUser()->getId()));
        $view->set('notes', $notes);
        
        if(RequestMethods::post('submitAddNote')){
            if($this->checkCSRFToken() !== true){
                self::redirect('/note');
            }
            
            $note = new App_Model_Note(array(
                'userId' => $this->getUser()->getId(),
                'title' => RequestMethods::post('title'),
                'body' => RequestMethods::post('text')
            ));
            
            if($note->validate()){
                $nid = $note->save();
                
                Event::fire('app.log', array('success', 'Note id: ' . $nid));
                $view->successMessage('Note has been successfully created');
                self::redirect('/note');
            }else{
                Event::fire('app.log', array('fail'));
                $view->set('errors', $note->getErrors())
                        ->set('addnote', $note);
            }
                
        }
    }

    /**
     * @before _secured, _developer
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        
        $note = App_Model_Note::first(
                array('id = ?' => (int)$id, 'userId = ?' => $this->getUser()->getId()));
        
        if($note === null){
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/note');
        }
        
        $view->set('note', $note);
        
        if(RequestMethods::post('submitEditNote')){
            if($this->checkCSRFToken() !== true){
                self::redirect('/note');
            }
            
            $note->title = RequestMethods::post('title');
            $note->body = RequestMethods::post('text');
            
            if($note->validate()){
                $note->save();
                
                Event::fire('app.log', array('success', 'Note id: ' . $note->getId()));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/note');
            }else{
                Event::fire('app.log', array('fail', 'Note id: ' . $note->getId()));
                $view->set('note', $note)
                        ->set('errors', $note->getErrors()); 
            }
                
        }
    }

    /**
     * @before _secured, _developer
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkCSRFToken()) {
            $note = App_Model_Note::first(
                            array('id = ?' => (int) $id, 'userId = ?' => $this->getUser()->getId()));

            if ($note === null) {
                echo self::ERROR_MESSAGE_2;
            }

            if ($note->delete()) {
                Event::fire('app.log', array('success', 'Note id: ' . $note->getId()));
                echo 'success';
            } else {
                Event::fire('app.log', array('fail', 'Note id: ' . $note->getId()));
                echo self::ERROR_MESSAGE_1;
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

}
