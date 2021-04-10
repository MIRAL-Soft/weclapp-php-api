<?php


namespace weclapp\api;


class SalesInvoice extends WeclappAPICall
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
     * Gives the invoices
     *
     * @param int $page The page
     * @param int $pageSize Pagesize
     * @param string $sort Sort (@see weclapp\api\Sort)
     * @return array The result
     */
    public function get(int $page = 1, int $pageSize = 50, string $sort = Sort::DESC) {
        $data = array('page' => $page, 'pageSize' => $pageSize, 'sort' => $sort);
        return $this->call($data);
    }

    /**
     * Get the Invoice with this ID
     *
     * @param string $id The ID from the invoice
     * @return array The invoice
     */
    public function getInvoice(string $id) {
        $this->subFunction = 'id/' . $id;
        return $this->call();
    }

    public function getInvoicePDF(string $id) {
        $this->subFunction = 'id/' . $id . '/downloadLatestSalesInvoicePdf';
        return $this->call(array(), false,false);
    }
}