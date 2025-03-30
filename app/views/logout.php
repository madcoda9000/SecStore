<?php
use App\Utils\SessionUtil;

Flight::route('/logout', function() {
    SessionUtil::destroy();
    Flight::redirect('/login');
});
?>
