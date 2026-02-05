<?php
// Generowanie prawidłowych hashy
echo "Admin hash (hasło: admin): " . password_hash('admin', PASSWORD_ARGON2ID) . "\n";
echo "User hash (hasło: user): " . password_hash('user', PASSWORD_ARGON2ID) . "\n";
