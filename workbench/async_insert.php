<?php
require_once 'session.php';
require_once 'shared.php';
require_once 'controllers/AsyncDmlController.php';
require_once 'header.php';

$c = new AsyncDmlController();
?>

<form method="post" enctype="multipart/form-data" action="">
    <?php print getCsrfFormTag(); ?>
    <div>
        <label>
            Object Type:
            <?php printObjectSelection(WorkbenchContext::get()->getDefaultObject()); ?>
        </label>
    </div>
    <!-- TODO: single file look up -->
    <div>
        <label>
            From File
            <input type="file" name="<?php echo AsyncDmlController::CSV_FILE ?>"/>
        </label>
    </div>
    <div>
        <input type="submit" value="Next"/>
    </div>
</form>

<?php require_once 'footer.php'; ?>