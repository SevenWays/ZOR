<?php

return array(
    'console' => array(
        'router' => array(
            'routes' => array(
                'new' => array(
                    'options' => array(
                        'route' => 'create (new|module|fmodule):what [--name=] [--link=] [--path=]',
                        'defaults' => array(
                            'controller' => 'ZOR\Controller\Create',
                            'action' => 'create'
                        )
                    )
                ),
                'generate' => array(
                    'options' => array(
                    'route' => 'generate (ctrl|act|model|view):what [--name=] [--module=] [--cname=] [--actions=] [--columns=] [--path=]',

                        //'route' => 'generate (ctrl|act|model|view):what [--mname=] [--cname=] [--actions=] [--name=] [--columns=] [--path=]',
                        'defaults' => array(
                            'controller' => 'ZOR\Controller\Create',
                            'action' => 'generate'
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
);
