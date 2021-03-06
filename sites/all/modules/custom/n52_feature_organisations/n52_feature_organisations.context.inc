<?php
/**
 * @file
 * n52_feature_organisations.context.inc
 */

/**
 * Implements hook_context_default_contexts().
 */
function n52_feature_organisations_context_default_contexts() {
  $export = array();

  $context = new stdClass();
  $context->disabled = FALSE; /* Edit this to true to make a default context disabled initially */
  $context->api_version = 3;
  $context->name = 'organisations';
  $context->description = '';
  $context->tag = 'main_menu';
  $context->conditions = array(
    'path' => array(
      'values' => array(
        'organisation*' => 'organisation*',
        'organisation/*' => 'organisation/*',
      ),
    ),
  );
  $context->reactions = array(
    'menu' => array(
      0 => 'main-menu:organisation',
    ),
  );
  $context->condition_mode = 0;

  // Translatables
  // Included for use with string extractors like potx.
  t('main_menu');
  $export['organisations'] = $context;

  return $export;
}
