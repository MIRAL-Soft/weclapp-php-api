<?php

require_once '../vendor/autoload.php';

use miralsoft\weclapp\api\Article;
use miralsoft\weclapp\api\ArticleCategory;

require_once 'configWeclapp.php';

$article = new Article();
$articleCategory = new ArticleCategory();

//$result = $article->get(1, 100, 'articleNumber');
//$result = $article->getAll('articleNumber');
$result = $article->getArticlesFromCategory($articleCategory->getArticleCategoryFromName('Service Dienstleistungen'));
//$result = $article->getArticlesFromCategory($articleCategory->getArticleCategoryFromName('Managed Services'));

if (is_array($result)) {
    echo 'Count results: ' . count($result);
}
echo '<br><br>';
print_r($result);
