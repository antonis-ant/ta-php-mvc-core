<?php


namespace tonyanant\phpmvc;


class View
{
    public string $title = '';

    public function renderView($view, $params = []) {
        $viewContent = $this->renderOnlyView($view, $params);
        $layoutContent = $this->layoutContent();
        // Look for specified placeholder inside layout content and replace it with the specified view content.
        return str_replace('{{content}}', $viewContent, $layoutContent);
    }

    protected function layoutContent() {
        // Start buffering the output of included file below, without displaying it.
        $layout = Application::$app->getLayout();
        if (Application::$app->controller) {
            $layout = Application::$app->controller->layout;
        }
        ob_start();
        include_once Application::$ROOT_DIR. "/views/layouts/$layout.php";
        // Return buffered content & clear buffer.
        return ob_get_clean();
    }

    protected function renderOnlyView($view, $params) {
        // Set parameters to be used in rendered view.
        foreach ($params as $key => $value) {
            $$key = $value;
        }
        ob_start();
        include_once Application::$ROOT_DIR. "/views/$view.php";
        return ob_get_clean();
    }
}