<?php

namespace Drupal\openy_campaign\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for ymca_campaign_member entity.
 *
 * @ingroup openy_campaign_member
 */
class MemberCampaignListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the Member list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('Record ID');
    $header['member'] = $this->t('Member ID');
    $header['name'] = $this->t('Member name');
    $header['membership_id'] = $this->t('Membership ID');
    $header['campaign'] = $this->t('Campaign');
    $return = $header + parent::buildHeader();
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['member'] = $entity->member->entity->id();
    $row['name'] = $entity->member->entity->getFullName();
    $row['membership_id'] = $entity->member->entity->getMemberId();
    $row['campaign'] = $entity->campaign->entity->getTitle();

    return $row + parent::buildRow($entity);
  }

}