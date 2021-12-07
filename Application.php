<?php

namespace tonyanant\phpmvc;

use tonyanant\phpmvc\db\Database;

/**
 * Class Application
 * @package tonyanant\phpmvc
 */
class Application
{
    public static string $ROOT_DIR;
    private string $userClass;
    private string $layout = 'main';
    public Router $router;
    public Request $request;
    public Response $response;
    public Session $session;
    public Database $db;
    public ?UserModel $user; // the "?" means the variable can be null (e.g. guest is browsing the website).
    public static Application $app;
    public ?Controller $controller = null;
    public View $view;

    public function __construct($rootPath, array $config) {
        // Set config options
        /*
         * Get the user class from config. We do it this way since the User class exists outside the core
         * and we want to be able to deploy the core independently and have it work for any class name.
         * */
        $this->userClass = $config['userClass'];
        $this->db = new Database($config['db']);
        self::$ROOT_DIR = $rootPath;
        self::$app = $this;

        // Initialize Core Classes.
        $this->request = new Request();
        $this->response = new Response();
        $this->session = new Session();
        $this->router = new Router($this->request, $this->response);
        $this->view = new View();

        // Get login session data
        // Get logged-in user's id.
        $primaryValue = $this->session->get('user');
        // User is logged-in get user data from database.
        if ($primaryValue) {
            $this->user = new $this->userClass();
            $primaryKey = $this->user->primaryKey(); // get user class primary key NAME.
            $this->user = $this->user->findOne([$primaryKey => $primaryValue]); // fetch user.
        } else {
            $this->user = null;
        }
    }

    public static function isGuest() {
        return !self::$app->user;
    }

    public function run() {
        try {
            echo $this->router->resolve();
        } catch(\Exception $e) {
            $this->response->setStatusCode($e->getCode());
            echo $this->view->renderView('_error', [
                'exception' => $e
            ]);
        }
    }

    public function login(UserModel $user) {
        $this->user = $user;
        $primaryKey = $user->primaryKey();
        $primaryValue = $user->{$primaryKey};
        $this->session->set('user', $primaryValue);

        return true;
    }

    public function logout() {
        $this->user = null;
        $this->session->remove('user');
    }


    /* Getters & Setters */
    public function getController(): Controller {
        return $this->controller;
    }

    public function setController(Controller $controller): void {
        $this->controller = $controller;
    }

    public function getLayout(): string {
        return $this->layout;
    }

    public function setLayout(string $layout): void {
        $this->layout = $layout;
    }

}