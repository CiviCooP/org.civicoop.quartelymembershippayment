<?php

class CRM_Quartelymembershippayment_Handler {
 
  protected $id;
  
  protected $params;
  
  protected $isValid = null;
  
  protected $first_contribution;
  
  protected $membership;
  
  public function __construct($id, $params) {
    $this->id = $id;
    if (is_object($params)) {
      $values = array();
      CRM_Core_DAO::storeValues($params, $values);
      $this->params = $values;
    } elseif (is_array($this->params)) {
      $this->params = $params;
    } else {
      $this->params = array();
    }
  }
  
  /*
   * Do a check wether the params are valid for processing
   * 
   */
  protected function preCheck() {
    if (!count($this->params)) {
      $this->isValid = false;
      return;
    }
    if (empty($this->params['contribution_id'])) {
      $this->isValid = false;
      return;
    }
    
    try {
      $this->membership = $this->getRelatedMembership();
      $this->first_contribution = $this->getRelatedContribution();
    } catch (Exception $ex) {
      $this->isValid = false;
      return;
    }
    
    if (!CRM_Quartelymembershippayment_MembershipType::isValidForPayPerQuarter($this->membership['membership_type_id'])) {
      $this->isValid = false;
      return;
    }
    
    $this->isValid = true;
  }
  
  public function isValid() {
    if (!isset($this->isValid)) {
      $this->preCheck();
    }    
    return $this->isValid;
  }
  
  public function createQuartelyContributions() {
    if (!$this->isValid()) {
      return;
    }
    
    $firstContributionDate = new DateTime($this->first_contribution['receive_date']);
    if (isset($this->membership['end_date'])) {
      //use end date because in case of renewal we don't know the renewal date
      //but we know the end date of the membership
      $startQDate = new DateTime($this->membership['end_date']);
      $startQDate->modify("-1 year");
      $startQDate->modify("+1 day");
    } else {
      $startQDate = new DateTime($this->membership['start_date']);
    }
    
    for($q = 1; $q <= 4; $q++) {
      $endQDate = clone $startQDate;
      $endQDate->modify("+3 months");
      $endQDate->modify("-1 day");

      if ($firstContributionDate->format("Ymd") > $startQDate->format("Ymd") && $firstContributionDate->format("Ymd") <= $endQDate->format("Ymd")) {
        $nextStartQDate = clone $startQDate;
        $nextStartQDate->modify("+3 months");
        
        $this->alterContribution($nextStartQDate);
        $firstContributionDate = clone $nextStartQDate;
      } elseif ($firstContributionDate->format("Ymd") < $startQDate->format("Ymd")) {
        $this->addNewContribution($startQDate);
      }
      
      $startQDate->modify("+3 months");
    }
  }
  
  protected function alterContribution(DateTime $receive_date) {
    $params = $this->first_contribution;
    $params['receive_date'] = $receive_date->format('YmdHis');
		unset($params['payment_instrument']);
    $result = civicrm_api3('Contribution', 'create', $params);
  }
  
  protected function addNewContribution(DateTime $receive_date) {
    $params = $this->first_contribution;
    $params['receive_date'] = $receive_date->format('YmdHis');
		unset($params['payment_instrument']);
    unset($params['contribution_id']);
    unset($params['id']);
    
    $result = civicrm_api3('Contribution', 'create', $params);
    //record membership payment for this contribution
    //we don't use the bao for this otherwise we create loop to ourselves ;-)
    $sql = "INSERT INTO `civicrm_membership_payment` (`membership_id`, `contribution_id`) VALUES (%1, %2)";
    CRM_Core_DAO::executeQuery($sql, array(
      1 => array($this->membership['id'], 'Integer'),
      2 => array($result['id'], 'Integer')
    ));
  }
  
  protected function getRelatedContribution() {
    $contribution = civicrm_api3('Contribution', 'getsingle', array('id' => $this->params['contribution_id']));
    return $contribution;
  }
  
  protected function getRelatedMembership() {
    $membership = civicrm_api3('Membership', 'getsingle', array('id' => $this->params['membership_id']));
    return $membership;
  }
  
}

