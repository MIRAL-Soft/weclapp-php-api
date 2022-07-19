<?php

namespace miralsoft\weclapp\api;

class Article extends WeclappAPICall
{
    /**
     * Gives the count of all articles
     *
     * @return int The json result
     */
    public function count(): int
    {
        $this->subFunction = 'count';
        return $this->call();
    }

    /**
     * Gives the articles
     *
     * @param int $page The page
     * @param int $pageSize Pagesize
     * @param string $sort The field for sorting for (- sign as prefix for DESC sorting)
     * @return array The result
     */
    public function get(int $page = 1, int $pageSize = 50, string $sort = '')
    {
        $this->subFunction = '';
        $data = array('page' => $page, 'pageSize' => $pageSize, 'sort' => $sort);
        return $this->call($data);
    }

    /**
     * Gives all articles
     *
     * @param string $sort The field for sorting for (- sign as prefix for DESC sorting)
     * @return array The result
     */
    public function getAll(string $sort = '')
    {
        // Only 1000 users per request are possible
        $articlesPerPage = 900;
        $count = $this->count();

        $articles = [];
        for ($i = 1; $i <= ($count / $articlesPerPage) + 1; $i++) {
            $result = $this->get($i, $articlesPerPage, $sort);
            if (is_array($result) && count($result) > 0) {
                $articles = array_merge($articles, $result);
            }
        }

        return $articles;
    }

    /**
     * Get the article with this ID
     *
     * @param string $id The ID from the article
     * @return array The article
     */
    public function getArticle(string $id)
    {
        $this->subFunction = 'id/' . $id;
        return $this->call();
    }

    /**
     * Gets all articles from the given category
     *
     * @param string $category the category
     * @return array the article within the category
     */
    public function getArticlesFromCategory(string $category): array
    {
        $articlesInCategory = [];

        // increment all articles
        $allArticles = $this->getAll('articleNumber');
        if (is_array($allArticles) && count($allArticles) > 0) {
            foreach($allArticles as $article){
                // If the article is in category
                if(isset($article['articleCategoryId']) && $article['articleCategoryId'] == $category){
                    $articlesInCategory[] = $article;
                }
            }
        }

        return $articlesInCategory;
    }
}