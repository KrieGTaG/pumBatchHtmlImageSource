<?php

require __DIR__ . '/helper.php';

$goBackBtn = '
    <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Batch php file for pum HTML files</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-pprn3073KE6tl6bjs2QrFaJGz5/SUsLqktiwsUTF55Jfv3qYSDhgCecCxMW52nD2" crossorigin="anonymous"></script>
        </head>
        <body>
            <div class="m-3">
                <button 
                    class="btn btn-primary" 
                    name="goBack" 
                    value="goBack" 
                    onclick="window.location.href=\'/batch/pumBatchHtmlImageSource/page.html\'">
                    Go back to batch
                </button>
            </div>
        </body>
    </html>';

if (isset($_POST['submit'])) {

    $jsModulePageContent = '';
    $jsModuleImageContent = '';

    $oldPath = $_POST['inputPath'];
    $newPath = $_POST['newPath'];
    $dir = substr(strrchr($oldPath, "/"), 1);
    $dirName = substr(strrchr($dir, "-"), 1);

    $dirContent = scandir($oldPath, SCANDIR_SORT_NONE);

    if(!$dirContent) {
        echo '<h3 class="m-3">The folder was not found. Verify the path</h3>';
        echo $goBackBtn;
        return;
    } else {
        if (!file_exists($newPath . '/' . $dirName)) {
            mkdir($newPath . '/' . $dirName, 0777, true);
        }
        $dirContent = prepareArray($dirContent);
        for ($i = 0; $i < count($dirContent); $i++) {
            if(is_dir($oldPath . '/' . $dirContent[$i])) {
                if (!file_exists($newPath . '/' . $dirName . '/' . substr(strrchr($dirContent[$i], "-"), 1))) {
                    mkdir($newPath . '/' . $dirName . '/' . substr(strrchr($dirContent[$i], "-"), 1));
                }
                $innerDirContent = scandir($oldPath . '/' . $dirContent[$i], SCANDIR_SORT_NONE);
                $innerDirContent = prepareArray($innerDirContent);
                if(in_array('page.html', $innerDirContent)) {
                    for($j = 0; $j < count($innerDirContent); $j++) {
                        handleLowestLevelWithoutPage($oldPath, $newPath, $dirName, $dirContent[$i], $innerDirContent[$j]);
                    }
                } else {
                    for($j = 0; $j < count($innerDirContent); $j++) {
                        handlePageLevel($oldPath, $newPath, $dirName, $dirContent[$i], $innerDirContent[$j], count($innerDirContent), $j);
                    }
                }
            }
        }
    }

    buildPageJsModule($dirName, $newPath . '/' . $dirName);
    buildImageJsModule($dirName, $newPath . '/' . $dirName);
    appendModulesToTypings($dirName);
    updatePropType($dirName);
    updateNavGuide($dirName);
    buildScreen($dirName);
}
echo '<h3 class="m-3">Success!</h3>';
echo $goBackBtn;

?>