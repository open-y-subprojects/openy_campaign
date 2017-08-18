<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\Member;
use Drupal\openy_campaign\Entity\MemberCampaign;

/**
 * Form controller for the Simplified Team Member Registration Portal form.
 *
 * @ingroup openy_campaign_member
 */
class MemberRegistrationPortalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_campaign_registration_portal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $campaignIds = \Drupal::entityQuery('node')->condition('type','campaign')->execute();
    $campaigns = Node::loadMultiple($campaignIds);
    $options = [];
    foreach ($campaigns as $item) {
      /** @var Node $item */
      $options[$item->id()] = $item->getTitle();
    }

    // Select Campaign to assign Member
    $form['campaign_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Campaign'),
      '#options' => $options,
    ];

    // The id on the membership card.
    $form['membership_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Scan the Membership ID'),
      '#default_value' => '',
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Register Team Member'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('openy_campaign.general_settings');
    $errorDefault = $config->get('error_msg_default');

    $campaignID = $form_state->getValue('campaign_id');
    $membershipID = $form_state->getValue('membership_id');
    // TODO Add check length of $membershipID
    if (!is_numeric($membershipID)) {
      $form_state->setErrorByName('membership_id', $this->t('Please, check Membership ID number.'));
      return;
    }

    // Check MemberCampaign entity
    $isMemberCampaignExists = MemberCampaign::isMemberCampaignExists($membershipID, $campaignID);

    // If the member is already registered, there will be a basic error message “This member has already registered”.
    if ($isMemberCampaignExists) {
      $form_state->setErrorByName('membership_id', $this->t('This member has already registered.'));
      return;
    }

    /** @var Member $member Load or create Temporary Member entity. Will be saved by submit. */
    $member = Member::loadMemberFromCRMData($membershipID);
    if (($member instanceof Member === FALSE) || empty($member)) {
      $form_state->setErrorByName('membership_id', $errorDefault);

      return;
    }

    // Get Campaign entity
    $campaign = Node::load($campaignID);

    /** @var MemberCampaign $memberCampaign Create temporary MemberCampaign entity. Will be saved by submit. */
    $memberCampaign = MemberCampaign::createMemberCampaign($member, $campaign);
    if (($memberCampaign instanceof MemberCampaign === FALSE) || empty($memberCampaign)) {
      $form_state->setErrorByName('membership_id', $errorDefault);

      return;
    }

    // Check Target Audience Settings from Campaign.
    $validateAudienceErrorMessages = $memberCampaign->validateTargetAudienceSettings();

    // Member is ineligible due to the Target Audience Setting
    if (!empty($validateAudienceErrorMessages)) {
      $errorText = implode('<br>', $validateAudienceErrorMessages);
      $form_state->setErrorByName('membership_id',
        $this->t('This member is not eligible for the campaign for the following reasons:<br>@errors', ['@errors' => $errorText]));

      return;
    }

    // Save Member and MemberCampaign entities in storage to save by submit.
    $form_state->setStorage([
      'member' => $member,
      'member_campaign' => $memberCampaign,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get Member and MemberCampaign entities from storage.
    $storage = $form_state->getStorage();

    // Save Member entity.
    if (!empty($storage['member'])) {
      /** @var Member $member Member object. */
      $member = $storage['member'];
      $member->save();
    }

    // Save MemberCampaign entity.
    if (!empty($storage['member_campaign'])) {
      /** @var MemberCampaign $memberCampaign MemberCampaign object. */
      $memberCampaign = $storage['member_campaign'];
      $memberCampaign->save();
    }

    // If the member has not previously registered, there will be a basic message "This member is now registered".
    drupal_set_message(t('This member is now registered'), 'status', TRUE);
  }

}