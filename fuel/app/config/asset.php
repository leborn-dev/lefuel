<?php
/**
 * Use DOCROOT-relative absolute paths so assets resolve correctly regardless
 * of the PHP process working directory (e.g. php -S from the project root).
 */
return array(
    'paths' => array(DOCROOT.'assets/'),
);
