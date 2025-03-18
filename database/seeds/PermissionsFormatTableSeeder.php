<?php

use Illuminate\Database\Seeder;
use App\Models\PermissionFormat;

class PermissionsFormatTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissionFormatArray = [
            'company' => [
                'user' => [
                    'show' => 0,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'detail' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ]
                ],
                'manager' => [
                    'show' => 0,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'detail' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                ],
            ],
            'statement' => [
                'user' => [
                    'show' => 0,
                    'view' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ]
                ],
                'manager' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                ],
            ],
            'category' => [
                'user' => [
                    'show' => 0,
                    'view' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ]
                ],
                'manager' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                ],
            ],
            'department' => [
                'user' => [
                    'show' => 0,
                    'view' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ]
                ],
                'manager' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                ],
            ],
            'job title' => [
                'user' => [
                    'show' => 0,
                    'view' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ]
                ],
                'manager' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                ],
            ],
            'user permission' => [
                'user' => [
                    'show' => 0,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ]
                ],
                'manager' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                ],
            ],
            'absence setting' => [
                'user' => [
                    'show' => 0,
                    'view' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ]
                ],
                'manager' => [
                    'show' => 0,
                    'view' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                ],
            ],
            'employee' => [
                'user' => [
                    'show' => 0,
                    'view' => [
                        'disable' => 1,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ]
                ],
                'manager' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                ],
            ],
            'project' => [
                'user' => [
                    'show' => 0,
                    'view' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ]
                ],
                'manager' => [
                    'show' => 0,
                    'view' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                ],
            ],
            'contact' => [
                'user' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ]
                ],
                'manager' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 1,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'company',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                ],
            ],
            'absence' => [
                'user' => [
                    'show' => 0,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ]
                ],
                'manager' => [
                    'show' => 0,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                ],
            ],
            'goal' => [
                'user' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ]
                ],
                'manager' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                ],
            ],
            'routine' => [
                'user' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ]
                ],
                'manager' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                ],
            ],
            'instruction' => [
                'user' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ]
                ],
                'manager' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                ],
            ],
            'checklist' => [
                'user' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ]
                ],
                'manager' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                ],
            ],
            'risk area' => [
                'user' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ]
                ],
                'manager' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                ],
            ],
            'document' => [
                'user' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ]
                ],
                'manager' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                ],
            ],
            'deviation' => [
                'user' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ]
                ],
                'manager' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                ],
            ],
            'risk analysis' => [
                'user' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ]
                ],
                'manager' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                ],
            ],
            'task' => [
                'user' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ]
                ],
                'manager' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                ],
            ],
            'report checklist' => [
                'user' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'personal',
                        'type' => 'boolean',
                    ]
                ],
                'manager' => [
                    'show' => 1,
                    'view' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'detail' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'basic' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                    'resource' => [
                        'disable' => 0,
                        'apply' => 'unknown',
                        'type' => 'unknown',
                    ],
                    'process' => [
                        'disable' => 0,
                        'apply' => 'group',
                        'type' => 'boolean',
                    ],
                ],
            ],
        ];

        DB::table('permissions_format')->delete();

        foreach ($permissionFormatArray as $function => $item) {
            foreach ($item as $filter_by => $value) {
                if (!empty($value)) {
                    $show = 0;
                    foreach ($value as $permission => $detail) {
                        if ($permission == 'show') {
                            $show = $detail;
                        } else {
                            PermissionFormat::create(array(
                                'function' => $function,
                                'filter_by' => $filter_by,
                                'show' => $show,
                                'permission_name' => $permission,
                                'permission_type' => $detail['type'],
                                'permission_disable' => $detail['disable'],
                                'permission_apply' => $detail['apply'],
                            ));
                        }
                    }
                }
            }
        }
    }
}
