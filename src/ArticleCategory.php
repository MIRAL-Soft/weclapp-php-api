<?php

namespace miralsoft\weclapp\api;

class ArticleCategory extends WeclappAPICall
{
    /**
     * Gives the count of all article category
     *
     * @return int The json result
     */
    public function count(): int
    {
        $this->subFunction = 'count';
        return $this->call();
    }

    /**
     * Gives the article category
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
     * Gives all article category
     *
     * @param string $sort The field for sorting for (- sign as prefix for DESC sorting)
     * @return array The result
     */
    public function getAll(string $sort = '')
    {
        // Only 1000 per request are possible
        $articleCategoriesPerPage = 900;
        $count = $this->count();

        $articleCategories = [];
        for ($i = 1; $i <= ($count / $articleCategoriesPerPage) + 1; $i++) {
            $result = $this->get($i, $articleCategoriesPerPage, $sort);
            if (is_array($result) && count($result) > 0) {
                $articleCategories = array_merge($articleCategories, $result);
            }
        }

        return $articleCategories;
    }

    /**
     * Get the article category with this ID
     *
     * @param string $id The ID from the article category
     * @return array The article category
     */
    public function getArticleCategory(string $id)
    {
        $this->subFunction = 'id/' . $id;
        return $this->call();
    }

    /**
     * Get the article category from name
     *
     * @param string $name the name which is searched
     * @return string The id from category
     */
    public function getArticleCategoryFromName(string $name): string
    {
        $categories = $this->getAll();

        foreach($categories as $category){
            if(is_array($category) && isset($category['id']) && isset($category['name']) && $category['name'] == $name){
                return $category['id'];
            }
        }

        return -1;
    }
}