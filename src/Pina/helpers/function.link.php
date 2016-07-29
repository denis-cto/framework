<?php

function smarty_function_link($params, &$view)
{
    if (empty($params['get'])) {
        return '';
    }
    
    $assign = '';
    if (isset($params['assign'])) {
        $assign = $params['assign'];
        unset($params['assign']);
    }
    
    global $__pinaLinkContext;
    
    if (is_array($__pinaLinkContext)) {
        $params = array_merge($params, array_filter($__pinaLinkContext));
    }
    
	$link = \Pina\App::link($params['get'], $params);
    
    if ($assign) {
        $view->assign($assign, $link);
        $link = '';
    }
    
    return $link;
}
