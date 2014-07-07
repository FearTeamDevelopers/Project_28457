<?php

use App\Etc\Controller;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;
use THCFrame\Database\Mysqldump;

/**
 * Description of App_Controller_System
 *
 * @author Tomy
 */
class App_Controller_System extends Controller
{

    /**
     * @before _secured, _admin
     */
    public function index()
    {
        
    }

    /**
     * @before _secured, _admin
     */
    public function showAdminLog()
    {
        $view = $this->getActionView();
        $log = App_Model_AdminLog::all(array(), array('*'), array('created' => 'DESC'));
        $view->set('adminlog', $log);
    }

    /**
     * @before _secured, _admin
     */
    public function databaseBackup()
    {
        $view = $this->getActionView();
        $dump = new Mysqldump(array('exclude-tables' => array('tb_user')));
        $fm = new FileManager();

        if (!is_dir(APP_PATH . '/temp/db/')) {
            $fm->mkdir(APP_PATH . '/temp/db/');
        }

        $dump->create();
        $view->successMessage('Database backup has been successfully created');
        Event::fire('app.log', array('success', 'Database backup ' . $dump->getBackupName()));
        unset($fm);
        unset($dump);
        self::redirect('/system');
    }

    /**
     * @before _secured, _admin
     */
    public function showDeletedProjects()
    {
        $view = $this->getActionView();
        $projects = App_Model_Project::fetchDeletedProjects();
        $view->set('projects', $projects);
    }

    /**
     * @before _secured, _admin
     */
    public function showDeletedTasks()
    {
        $view = $this->getActionView();
        $tasks = App_Model_Task::fetchDeletedTasks();
        $view->set('tasks', $tasks);
    }

    /**
     * @before _secured, _admin
     */
    public function showDeletedUsers()
    {
        $view = $this->getActionView();
        $users = App_Model_User::all(array('deleted = ?' => true));
        $view->set('users', $users);
    }

}
