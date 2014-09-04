<?php

class CRM_Quartelymembershippayment_MembershipType {
  
  public static function getPayQuartely($membership_type_id) {
    if (empty($membership_type_id)) {
      return false;
    }
    
    $sql = "SELECT * FROM `civicrm_membership_type_pay_quartely` WHERE `membership_type_id` = %1";
    $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($membership_type_id, 'Integer')));
    if ($dao->fetch()) {
      return $dao->pay_quartely ? true : false;
    }
    return false;
  }
  
  public static function isValidForPayPerQuarter($membership_type_id) {
    if (!self::getPayQuartely($membership_type_id)) {
      return false;
    }
    
    //check if period is one year
    $membershipType = new CRM_Member_DAO_MembershipType();
    $membershipType->id = $membership_type_id;
    if ($membershipType->find(TRUE)) {
      if ($membershipType->duration_unit == 'year' && $membershipType->duration_interval == 1) {
        return true;
      }
    }
    
    return false;
  }
  
  public static function exist($membership_type_id) {
    if (empty($membership_type_id)) {
      return false;
    }
    
    $sql = "SELECT * FROM `civicrm_membership_type_pay_quartely` WHERE `membership_type_id` = %1";
    $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($membership_type_id, 'Integer')));
    if ($dao->fetch()) {
      return true;
    }
    return false;
  }
  
  public static function store($membership_type_id, $pay_quartely) {
    if (empty($membership_type_id)) {
      return;
    }
    
    if (self::exist($membership_type_id)) {
      //update query
      $sql = "UPDATE `civicrm_membership_type_pay_quartely` SET `pay_quartely` = %1 WHERE `membership_type_id`  = %2";
    } else {
      $sql = "INSERT INTO `civicrm_membership_type_pay_quartely` (`pay_quartely`, `membership_type_id`) VALUES (%1, %2)";
    }
    
    CRM_Core_DAO::executeQuery($sql, array(
      1 => array(($pay_quartely ? '1' : '0'), 'Integer'),
      2 => array($membership_type_id, 'Integer')
    ));
  }
  
  public static function delete($membership_type_id) {
    if (empty($membership_type_id)) {
      return;
    }
    
    if (self::exist($membership_type_id)) {
      $sql = "DELETE FROM `civicrm_membership_type_pay_quartely` WHERE `membership_type_id`  = %1";
      CRM_Core_DAO::executeQuery($sql, array(
        1 => array($membership_type_id, 'Integer')
      ));
    }
  }
  
}
