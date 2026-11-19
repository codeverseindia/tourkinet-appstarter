<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
send_security_headers();
dispatch_request();
