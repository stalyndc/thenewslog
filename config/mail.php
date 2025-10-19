<?php

return [
    'from_name' => 'The News Log',
    'from_email' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@thenewslog.org',
];
