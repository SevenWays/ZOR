<?php

return array(
    'console' => array(
        'router' => array(
            'routes' => array(
                'new' => array(
                    'options' => array(
                        'route' => 'create (project|module|fmodule|database):what [--name=] [--driver=] [--username=] [--password=] [--require=] [--path=]',
                        'defaults' => array(
                            'controller' => 'ZOR\Controller\Create',
                            'action' => 'create'
                        )
                    )
                ),
                'generate' => array(
                    'options' => array(
                    'route' => 'generate (ctrl|act|model|view|migration):what [--name=] [--module=] [--cname=] [--actions=] [--columns=] [--path=]',
                        'defaults' => array(
                            'controller' => 'ZOR\Controller\Create',
                            'action' => 'generate'
                        )
                    )
                ),
                'utils' => array(
                    'options' => array(
                    'route' => '(run|db) (server|migrate|rollback):what [--host=] [--port=] [--path=]',
                        'defaults' => array(
                            'controller' => 'ZOR\Controller\Create',
                            'action' => 'utils'
                        )
                    )
                ),
            )
        )
    ),
    'controllers' => array(
        'invokables' => array(
            'ZOR\Controller\Create' => 'ZOR\Controller\CreateController'
        ),
    ),
    'view_helpers' => array(
      'invokables' => array(
         'form_for' => 'ZOR\View\Helper\Form',
      ),
   ),
);
