<?php

require_once 'quartelymembershippayment.civix.php';

function quartelymembershippayment_civicrm_post($op, $objectName, $id, $objRef) {
  //echo $objectName; echo "<hr>";
  if ($op == 'create' && $objectName == 'MembershipPayment') {
    $handler = new CRM_Quartelymembershippayment_Handler($id, $objRef);
    if ($handler->isValid()) {
      $handler->createQuartelyContributions();
    }
  }
}

function quartelymembershippayment_civicrm_buildForm($formName, &$form) {
  //we can only update memberships when we edit the membership type 
  //not on creation
  if ($formName == 'CRM_Member_Form_MembershipType' && ($form->getVar('_action') & CRM_Core_Action::UPDATE)) {
    $form->add('checkbox', 'pay_quartely', ts('Payment per quarter?'));
    
    $membership_type_id = $form->getVar('_id');
    $defaults['pay_quartely'] = CRM_Quartelymembershippayment_MembershipType::getPayQuartely($membership_type_id);
    $form->setDefaults($defaults);
    
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => "CRM/MembershipType/pay_quartely.tpl"
     ));
  }
}

function quartelymembershippayment_civicrm_postProcess( $formName, &$form ) {
  //we can only update memberships when we edit the membership type 
  //not on creation
  if ($formName == 'CRM_Member_Form_MembershipType' && ($form->getVar('_action') & CRM_Core_Action::UPDATE)) {
    $membership_type_id = $form->getVar('_id');
    $values = $form->controller->exportValues($form->getVar('_name'));
    $pay_quartely = false;
    if (isset($values['pay_quartely']) && !empty($values['pay_quartely'])) {
      $pay_quartely = true;
    }
    CRM_Quartelymembershippayment_MembershipType::store($membership_type_id, $pay_quartely);
  } elseif ($formName == 'CRM_Member_Form_MembershipType' && ($form->getVar('_action') & CRM_Core_Action::DELETE)) {
    $membership_type_id = $form->getVar('_id');
    CRM_Quartelymembershippayment_MembershipType::delete($membership_type_id);
  }
}


/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function quartelymembershippayment_civicrm_config(&$config) {
  _quartelymembershippayment_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function quartelymembershippayment_civicrm_xmlMenu(&$files) {
  _quartelymembershippayment_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function quartelymembershippayment_civicrm_install() {
  return _quartelymembershippayment_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function quartelymembershippayment_civicrm_uninstall() {
  return _quartelymembershippayment_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function quartelymembershippayment_civicrm_enable() {
  return _quartelymembershippayment_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function quartelymembershippayment_civicrm_disable() {
  return _quartelymembershippayment_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function quartelymembershippayment_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _quartelymembershippayment_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function quartelymembershippayment_civicrm_managed(&$entities) {
  return _quartelymembershippayment_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function quartelymembershippayment_civicrm_caseTypes(&$caseTypes) {
  _quartelymembershippayment_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function quartelymembershippayment_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _quartelymembershippayment_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
