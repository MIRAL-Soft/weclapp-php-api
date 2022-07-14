<?php


namespace miralsoft\weclapp\api;


class SalesOrder extends WeclappAPICall
{

    /**
     * Gives the count of all Orders
     *
     * @return int The json result
     */
    public function count(): int
    {
        $this->subFunction = 'count';
        return $this->call();
    }

    /**
     * Gives the Orders
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
     * Get the Order with this ID
     *
     * @param string $id The ID from the Order
     * @return array The Order
     */
    public function getOrder(string $id)
    {
        $this->subFunction = 'id/' . $id;
        return $this->call();
    }

    public function getOrderPDF(string $id)
    {
        $this->subFunction = 'id/' . $id . '/downloadLatestOrderConfirmationPdf';
        return $this->call(array(), false, false);
    }
}