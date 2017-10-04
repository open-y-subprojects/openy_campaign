<?php

namespace Drupal\openy_campaign\Plugin\Block;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\openy_campaign\CampaignMenuServiceInterface;
use Drupal\Core\Url;
use Drupal\openy_campaign\Entity\MemberCampaign;

/**
 * Provides a Campaign user menu block.
 *
 * @Block(
 *   id = "campaign_user_menu_block",
 *   admin_label = @Translation("Campaign user menu block"),
 *   category = @Translation("Campaign"),
 * )
 */
class CampaignUserMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Campaign menu service.
   *
   * @var \Drupal\openy_campaign\CampaignMenuServiceInterface
   */
  protected $campaignMenuService;

  /**
   * Constructs a new Campaign user menu block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param CampaignMenuServiceInterface $campaign_menu_service
   *   The Campaign menu service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CampaignMenuServiceInterface $campaign_menu_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->campaignMenuService = $campaign_menu_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('openy_campaign.campaign_menu_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Disable block cache.
    $build['#cache']['max-age'] = 0;

    // Extract Campaign node from route.
    $campaign = $this->campaignMenuService->getCampaignNodeFromRoute();
    if (empty($campaign)) {
      return $build;
    }

    // For logged in members
    if (MemberCampaign::isLoggedIn($campaign->id())) {
      $userData = MemberCampaign::getMemberCampaignData($campaign->id());
      $fullName = !empty($userData['full_name']) ? $userData['full_name'] : $this->t('Team member');

      $build['full_name'] = [
        '#markup' => '<div class="member-full-name">'.
            '<i class="fa fa-user-o" aria-hidden="true"></i>' . $fullName . '</div>' ,
      ];

      /*$build['logout'] = [
        '#type' => 'link',
        '#title' => $this->t('Logout'),
        '#url' => Url::fromRoute('openy_campaign.member-logout', ['campaign_id' => $campaign->id()]),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'logout'
          ],
        ],
      ];*/
    }
    else {
      $build['register'] = [
        '#type' => 'link',
        '#title' => $this->t('Register'),
        '#url' => Url::fromRoute('openy_campaign.member-action', ['action' => 'registration', 'campaign_id' => $campaign->id()]),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'register'
          ],
        ],
      ];

      $build['login'] = [
        '#type' => 'link',
        '#title' => $this->t('Sign in'),
        '#url' => Url::fromRoute('openy_campaign.member-action', ['action' => 'login', 'campaign_id' => $campaign->id()]),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'login'
          ],
        ],
      ];
    }

    $build['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $build;
  }
}