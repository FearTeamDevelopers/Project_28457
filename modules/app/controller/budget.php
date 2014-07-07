<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

/**
 * Description of App_Controller_Budget
 *
 * @author Tomy
 */
class App_Controller_Budget extends Controller
{

    /**
     * @before _secured, _developer
     */
    public function detail($id)
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();
        
        $budget = App_Model_ProjectBudget::first(
            array('active = ?' => true, 'deleted = ?' => false, 'id = ?' => (int) $id)
        );
        
        if($budget === null){
            $view->warningMessage('Budget record not found');
            self::redirect('/');
        }
        
        $view->set('budget', $budget);
    }

    /**
     * @before _secured, _developer
     */
    public function add($id)
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();

        $project = App_Model_Project::first(
                        array('active = ?' => true, 'deleted = ?' => false, 'id = ?' => (int) $id));

        if($project === null){
            $view->warningMessage('Project not found');
            self::redirect('/project');
        }
        
        $view->set('projectid', $project->getId());

        if(RequestMethods::post('submitAddBudget')){
            $this->checkToken();
            
            $budget = new App_Model_ProjectBudget(array(
                'projectId' => $project->getId(),
                'userId' => $this->getUser()->getId(),
                'title' => RequestMethods::post('title'),
                'description' => RequestMethods::post('description'),
                'quantity' => RequestMethods::post('quantity'),
                'mu' => RequestMethods::post('mu', 'h'),
                'ppu' => RequestMethods::post('ppu'),
                'totalPrice' => round((float)RequestMethods::post('ppu') * (float)RequestMethods::post('quantity'), 2)
            ));
            
            if($budget->validate()){
                $bid = $budget->save();
                
                Event::fire('app.log', array('success', 'Project budget item id: ' . $bid));
                $view->successMessage('Record has been successfully saved');
                self::redirect('/project/'.$project->getUrlKey().'/#financial');
            }else{
                Event::fire('app.log', array('fail'));
                $view->set('errors', $budget->getErrors())
                        ->set('budget', $budget);
            }
        }
    }

    /**
     * @before _secured, _developer
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $budgetRec = App_Model_ProjectBudget::first(
                        array('active = ?' => true, 'deleted = ?' => false, 'id = ?' => (int) $id));

        if($budgetRec === null){
            $view->warningMessage('Record not found');
            self::redirect('/project');
        }
        
        $view->set('budget', $budgetRec);
        
        if(RequestMethods::post('submitEditBudget')){
            $this->checkToken();
            
            $budgetRec->title = RequestMethods::post('title');
            $budgetRec->description = RequestMethods::post('description');
            $budgetRec->quantity = RequestMethods::post('quantity');
            $budgetRec->mu = RequestMethods::post('mu', 'h');
            $budgetRec->ppu = RequestMethods::post('ppu');
            $budgetRec->totalPrice = round(RequestMethods::post('ppu') * RequestMethods::post('quantity'), 2);
            
            if($budgetRec->validate()){
                $budgetRec->save();
                
                Event::fire('app.log', array('success', 'Project budget item id: ' . $id));
                $view->successMessage('All changes were successfully saved');
                self::redirect('/project/detail/'.$budgetRec->getProjectId().'#financial');
            }else{
                Event::fire('app.log', array('fail', 'Project budget item id: ' . $id));
                $view->set('errors', $budgetRec->getErrors());
            }
        }
    }

    /**
     * @before _secured, _projectmanager
     */
    public function delete($id)
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();
        
        $budget = App_Model_ProjectBudget::first(
                array('active = ?' => true, 'deleted = ?' => false, 'id = ?' => (int) $id));
        
        if($budget === null){
            $view->warningMessage('Recored not found');
            self::redirect('/project');
        }
        
        $view->set('budget', $budget);
        
        if(RequestMethods::post('submitDeleteBudget')){
            $this->checkToken();
            $budget->deleted = true;
            
            if($budget->validate()){
                $budget->save();
                
                Event::fire('app.log', array('success', 'Record id: ' . $budget->getId()));
                $view->successMessage('Recored has been deleted successfully');
                self::redirect('/project/detail/'.$budget->getProjectId().'#financial');
            }else{
                Event::fire('app.log', array('fail', 'Record id: ' . $budget->getId()));
                $view->errorMessage('An error occured while deleting the record');
                self::redirect('/project/detail/'.$budget->getProjectId().'#financial');
            }
        }
    }
    
    /**
     * @before _secured, _admin
     */
    public function undelete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkToken()) {
            $budget = App_Model_ProjectBudget::first(
                            array('active = ?' => true, 'deleted = ?' => true, 'id = ?' => (int) $id));

            if ($budget === null) {
                echo 'Record not found';
            }

            $budget->deleted = false;

            if ($budget->validate()) {
                $budget->save();

                Event::fire('app.log', array('success', 'Record id: ' . $budget->getId()));
                echo 'success';
            } else {
                Event::fire('app.log', array('fail', 'Record id: ' . $budget->getId()));
                echo 'An error occured while undeleting the record';
            }
        } else {
            echo 'Security token is not valid';
        }
    }

}
