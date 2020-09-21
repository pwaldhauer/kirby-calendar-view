<?php

Kirby::plugin('pwaldhauer/calendar-view', [
    'sections' => [
        'calendar-view' => require_once __DIR__ . '/sections/calendar-view.php',
    ],

    'api' => [
        'routes' => [
            [
                'pattern' => 'calendar-view/(:any)/(:any)/(:any)',
                'action' => function ($path, $from, $to) {
                    /** @var \Page $page */
                    $page = page($path);

                    $result = $page->index(true)
                        ->filterBy('date', '!=' ,'')
                        ->filterBy('date', 'date >', $from)
                        ->filterBy('date', 'date <', $to)
                        ->map(function ($page)  {
                        return [
                            'id' => $page->id(),
                            'title' => (string)$page->title(),
                            'date' => (string)$page->date(),
                            'panelLink' => 'pages/' . str_replace('/', '+', $page->id()),
                            'status' => (string)$page->status()
                        ];
                    });

                    return response::json([
                        'from' => $from,
                        'to' => $to,
                        'data' => $result->values(),
                    ]);
                },

                'method' => 'GET'
            ],
        ]

    ]

]);
