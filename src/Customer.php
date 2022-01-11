<?php

namespace miralsoft\weclapp\api;

class Customer extends WeclappAPICall
{
    /**
     * Gives the count of all invoices
     *
     * @return int The json result
     */
    public function count(): int
    {
        $this->subFunction = 'count';
        return $this->call();
    }

    /**
     * Gives the Customers
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
     * Get the Customer with this ID
     *
     * @param string $id The ID from the customer
     * @return array The Customer
     */
    public function getCustomer(string $id)
    {
        $this->subFunction = 'id/' . $id;
        return $this->call();
    }
}