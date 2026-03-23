<?php
return [
    'key' => env('JWT.KEY', 'your-secret-key'),
    'expire' => env('JWT.EXPIRE', 86400), // 默认一天
];