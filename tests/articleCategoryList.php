<?php

require_once '../vendor/autoload.php';

use miralsoft\weclapp\api\ArticleCategory;

require_once 'configWeclapp.php';

$articleCategory = new ArticleCategory();

//$result = $articleCategory->get(1, 100, 'name');
//$result = $articleCategory->getAll('name');
//$result = $articleCategory->getArticleCategory(14677);
$result = [$articleCategory->getArticleCategoryFromName('Service Dienstleistungen')];

if (is_array($result)) {
    echo 'Count results: ' . count($result);
}
echo '<br><br>';
print_r($result);
