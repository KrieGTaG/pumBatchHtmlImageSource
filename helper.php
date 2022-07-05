<?php

use InlineStyle\InlineStyle;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/constants.php';

$pageModuleImportList = '';
$pageModuleObj = '';
$imageModuleObj = '';

function prepareArray($array) {
    if(in_array('.', $array)) {
        array_splice($array, array_search('.', $array), 1);
    }
    if (in_array('..', $array)) {
        array_splice($array, array_search('..', $array), 1);
    }
    if (in_array('.DS_Store', $array)) {
        array_splice($array, array_search('.DS_Store', $array), 1);
    }
    return $array;
}


function handlePageLevel($oldPath, $newPath, $dirName, $categoryName, $pageName, $pageCount, $pageIndex) {
    if(is_dir($oldPath . '/' . $categoryName . '/' . $pageName)) {
        if (!file_exists($newPath . '/' . $dirName . '/' . '/' . substr(strrchr($categoryName, "-"), 1) . '/' . strtolower($pageName))) {
            mkdir($newPath . '/' . $dirName . '/' . substr(strrchr($categoryName, "-"), 1) . '/' . strtolower($pageName));
        }
        $pageContent = scandir($oldPath . '/' . $categoryName . '/' . $pageName, SCANDIR_SORT_NONE);
        $pageContent = prepareArray($pageContent);
        for ($k = 0; $k < count($pageContent); $k++) {
            handleLowestLevel($oldPath, $newPath, $dirName, $categoryName, $pageName, $pageContent[$k], $pageCount, $pageIndex);
        }
    }
}

function handleLowestLevel($oldPath, $newPath, $dirName, $categoryName, $pageName, $pageContent, $pageCount, $pageIndex) {
    $oldFilePath = $oldPath . '/' . $categoryName . '/' . $pageName . '/' . $pageContent;
    $newFilePath = $newPath . '/' . $dirName . '/' . substr(strrchr($categoryName, "-"), 1) . '/' . strtolower($pageName) . '/' . $pageContent;
    if(is_dir($oldFilePath)) {
        handleImageFolder($newFilePath, $oldFilePath);
    } else {
        handleContent($oldFilePath, $newFilePath, $categoryName, $pageName, $pageCount, $pageIndex);
    }
}

function handleLowestLevelWithoutPage($oldPath, $newPath, $dirName, $categoryName, $pageContent) {
    $oldFilePath = $oldPath . '/' . $categoryName . '/' . $pageContent;
    $newFilePath = $newPath . '/' . $dirName . '/' . substr(strrchr($categoryName, "-"), 1) . '/' . $pageContent;
    if(is_dir($oldFilePath)) {
        handleImageFolder($newFilePath, $oldFilePath);
    } else {
        handleContent($oldFilePath, $newFilePath, $categoryName);
    }
}

function handleContent($oldFilePath, $newFilePath, $categoryName, $pageName = null, $pageCount = 0, $pageIndex = 0) {
        if (!copy($oldFilePath, $newFilePath)) {
        echo "La copie " . $oldFilePath . " du fichier a échoué...<br>";
        echo "La destination était " . $newFilePath . "<br>";
    }

    $html = file_get_contents($newFilePath);

    $dom = new DOMDocument;
    $dom->loadHTML($html);
    $items = $dom->getElementsByTagName('img');

    foreach ($items as $index=>$item) {
        appendPathToImageModule($index, count($items), substr(strrchr($categoryName, "-"), 1), $pageName, $pageCount, $pageIndex);
        if($pageName) {
            $item->setAttribute('source', strtolower($pageName) . '_' . ($index + 1));
        } else {
            $item->setAttribute('source', strtolower(substr(strrchr($categoryName, "-"), 1)) . '_' . ($index + 1));
        }

    }

    $css = $dom->getElementsByTagName('link');

    foreach ($css as $cssLink) {
        $currentSrc = $cssLink->getAttribute('href');
        $val = str_replace('00-Assets', 'Html_assets', $currentSrc);
        $cssLink->setAttribute('href', $val);
    }

    $return_val = file_put_contents($newFilePath, $dom->saveHTML());
    if (!$return_val) {
        '<h3 class="m-3">Operation failed for appending source</h3>';
    }

    $htmldoc = new InlineStyle($newFilePath);
    $htmldoc->applyStylesheet($htmldoc->extractStylesheets());
    $html = $htmldoc->getHTML();
    $return_val = file_put_contents($newFilePath, $html);
    if (!$return_val) {
        '<h3 class="m-3">Operation failed fon inlining</h3>';
    }
    writeRelatedJsFile($newFilePath, file_get_contents($newFilePath));
    appendPathToModule($categoryName, $pageIndex, $pageCount, $pageName);
}

