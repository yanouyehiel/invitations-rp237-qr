<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

db()->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
echo "Base prête.\n";
