<?php

namespace Drupal\config_entity_access_deny\Controller;

use Drupal\block_content\Controller\BlockContentController as DrupalBlockContentController;
use Drupal\config_entity_access_deny\ConfigEntityAccessDenyTrait;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BlockContentController.
 */
class BlockContentController extends DrupalBlockContentController {

  use ConfigEntityAccessDenyTrait;

  /**
   * {@inheritdoc}
   */
  public function add(Request $request) {
    $build = parent::add($request);
    $types = $build['#content'] ?? [];
    // Filter denied config entity.
    foreach ($types as $key => $type) {
      if ($this->isDeniedEntity($type)) {
        unset($types[$key]);
      }
    }
    if (count($types) == 1) {
      return $this->addForm(reset($types), $request);
    }
    if (count($types) === 0) {
      return [
        '#markup' => $this->t('You have not created any block types yet. Go to the <a href=":url">block type creation page</a> to add a new block type.', [
          ':url' => Url::fromRoute('block_content.type_add')->toString(),
        ]),
      ];
    }
    $build['#content'] = $types;
    return $build;
  }

}