function appendPathToImageModule($index, $arraySize, $categoryName, $pageName, $pageCount, $pageIndex) {
    global $imageModuleObj;
    if($pageName) {
        if($pageIndex === 0 && $index === 0) {
            $imageModuleObj = $imageModuleObj . strtolower($categoryName) . ': {' . PHP_EOL;
        }
        if($index === 0) {
            $imageModuleObj = $imageModuleObj . $pageName . ': {' . PHP_EOL;
        }
        $requirePath = './' . $categoryName . '/' . strtolower($pageName) . '/images/Image' . ($index + 1) . '.jpg';
        $imageModuleObj = $imageModuleObj . strtolower($pageName) . '_' . ($index + 1) . ': require(\'' . $requirePath . '\'),' . PHP_EOL;
        if($index === $arraySize - 1) {
            $imageModuleObj = $imageModuleObj . '},' . PHP_EOL;
        }
        if($pageIndex === $pageCount - 1 && $index === $arraySize - 1) {
            $imageModuleObj = $imageModuleObj . '},' . PHP_EOL;
        }
    } else {
        if($index === 0) {
            $imageModuleObj = $imageModuleObj . strtolower($categoryName) . ': {' . PHP_EOL;
        }
        $requirePath = './' . $categoryName . '/images/Image' . ($index + 1) . '.jpg';
        $imageModuleObj = $imageModuleObj . strtolower($categoryName) . '_' . ($index + 1) . ': require(\'' . $requirePath . '\'),' . PHP_EOL;
        if($index === $arraySize - 1) {
            $imageModuleObj = $imageModuleObj . '},' . PHP_EOL;
        }
    }
}

//TODO
function appendPathToModule($categoryName, $pageIndex, $pageCount, $pageName) {
    global $pageModuleImportList, $pageModuleObj;
    $importName = $pageName === null ? strtolower( substr(strrchr($categoryName, "-"), 1)) . 'Content' : strtolower( substr(strrchr($categoryName, "-"), 1)) . '_' . strtolower($pageName) . '_Content';
    $fromLocation = $pageName === null ? './' . substr(strrchr($categoryName, "-"), 1) . '/page' : './' . substr(strrchr($categoryName, "-"), 1) . '/' . strtolower($pageName) . '/page';
    $pageModuleImportList = $pageModuleImportList . 'import * as '. $importName . ' from \''. $fromLocation . '\';' . PHP_EOL;
    if($pageName) {
        if($pageIndex === 0) {
            $pageModuleObj = ' ' . $pageModuleObj . strtolower( substr(strrchr($categoryName, "-"), 1)) . ': {' . PHP_EOL;
        }
        $pageModuleObj = $pageModuleObj . strtolower($pageName) . ': ' . $importName . '.default(),' . PHP_EOL;
        if($pageIndex === $pageCount - 1) {
            $pageModuleObj = $pageModuleObj . '},' . PHP_EOL;
        }
    } else {
        $pageModuleObj = $pageModuleObj . strtolower( substr(strrchr($categoryName, "-"), 1)) . ': ' . $importName . '(),' . PHP_EOL;
    }

}

function writeRelatedJsFile($newFilePath, $content) {
    $updatedFilePath = substr($newFilePath, 0, strrpos($newFilePath, '.')) . '.js';
    $jsContent = 'export default function () { return `'. $content . '`;}';
    $return_val = file_put_contents($updatedFilePath, $jsContent);
    if (!$return_val) {
        '<h3 class="m-3">Operation failed on Creating js file</h3>';
    }
}

function handleImageFolder($newFilePath, $oldFilePath) {
    if (!file_exists($newFilePath)) {
        mkdir($newFilePath);
    }
    $images = scandir($oldFilePath, SCANDIR_SORT_NONE);
    $images = prepareArray($images);
    for ($l = 0; $l < count($images); $l++) {
        if (!copy($oldFilePath . '/' . $images[$l], $newFilePath . '/' . $images[$l])) {
            echo "La copie " . $oldFilePath . " du fichier a échoué...<br>";
            echo "La destination était " . $newFilePath . "<br>";
        }
    }
}

