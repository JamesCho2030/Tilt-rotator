<?php
return [
    'name' => 'JK INNOVATION 발주관리 시스템',
    'company' => [
        'name' => 'JK INNOVATION',
        'address' => '경남 양산시 물금읍 증산역로 135 퍼스트 조양 401호',
        'contact' => '055-321-4055',
        'email' => 'james.cho@jkinnovation.com',
    ],
    'jwt' => [
        'secret' => getenv('JWT_SECRET') ?: 'change_this_secret',
        'issuer' => 'jkinnovation.com',
        'audience' => 'jkinnovation.com',
        'expiration' => 3600 * 4,
        'refresh_expiration' => 3600 * 24 * 14,
    ],
    'pagination' => [
        'per_page' => 20,
    ],
    'security' => [
        'password_cost' => 12,
    ],
];
