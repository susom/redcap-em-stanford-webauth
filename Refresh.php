<?php
namespace Stanford\Webauth;

/**
 * This page is behind webauth and simply closes after authentication
 */

if (isset($_GET['action']) && $_GET['action'] == "webauth-refresh") {
    ?>
    <script>
        if (confirm("Your Webauth session has been refreshed. :-)\n\nPress OK to close this window and return to your survey.\n\nAfter a brief wait the warning dialog should clear.")) {
            close();
        }
    </script>
    <?php
    exit();
}

// WEBAUTH ALIVE - RETURN 1
// header("Status: 404 Not Found");
echo 1;