function buildPageJsModule($dirname, $path) {

    global $pageModuleImportList, $pageModuleObj;

    $toReturn = '';
    $toReturn = $toReturn . $pageModuleImportList . PHP_EOL;
    $toReturn = $toReturn . 'const pages' . $dirname . ' = {' . PHP_EOL;
    $toReturn = $toReturn . $pageModuleObj;
    $toReturn = $toReturn . '};' . PHP_EOL;
    $toReturn = $toReturn . 'export default pages' . $dirname . ';';
    $return_val = file_put_contents($path . '/pages' . $dirname . '.js', $toReturn);
    if (!$return_val) {
        '<h3 class="m-3">Operation failed on Creating js Module page file</h3>';
    }

}

function buildImageJsModule($dirname, $path) {

    global $imageModuleObj;

    $toReturn = '';
    $toReturn = $toReturn . 'const images' . $dirname . ' = {';
    $toReturn = $toReturn . $imageModuleObj;
    $toReturn = $toReturn . '};';
    $toReturn = $toReturn . 'export default images' . $dirname . ';';
    $return_val = file_put_contents($path . '/images' . $dirname . '.js', $toReturn);
    if (!$return_val) {
        '<h3 class="m-3">Operation failed on Creating js Module image file</h3>';
    }

}

function appendModulesToTypings($dirName) {
    $content = file_get_contents('../../React/React-Native/insects-guide/typings/index.d.ts');
    $content = $content . 'declare module \'static_assets/pages/' . $dirName . '/images' . $dirName. '\';' . PHP_EOL;
    $content = $content . 'declare module \'static_assets/pages/' . $dirName . '/pages' . $dirName. '\';' . PHP_EOL;
    $return_val = file_put_contents('../../React/React-Native/insects-guide/typings/index.d.ts', $content);
    if (!$return_val) {
        '<h3 class="m-3">Operation failed on updating typings</h3>';
    }
}

function updatePropType($dirName) {
    $basePath = '../../React/React-Native/insects-guide/assets/pages/' . $dirName;
    $content = file_get_contents('../../React/React-Native/insects-guide/typings/guidePropType.tsx');
    $dirContent = scandir($basePath, SCANDIR_SORT_NONE);
    $categories = prepareArray($dirContent);

    for($i = 0; $i < count($categories); $i++) {
        if(is_dir($basePath . '/' . $categories[$i]) && !(ctype_digit(substr($categories[$i], -1))))
        {
            $content = substr_replace($content, $categories[$i] . ': undefined;' . PHP_EOL, strpos($content, '}'), 0);
        }
    }
    $return_val = file_put_contents('../../React/React-Native/insects-guide/typings/guidePropType.tsx', $content);
    if (!$return_val) {
        '<h3 class="m-3">Operation failed on updating guideProp type</h3>';
    }
}

function updateNavGuide($dirName) {
    $basePath = '../../React/React-Native/insects-guide/assets/pages/' . $dirName;
    $content = file_get_contents('../../React/React-Native/insects-guide/navigation/GuideNavigation.tsx');
    $dirContent = scandir($basePath, SCANDIR_SORT_NONE);
    $categories = prepareArray($dirContent);

    $importToAppend = 'import ' . $dirName . 'Screen from \'../screens/GuideScreens/' . $dirName . 'Screen\';' . PHP_EOL;
    $content = substr_replace($content, $importToAppend, strpos($content, 'import { RootDrawerParamList } from \'../typings/guidePropType\';'), 0);

    $toAppend = '      <Drawer.Screen
        name="%s"
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        options={({ navigation }: RootDrawerScreenProps<\'%s\'>) => ({
          title: \'%s\',
          groupName: \''. $dirName . '\',
        })}
      >
        {() => <'. $dirName . 'Screen routeName={\'%s\'} />}
      </Drawer.Screen>'  . PHP_EOL;

    for($i = 0; $i < count($categories); $i++) {
        if(is_dir($basePath . '/' . $categories[$i])) {
            $content = substr_replace($content, sprintf($toAppend, $categories[$i], $categories[$i], $categories[$i], lcfirst($categories[$i])), strpos($content, '</Drawer.Navigator>'), 0);
        }
    }
    $return_val = file_put_contents('../../React/React-Native/insects-guide/navigation/GuideNavigation.tsx', $content);
    if (!$return_val) {
        '<h3 class="m-3">Operation failed on updating guideProp type</h3>';
    }
}

function buildScreen($dirName) {
    $basePath = '../../React/React-Native/insects-guide/screens/GuideScreens/' . $dirName . 'Screen.tsx';
    $toReturn = getScreenFormat($dirName);
    $return_val = file_put_contents($basePath, $toReturn);
    if (!$return_val) {
        '<h3 class="m-3">Operation failed on creating Screen file</h3>';
    }
}