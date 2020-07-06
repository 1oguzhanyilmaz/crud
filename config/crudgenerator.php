<?php

return [

    'custom_template' => false,
    'custom_delimiter' => ['%%', '%%'],
    'dynamic_view_template' => [
        'index' => [
            'formHeadingHtml',
            'formBodyHtml',
            'crudName',
            'crudNameCap',
            'modelName',
            'viewName',
            'routeGroup',
            'primaryKey'
        ],
        'form'  => [
            'formFieldsHtml'
        ],
        'create' => [
            'crudName',
            'crudNameCap',
            'modelName',
            'modelNameCap',
            'viewName',
            'routeGroup',
            'viewTemplateDir'
        ],
        'edit' => [
            'crudName',
            'crudNameSingular',
            'crudNameCap',
            'modelName',
            'modelNameCap',
            'viewName',
            'routeGroup',
            'primaryKey',
            'viewTemplateDir'
        ],
        'show' => [
            'formHeadingHtml',
            'formBodyHtml',
            'formBodyHtmlForShowView',
            'crudName',
            'crudNameSingular',
            'crudNameCap',
            'modelName',
            'viewName',
            'routeGroup',
            'primaryKey'
        ]
    ]

];
