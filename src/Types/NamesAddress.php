<?php

namespace Frknakk\Internetmarke\Types;

class NamesAddress
{
    protected $person_salutation;
    protected $person_title;
    protected $person_firstname;
    protected $person_lastname;
    protected $company_name;
    protected $address_additional;
    protected $address_street;
    protected $address_housenr;
    protected $address_zipcode;
    protected $address_city;
    protected $address_country;

    public function salutation($val) {
        $this->person_salutation = trim($val);
        return $this;
    }

    public function title($val) {
        $this->person_title = trim($val);
        return $this;
    }

    public function firstname($val) {
        $this->person_firstname = trim($val);
        return $this;
    }

    public function lastname($val) {
        $this->person_lastname = trim($val);
        return $this;
    }

    public function company($val) {
        $this->company_name = trim($val);
        return $this;
    }

    public function additional($val) {
        $this->address_additional = trim($val);
        return $this;
    }

    public function street($val) {
        $this->address_street = trim($val);
        return $this;
    }

    public function housenr($val) {
        $this->address_housenr = trim($val);
        return $this;
    }

    public function zipcode($val) {
        $this->address_zipcode = trim($val);
        return $this;
    }

    public function city($val) {
        $this->address_city = trim($val);
        return $this;
    }

    public function country($val) {
        $this->address_country = trim($val);
        return $this;
    }

    public function streetAndHousenr($val) {
        $this->address_street = trim($val);
        $this->address_housenr = null;

        $parts = explode(' ', trim($val));
        if (count($parts) > 1)
        {
            $this->address_housenr = $parts[count($parts) - 1];
            unset($parts[count($parts) - 1]);
            $this->address_street = implode(' ', $parts);
        }

        return $this;
    }

    public function zipcodeAndCity($val) {
        $this->address_zipcode = null;
        $this->address_city = trim($val);

        $parts = explode(' ', trim($val));
        if (count($parts) > 1)
        {
            $this->address_zipcode = $parts[0];
            unset($parts[0]);
            $this->address_city = implode(' ', $parts);
        }

        return $this;
    }

    public function toArray()
    {
        $arr = [
            'name' => [],
            'address' => [
                'additional' => $this->address_additional,
                'street' => $this->address_street,
                'houseNo' => $this->address_housenr,
                'zip' => $this->address_zipcode,
                'city' => $this->address_city,
                'country' => $this->address_country,
            ]
        ];

        $personName = null;
        if (!empty($this->person_firstname) && !empty($this->person_lastname))
        {
            $personName = [
                'salutation' => $this->person_salutation,
                'title' => $this->person_title,
                'firstname' => $this->person_firstname,
                'lastname' => $this->person_lastname,
            ];
        }

        if (!empty($this->company_name))
        {
            $arr['name']['companyName'] = [
                'company' => $this->company_name,
                'personName' => $personName,
            ];
        }
        else
        {
            $arr['name']['personName'] = $personName;
        }

        return $arr;
    }

    public static function create()
    {
        return new static;
    }
}
