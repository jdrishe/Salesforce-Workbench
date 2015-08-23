<?php
require_once 'session.php';
require_once 'shared.php';
require_once 'controllers/AsyncDmlController.php';
require_once 'header.php';

$c = new AsyncDmlController();
switch ($c->stepView()) {

case AsyncDmlController::STEP_UPLOAD:
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
                <input type="file" name="<?php echo AsyncDmlController::FILE_KEY ?>"/>
            </label>
        </div>
        <div>
            <input type="submit" value="Next"/>
        </div>
    </form>

<?php break; case AsyncDmlController::STEP_MAP: ?>
    <form method="post" action="">
        <?php print getCsrfFormTag(); ?>

        <?php var_dump($c->getColumnNames()); // TODO: expand into table ?>

        <div>
            <input type="submit" name="<?php echo AsyncDmlController::RESET_KEY ?>" value="Reset"/>
            <input type="submit" value="Next"/>
        </div>
    </form>


<?php
break; } // end step switch
require_once 'footer.php';
?>