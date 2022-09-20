<?php

namespace miralsoft\weclapp\api;

class Customer extends WeclappAPICall
{
    /** @var array|null all Customers in list */
    protected static $allCustomers = null;

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
     * Gives all Customers
     *
     * @param string $sort The field for sorting for (- sign as prefix for DESC sorting)
     * @return array The result
     */
    public function getAll(string $sort = '')
    {
        // Only make this call once
        if (self::$allCustomers == null) {
            // Only 1000 users per request are possible
            $customersPerPage = 900;
            $count = $this->count();

            $customers = [];
            for ($i = 1; $i <= ($count / $customersPerPage) + 1; $i++) {
                $result = $this->get($i, $customersPerPage, $sort);
                if (is_array($result) && count($result) > 0) {
                    $customers = array_merge($customers, $result);
                }
            }

            self::$allCustomers = $customers;
        }


        return self::$allCustomers;
    }

    /**
     * Get the Company with the given name
     *
     * @param string $company The company name which is searched
     * @return array|mixed The result
     */
    public function searchByCompany(string $company)
    {
        // Get all customers with sort by company
        $customers = $this->getAll('company');

        foreach ($customers as $customer) {
            // Search the company
            if (isset($customer['company']) && $customer['company'] != '' && $customer['company'] == $company) {
                return $customer;
            }
        }

        return [];
    }

    /**
     * Gets the customer from the contact
     *
     * @param array $contact The array from the contact
     * @return array|mixed
     */
    public function getCustomerFromContact(array $contact)
    {
        // If id is set, it is a correct contact
        if (isset($contact['id'])) {
            // first look over ID
            $customer = $this->getCustomerFromAll($contact['id']);

            if (isset($customer['id'])) {
                return $customer;
            } elseif (isset($contact['personCompany'])) {
                // if the contact is from a company and no customer found over the id, search the company
                return $this->searchByCompany($contact['personCompany']);
            }
        }

        return [];
    }

    /**
     * Gets a user from all users - more faster than getCustomer() for big calls, but not updated on every call
     *
     * @param string $id the searched id
     * @return array|mixed
     */
    public function getCustomerFromAll(string $id){
        // Get all customers with sort by company
        $customers = $this->getAll('company');

        foreach ($customers as $customer) {
            // Search the ID
            if (isset($customer['id']) && $customer['id'] != '' && $customer['id'] == $id) {
                return $customer;
            }
        }

        return [];
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