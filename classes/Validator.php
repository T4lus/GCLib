<?php

namespace Classes;

/**
 * Validator
 */

class Validator extends \GcLib\Validator
{

	/**
	 * Valid: E-Mail or Empty
     * 
	 * @param type $email
	 * @return boolean
	 */
	public static function emailorempty($email) {
        if(!empty($email))
        {
            return  (bool)\preg_match('/^[a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]+[.a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]*@[a-z\p{L}0-9]+[._a-z\p{L}0-9-]*\.[a-z0-9]+$/ui', $email);
        }
        return true;		
	}

    public static function dateorempty($date) {
        if(!empty($date))
        {
            if (!\preg_match('/^([0-9]{4})-((0?[0-9])|(1[0-2]))-((0?[0-9])|([1-2][0-9])|(3[01]))( [0-9]{2}:[0-9]{2}:[0-9]{2})?$/', $date, $matches))
                return false;
            return \checkdate((int)$matches[2], (int)$matches[5], (int)$matches[0]);
        }
        return true;
    }

    public static function zipcode($zipcode) {
        $value = (int)$zipcode;

        if(empty($zipcode) || $value <= 999)
        {
            return false;
        }
        return (bool)\preg_match('/^([0-9]{5}|[0-9]{4})$/', $zipcode);
    }   
}