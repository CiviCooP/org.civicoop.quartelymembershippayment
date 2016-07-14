<?php

class CRM_Quartelymembershippayment_Handler {
 
  protected $id;
  
  protected $params;
  
  protected $isValid = null;
  
  protected $first_contribution;
  
  protected $membership;
  
  protected static $_doNotCheckContributionIds = array();
  
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
    
    if (in_array($this->params['contribution_id'], self::$_doNotCheckContributionIds)) {
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

      if ($q == 1 && $firstContributionDate->format('Ymd') < $startQDate->format('Ymd')) {
        //when first contribution is in current year, but next quarter is in next year
        //set first contribution a year later so that we can move it back to the first day of
        //the first quarter        
        $this->alterContribution($startQDate);
        $firstContributionDate = clone $startQDate;
      } elseif ($firstContributionDate->format("Ymd") >= $startQDate->format("Ymd") && $firstContributionDate->format("Ymd") <= $endQDate->format("Ymd")) {
        $nextStartQDate = clone $startQDate;
        $nextStartQDate->modify("+3 months");
        
        $this->alterContribution($nextStartQDate);
        $firstContributionDate = clone $nextStartQDate;

        //skip one quarter
        $startQDate->modify("+3 months");
        $q++;

      } elseif ($firstContributionDate->format("Ymd") < $startQDate->format("Ymd")) {
        $this->addNewContribution($startQDate);
      }
      
      $startQDate->modify("+3 months");
    }
  }
  
  protected function alterContribution(DateTime $receive_date) {
    $params = $this->first_contribution;
    $instrument_id = $this->getPaymenyInstrument($params);
    $params['receive_date'] = $receive_date->format('YmdHis');
		unset($params['payment_instrument']);
    unset($params['instrument_id']);
    if ($instrument_id) {
      $params['payment_instrument_id'] = $instrument_id;
    }
    unset($params['soft_credit']);
    unset($params['soft_credit_to']);
    unset($params['soft_credit_id']);
    $result = civicrm_api3('Contribution', 'create', $params);
  }
  
  protected function addNewContribution(DateTime $receive_date) {
    $params = $this->first_contribution;

    $instrument_id = $this->getPaymenyInstrument($params);
    $params['receive_date'] = $receive_date->format('YmdHis');
		unset($params['payment_instrument']);
    unset($params['contribution_id']);
    unset($params['id']);    
    unset($params['instrument_id']);
    if ($instrument_id) {
      $params['payment_instrument_id'] = $instrument_id;
    }
    unset($params['soft_credit']);
    unset($params['soft_credit_to']);
    unset($params['soft_credit_id']);
        
    $result = civicrm_api3('Contribution', 'create', $params);
    // Get Soft contributions
    $softContributions = civicrm_api3('ContributionSoft', 'get', array('contribution_id' => $this->first_contribution['id']));
    foreach($softContributions['values'] as $softContribution) {
      $newSoftContribution = $softContribution;
      unset($newSoftContribution['id']);
      $newSoftContribution['contribution_id'] = $result['id'];
      civicrm_api3('ContributionSoft', 'create', $newSoftContribution);
    }

    
    //prevent looping with this new contribution record
    self::$_doNotCheckContributionIds[] = $result['id'];
    
    //$mpBao = new CRM_Member_BAO_MembershipPayment();
    $mpBao['membership_id'] = $this->membership['id'];
    $mpBao['contribution_id'] = $result['id'];
    CRM_Member_BAO_MembershipPayment::create($mpBao);   
  }
  
  protected function getRelatedContribution() {
    $contribution = civicrm_api3('Contribution', 'getsingle', array('id' => $this->params['contribution_id']));
    return $contribution;
  }
  
  protected function getRelatedMembership() {
    $membership = civicrm_api3('Membership', 'getsingle', array('id' => $this->params['membership_id']));
    return $membership;
  }
  
  protected function getPaymenyInstrument($contribution) {
    if (empty($contribution['instrument_id'])) {
      return false;
    }
    
    $instrument_id = CRM_Core_OptionGroup::getValue('payment_instrument', $contribution['instrument_id'], 'id', 'Integer');
    if (empty($instrument_id)) {
      return false;
    }
    return $instrument_id;
  }
}

