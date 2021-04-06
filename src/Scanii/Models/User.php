<?php

namespace Scanii\Models;

class User
{
    private $creationDate, $lastLoginDate;

    /**
     * User constructor.
     * @param $creationDate
     * @param $lastLoginDate
     */
    public function __construct($creationDate, $lastLoginDate)
    {
        $this->creationDate = $creationDate;
        $this->lastLoginDate = $lastLoginDate;
    }

    /**
     * @return mixed
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @return mixed
     */
    public function getLastLoginDate()
    {
        return $this->lastLoginDate;
    }


}
