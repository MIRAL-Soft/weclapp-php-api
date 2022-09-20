<?php

namespace miralsoft\weclapp\api;

class Contact extends WeclappAPICall
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
     * Gives the Contacts
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
     * Gives all Contacts
     *
     * @param string $sort The field for sorting for (- sign as prefix for DESC sorting)
     * @return array The result
     */
    public function getAll(string $sort = ''){
        // Only 1000 users per request are possible
        $contactsPerPage = 900;
        $count = $this->count();

        $contacts = [];
        for($i = 1; $i <= ($count/$contactsPerPage) + 1; $i++){
            $result = $this->get($i, $contactsPerPage, $sort);
            if(is_array($result) &&  count($result) > 0){
                $contacts = array_merge($contacts, $result);
            }
        }

        return $contacts;
    }

    /**
     * Get the Contact with this ID
     *
     * @param string $id The ID from the contact
     * @return array The Contact
     */
    public function getContact(string $id)
    {
        $this->subFunction = 'id/' . $id;
        return $this->call();
    }
}