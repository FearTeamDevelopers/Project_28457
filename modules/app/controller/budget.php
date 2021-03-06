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

        if ($budget === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
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

        if ($project === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/project');
        }

        $view->set('projectid', $project->getId());

        if (RequestMethods::post('submitAddBudget')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/project');
            }

            $budget = new App_Model_ProjectBudget(array(
                'projectId' => $project->getId(),
                'userId' => $this->getUser()->getId(),
                'title' => RequestMethods::post('title'),
                'description' => RequestMethods::post('description'),
                'quantity' => RequestMethods::post('quantity'),
                'mu' => RequestMethods::post('mu', 'h'),
                'ppu' => RequestMethods::post('ppu'),
                'totalPrice' => round((float) RequestMethods::post('ppu') * (float) RequestMethods::post('quantity'), 2)
            ));

            if ($budget->validate()) {
                $bid = $budget->save();

                Event::fire('app.log', array('success', 'Project budget item id: ' . $bid));
                $view->successMessage('Record has been successfully saved');
                self::redirect('/project/' . $project->getUrlKey() . '/#financial');
            } else {
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
                        array('deleted = ?' => false, 'id = ?' => (int) $id));

        if ($budgetRec === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/project');
        }

        $view->set('budget', $budgetRec);

        if (RequestMethods::post('submitEditBudget')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/project');
            }

            $budgetRec->title = RequestMethods::post('title');
            $budgetRec->description = RequestMethods::post('description');
            $budgetRec->quantity = RequestMethods::post('quantity');
            $budgetRec->mu = RequestMethods::post('mu', 'h');
            $budgetRec->ppu = RequestMethods::post('ppu');
            $budgetRec->totalPrice = round(RequestMethods::post('ppu') * RequestMethods::post('quantity'), 2);

            if ($budgetRec->validate()) {
                $budgetRec->save();

                Event::fire('app.log', array('success', 'Project budget item id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/project/detail/' . $budgetRec->getProjectId() . '#financial');
            } else {
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
                        array('deleted = ?' => false, 'id = ?' => (int) $id));

        if ($budget === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/project');
        }

        $view->set('budget', $budget);

        if (RequestMethods::post('submitDeleteBudget')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/project');
            }

            $budget->deleted = true;

            if ($budget->validate()) {
                $budget->save();

                Event::fire('app.log', array('success', 'Record id: ' . $budget->getId()));
                $view->successMessage('Recored has been deleted successfully');
                self::redirect('/project/detail/' . $budget->getProjectId() . '#financial');
            } else {
                Event::fire('app.log', array('fail', 'Record id: ' . $budget->getId()));
                $view->errorMessage('An error occured while deleting the record');
                self::redirect('/project/detail/' . $budget->getProjectId() . '#financial');
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

        $budget = App_Model_ProjectBudget::first(
                        array('deleted = ?' => true, 'id = ?' => (int) $id));

        if ($budget === null) {
            echo self::ERROR_MESSAGE_2;
        }

        $budget->deleted = false;

        if ($budget->validate()) {
            $budget->save();

            Event::fire('app.log', array('success', 'Record id: ' . $budget->getId()));
            echo 'success';
        } else {
            Event::fire('app.log', array('fail', 'Record id: ' . $budget->getId()));
            echo self::ERROR_MESSAGE_1;
        }
    }

}
