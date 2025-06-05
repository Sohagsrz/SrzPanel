<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class SwaggerController extends Controller
{
    public function index()
    {
        return view('api.docs');
    }

    public function json()
    {
        $swagger = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Hosting Panel API',
                'version' => '1.0.0',
                'description' => 'API documentation for the Hosting Panel',
            ],
            'servers' => [
                [
                    'url' => config('app.url') . '/api/v1',
                    'description' => 'API Server',
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                    ],
                ],
                'schemas' => [
                    'Account' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'username' => ['type' => 'string'],
                            'email' => ['type' => 'string', 'format' => 'email'],
                            'domain' => ['type' => 'string'],
                            'status' => ['type' => 'string', 'enum' => ['active', 'suspended', 'terminated']],
                            'disk_usage' => ['type' => 'integer'],
                            'disk_limit' => ['type' => 'integer'],
                            'bandwidth_usage' => ['type' => 'integer'],
                            'bandwidth_limit' => ['type' => 'integer'],
                            'created_at' => ['type' => 'string', 'format' => 'date-time'],
                            'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                ],
            ],
            'paths' => [
                '/accounts' => [
                    'get' => [
                        'tags' => ['Accounts'],
                        'summary' => 'List all accounts',
                        'security' => [['bearerAuth' => []]],
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'description' => 'Page number',
                                'required' => false,
                                'schema' => ['type' => 'integer'],
                            ],
                            [
                                'name' => 'per_page',
                                'in' => 'query',
                                'description' => 'Items per page',
                                'required' => false,
                                'schema' => ['type' => 'integer'],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Successful operation',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'data' => [
                                                    'type' => 'array',
                                                    'items' => ['$ref' => '#/components/schemas/Account'],
                                                ],
                                                'meta' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'total' => ['type' => 'integer'],
                                                        'per_page' => ['type' => 'integer'],
                                                        'current_page' => ['type' => 'integer'],
                                                        'last_page' => ['type' => 'integer'],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'post' => [
                        'tags' => ['Accounts'],
                        'summary' => 'Create a new account',
                        'security' => [['bearerAuth' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['username', 'email', 'password', 'domain'],
                                        'properties' => [
                                            'username' => ['type' => 'string'],
                                            'email' => ['type' => 'string', 'format' => 'email'],
                                            'password' => ['type' => 'string'],
                                            'domain' => ['type' => 'string'],
                                            'package_id' => ['type' => 'integer'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Account created successfully',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/Account',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return response()->json($swagger);
    }
} 