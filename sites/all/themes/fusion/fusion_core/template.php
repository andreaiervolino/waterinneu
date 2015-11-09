<?php

define('FUSION_FILE_NOT_FOUND', 404);

/**
 * Maintenance page preprocessing
 */
function fusion_core_preprocess_maintenance_page(&$vars) {
  if (class_exists('Database', FALSE)) {
    // set html vars (html.tpl.php is in maintenance-page.tpl.php)
    fusion_core_preprocess_html($vars);
    // set page vars
    fusion_core_preprocess_page($vars);
  }
}


/**
 * HTML preprocessing
 */
function fusion_core_preprocess_html(&$vars) {
  global $theme_key, $user;

  // Add to array of helpful body classes.
  if (isset($vars['node'])) {
    // For full nodes.
    $vars['classes_array'][] = ($vars['node']) ? 'full-node' : '';
    // For forums.
    $vars['classes_array'][] = (($vars['node']->type == 'forum') || (arg(0) == 'forum')) ? 'forum' : '';
  }
  else {
    // Forums.
    $vars['classes_array'][] = (arg(0) == 'forum') ? 'forum' : '';
  }
  if (module_exists('panels') && function_exists('panels_get_current_page_display')) {
    $vars['classes_array'][] = (panels_get_current_page_display()) ? 'panels' : '';
  }
  $vars['classes_array'][] = (theme_get_setting('theme_font') != 'none') ? theme_get_setting('theme_font') : '';
  $vars['classes_array'][] = theme_get_setting('theme_font_size');
  $vars['classes_array'][] = (user_access('administer blocks', $user) && theme_get_setting('grid_mask')) ? 'grid-mask-enabled' : '';

  // Add grid classes
  $grid = fusion_core_grid_info();

  // Fixed or fluid.
  $vars['classes_array'][] = 'grid-type-' . $grid['type'];

  // Number of units in the grid (12, 16, etc.)
  $vars['classes_array'][] = 'grid-width-' . sprintf("%02d", $grid['width']);

  // Fluid grid width in %.
  $vars['classes_array'][] = ($grid['type'] == 'fluid') ? theme_get_setting('fluid_grid_width') : '';

  // Remove any empty elements in the array.
  $vars['classes_array'] = array_filter($vars['classes_array']);

  // Serialize RDF namespaces into an RDFa 1.1 prefix attribute.
  if (function_exists('rdf_get_namespaces')) {
    foreach (rdf_get_namespaces() as $prefix => $uri) {
      $prefixes[] = $prefix . ': ' . $uri;
    }
    $vars['rdf_namespaces'] = ' prefix="' . implode(' ', $prefixes) . '"';
  }

  $themes = fusion_core_theme_paths($theme_key);
  $file = theme_get_setting('theme_grid') . '.css';
  $cache = cache_get('fusion');
  $new_cache = array();

  if (module_exists('fusion_accelerator') && theme_get_setting('responsive_enabled')) {

    $responsive_path = variable_get($theme_key . '_responsive_path');
    $responsive_file = variable_get($theme_key . '_responsive_css');

    if (empty($cache->data['grid_generated'])) {
      $rebuild_css = FALSE;
      if (!$responsive_path || $responsive_file) {
        // Variables are missing, either because the settings have never been accessed
        // or they've been manually deleted.
        $rebuild_css = TRUE;
      }
      elseif (!file_exists($responsive_path . '/' . $responsive_css)) {
        $rebuild_css = TRUE;
      }
      if ($rebuild_css) {
        // rebuild responsive CSS based on theme defaults.
        $responsive_options = _fusion_accelerator_get_responsive_options($theme_key);
        $options = array();
        foreach ($responsive_options as $op) {
          if ($setting = theme_get_setting($op, $theme_key)) {
            $options[$op] = $setting;
          }
        }
        $options['theme'] = $theme_key;
        $options['units'] = $options['responsive_columns'];
        $options['responsive'] = TRUE;
        _fusion_accelerator_save_grid($options);

        $new_cache['grid_generated'] = TRUE;
      }
    }

    // Add classes to indicate the sidebar widths across all responsive layouts.
    // Consolidate information about various layouts.
    $vars['mobile_friendly'] = TRUE;
    $vars['show_current_grid'] = (theme_get_setting('grid_mask')) ? TRUE : FALSE;

    // Fetch responsive grid.
    $path = variable_get($theme_key . '_responsive_path');
    $responsive_css = variable_get($theme_key . '_responsive_css');
    if ($path && $responsive_css) {
      // Generated by theme settings.
      drupal_add_css($path . '/' . $responsive_css);
    }

  } else {

    // Fusion Accelerator does not exist, or responsive has been disabled.
    // Search themes for grid files, and use those.
    $vars['mobile_friendly'] = FALSE;
    $vars['classes_array'][] = theme_get_setting('sidebar_layout');

    foreach ($themes as $name => $path) {
      if (empty($cache->data['grid_css'][$name][$file])) {
        if (file_exists($path . '/css/' . $file)) {
          drupal_add_css($path . '/css/' . $file, array('group' => CSS_THEME, 'preprocess' => TRUE));
          $new_cache['grid_css'][$name][$file] = TRUE;
        } else {
          $new_cache['grid_css'][$name][$file] = FUSION_FILE_NOT_FOUND;
        }
      } else {
        if ($cache->data['grid_css'][$name][$file] === TRUE) {
          drupal_add_css($path . '/css/' . $file, array('group' => CSS_THEME, 'preprocess' => TRUE));
        }
      }
    }

  }

  // Include any instances of local.css.
  foreach ($themes as $name => $path) {
    // Include any instances of local.css.
    if (empty($cache->data['local_css'][$path])) {
      if (file_exists($path . '/css/local.css')) {
        drupal_add_css($path . '/css/local.css', array('group' => CSS_THEME, 'preprocess' => TRUE));
        $new_cache['local_css'][$path] = TRUE;
      } else {
        $new_cache['local_css'][$path] = FUSION_FILE_NOT_FOUND;
      }
    } else {
      if ($cache->data['local_css'][$path] === TRUE) {
        drupal_add_css($path . '/css/local.css', array('group' => CSS_THEME, 'preprocess' => TRUE));
      }
    }

    // If responsive is enabled, include any instances of responsive.css.
    if (module_exists('fusion_accelerator') && theme_get_setting('responsive_enabled')) {
      if (empty($cache->data['responsive_css'][$path])) {
        if (file_exists($path . '/css/responsive.css')) {
          drupal_add_css($path . '/css/responsive.css', array('group' => CSS_THEME, 'preprocess' => TRUE));
          $new_cache['responsive_css'][$path] = TRUE;
        } else {
          $new_cache['responsive_css'][$path] = FUSION_FILE_NOT_FOUND;
        }
      } else {
        if ($cache->data['responsive_css'][$path] === TRUE) {
          drupal_add_css($path . '/css/responsive.css', array('group' => CSS_THEME, 'preprocess' => TRUE));
        }
      }
    }
  }

  // Add a unique page id.
  $vars['body_id'] = 'pid-' . drupal_clean_css_identifier(drupal_get_path_alias($_GET['q']));

  // Update cache, if necessary.
  if ($new_cache) {
    cache_set('fusion', $new_cache, 'cache', CACHE_TEMPORARY);
  }

}

/**
 * Page preprocessing
 */
function fusion_core_preprocess_page(&$vars) {

  // Set grid width
  $grid = fusion_core_grid_info();
  $vars['grid_width'] = $grid['name'] . $grid['width'];

  // Adjust width variables for nested grid groups
  $grid_adjusted_groups = (theme_get_setting('grid_adjusted_groups')) ? theme_get_setting('grid_adjusted_groups') : array();
  foreach (array_keys($grid_adjusted_groups) as $group) {
    $width = $grid['width'];
    foreach ($grid_adjusted_groups[$group] as $region) {
      $width = $width - $grid['regions'][$region]['width'];
    }
    if (!$grid['fixed'] && isset($grid['fluid_adjustments'][$group])) {
      $vars[$group . '_width'] = '" style="width:' . $grid['fluid_adjustments'][$group] . '%"';
    }
    else {
      $vars[$group . '_width'] = $grid['name'] . $width;
    }
  }

  // Remove breadcrumbs if disabled
  if (theme_get_setting('breadcrumb_display') == 0) {
    $vars['breadcrumb'] = '';
  }
}

/**
 * Region preprocessing
 */
function fusion_core_preprocess_region(&$vars) {
  static $grid;

  // Initialize grid info once per page
  if (!isset($grid)) {
    $grid = fusion_core_grid_info();
  }

  // Set region variables
  $vars['region_style'] = $vars['fluid_width'] = '';
  $vars['region_name'] = str_replace('_', '-', $vars['region']);
  $vars['classes_array'][] = $vars['region_name'];
  if (in_array($vars['region'], array_keys($grid['regions']))) {
    // Set region full-width or nested style
    $vars['region_style'] = $grid['regions'][$vars['region']]['style'];
    $vars['classes_array'][] = ($vars['region_style'] == 'nested') ? $vars['region_style'] : '';
    $vars['classes_array'][] = $grid['name'] . $grid['regions'][$vars['region']]['width'];
    // Adjust & set region width
    if (!$grid['fixed'] && isset($grid['fluid_adjustments'][$vars['region']])) {
      $vars['fluid_width'] = ' style="width:' . $grid['fluid_adjustments'][$vars['region']] . '%"';
    }
  }

  // Sidebar regions receive common class, "sidebar".
  $sidebar_regions = array('sidebar_first', 'sidebar_second');
  if (in_array($vars['region'], $sidebar_regions)) {
    $vars['classes_array'][] = 'sidebar';
  }
  
}

/**
 * Block preprocessing
 */
function fusion_core_preprocess_block(&$vars) {
  global $theme_info, $user;
  static $grid;

  // Exit if block is outside a defined region
  if (!in_array($vars['block']->region, array_keys($theme_info->info['regions']))) {
    return;
  }

  // Initialize grid info once per page
  if (!isset($grid)) {
    $grid = fusion_core_grid_info();
  }

  // Increment block count for current block's region, add first/last position class
  $grid['regions'][$vars['block']->region]['count'] ++;
  $region_count = $grid['regions'][$vars['block']->region]['count'];
  $total_blocks = $grid['regions'][$vars['block']->region]['total'];
  $vars['classes_array'][] = ($region_count == 1) ? 'first' : '';
  $vars['classes_array'][] = ($region_count == $total_blocks) ? 'last' : '';
  $vars['classes_array'][] = $vars['block_zebra'];

  // Set a default block width if not already set by Fusion Apply
  $classes = implode(' ', $vars['classes_array']);

  // Special treatment for node_top and node_bottom regions.
  // They are rendered inside of the $content region, so they need to be adjusted to fit the grid properly.
  $assign_grid_units = ($vars['block']->region == 'node_top' || $vars['block']->region == 'node_bottom') ? FALSE : TRUE;
  
  // add class and div to header top menu block
  if ($vars['block']->region =='header_top' && ($vars['block']->module == 'menu' || $vars['block']->module == 'system')){
  	$vars['content'] = "<div class=\"navcontainer\">" .$vars['content'] . "</div>"; 
  }
}


/**
 * Node preprocessing
 */
function fusion_core_preprocess_node(&$vars) {
  // Add to array of handy node classes
  $vars['classes_array'][] = $vars['zebra'];                              // Node is odd or even
  $vars['classes_array'][] = (!$vars['teaser']) ? 'full-node' : '';       // Node is teaser or full-node

  // Make select regions available to node template, but only on full node view.
  if ($vars['view_mode'] === 'full') {
    $node_region_list = array('node_top', 'node_bottom');
    $node_region_blocks = array();
    foreach($node_region_list as $region) {
      if ($list = fusion_core_block_list($region)) {
        $node_region_blocks[$region] = _block_get_renderable_array($list);
      }
      $vars[$region] = isset($node_region_blocks[$region]) ? $node_region_blocks[$region] : array();
    }
  }
}


/**
 * Comment preprocessing
 */
function fusion_core_preprocess_comment(&$vars) {
  static $comment_odd = TRUE;                                                                             // Comment is odd or even

  // Add to array of handy comment classes
  $vars['classes_array'][] = $comment_odd ? 'odd' : 'even';
  $comment_odd = !$comment_odd;
}


/**
 * Views preprocessing
 * Add view type class (e.g., node, teaser, list, table)
 */
function fusion_core_preprocess_views_view(&$vars) {
  $vars['css_name'] = $vars['css_name'] .' view-style-'. drupal_clean_css_identifier(strtolower($vars['view']->plugin_name));
}


/**
 * Search result preprocessing
 */
function fusion_core_preprocess_search_result(&$vars) {
  static $search_zebra = 'even';

  $search_zebra = ($search_zebra == 'even') ? 'odd' : 'even';
  $vars['search_zebra'] = $search_zebra;
  $result = $vars['result'];
  $vars['url'] = check_url($result['link']);
  $vars['title'] = check_plain($result['title']);

  // Check for snippet existence. User search does not include snippets.
  $vars['snippet'] = '';
  if (isset($result['snippet']) && theme_get_setting('search_snippet')) {
    $vars['snippet'] = $result['snippet'];
  }

  $info = array();
  if (!empty($result['type']) && theme_get_setting('search_info_type')) {
    $info['type'] = check_plain($result['type']);
  }
  if (!empty($result['user']) && theme_get_setting('search_info_user')) {
    $info['user'] = $result['user'];
  }
  if (!empty($result['date']) && theme_get_setting('search_info_date')) {
    $info['date'] = format_date($result['date'], 'small');
  }
  if (isset($result['extra']) && is_array($result['extra'])) {
    // $info = array_merge($info, $result['extra']);  Drupal bug?  [extra] array not keyed with 'comment' & 'upload'
    if (!empty($result['extra'][0]) && theme_get_setting('search_info_comment')) {
      $info['comment'] = $result['extra'][0];
    }
    if (!empty($result['extra'][1]) && theme_get_setting('search_info_upload')) {
      $info['upload'] = $result['extra'][1];
    }
  }

  // Provide separated and grouped meta information.
  $vars['info_split'] = $info;
  $vars['info'] = implode(' - ', $info);

  // Provide alternate search result template.
  $vars['template_files'][] = 'search-result-'. $vars['module'];
}


/**
 * Header region override
 * Prints header blocks without region wrappers
 */
function fusion_core_region__header($vars) {
  return $vars['content'];
}


/**
 * File element override
 * Sets form file input max width
 */
function fusion_core_file($vars) {
  $vars['element']['#size'] = ($vars['element']['#size'] > 40) ? 40 : $vars['element']['#size'];
  return theme_file($vars);
}


/**
 * Custom theme functions
 */
function fusion_core_theme() {
  return array(
    'grid_block' => array(
      'variables' => array('content' => NULL, 'id' => NULL),
    ),
  );
}

/**
 * Returns a list of blocks.
 * Uses Drupal block interface and appends any blocks assigned by the Context module.
 */
function fusion_core_block_list($region) {
  $drupal_list = array();
    if (module_exists('context') && $context = context_get_plugin('reaction', 'region')) {
    // Region reaction might be disabling this region. If it 
    // does, an empty array should be returned.
    $dummy_page = array($region => 1);
    $context->execute($dummy_page);
      if (!isset($dummy_page[$region])) {
        return array();
      }
    }
    if (module_exists('block')) {
      $drupal_list = block_list($region);
    }
  if (module_exists('context') && $context = context_get_plugin('reaction', 'block')) {
    $context_list = $context->block_list($region);
    $drupal_list = array_merge($context_list, $drupal_list);
  }
  return $drupal_list;
}

function fusion_core_grid_block($vars) {
  $output = '';
  if ($vars['content']) {
    $id = $vars['id'];
    $output .= '<div id="' . $id . '" class="' . $id . ' block">' . "\n";
    $output .= '<div id="' . $id . '-inner" class="' . $id . '-inner gutter">' . "\n";
    $output .= $vars['content'];
    $output .= '</div><!-- /' . $id . '-inner -->' . "\n";
    $output .= '</div><!-- /' . $id . ' -->' . "\n";
  }
  return $output;
}


/**
 * Generate initial grid info.
 */
function fusion_core_grid_info() {
  global $theme_key;

  $grid = &drupal_static(__FUNCTION__);
  if (isset($grid)) {
    return $grid;
  }

  $grid = array();
  if (theme_get_setting('responsive_enabled') && module_exists('fusion_accelerator')) {
    $grid['type'] = 'responsive';
    $grid['name'] = 'grid' . theme_get_setting('responsive_columns') . '-';
    $grid['fixed'] = TRUE;
    $grid['width'] = theme_get_setting('responsive_columns');
  } else {
    $grid['name'] = substr(theme_get_setting('theme_grid'), 0, 7);
    $grid['type'] = substr(theme_get_setting('theme_grid'), 7);
    $grid['fixed'] = (substr(theme_get_setting('theme_grid'), 7) != 'fluid') ? TRUE : FALSE;
    $grid['width'] = (int)substr($grid['name'], 4, 2);
  }

  // Set sidebar width if this is the block demonstration page.
  global $theme;
  $item = menu_get_item();
  if ($item['path'] == 'admin/structure/block/demo/' . $theme) {
    $grid['sidebar_first_width'] = theme_get_setting('sidebar_first_width');
    $grid['sidebar_second_width'] = theme_get_setting('sidebar_second_width');
  }
  else {  
  $grid['sidebar_first_width'] = (fusion_core_block_list('sidebar_first')) ? theme_get_setting('sidebar_first_width') : 0;
  $grid['sidebar_second_width'] = (fusion_core_block_list('sidebar_second')) ? theme_get_setting('sidebar_second_width') : 0;
  }

  $grid['regions'] = array();
  $regions = array_keys(system_region_list($theme_key, REGIONS_VISIBLE));
  $nested_regions = theme_get_setting('grid_nested_regions');
  $adjusted_regions = theme_get_setting('grid_adjusted_regions');
  foreach ($regions as $region) {
    $region_style = 'full-width';
    $region_width = $grid['width'];
    if ($region == 'sidebar_first' || $region == 'sidebar_second') {
      $region_width = ($region == 'sidebar_first') ? $grid['sidebar_first_width'] : $grid['sidebar_second_width'];
    }
    if ($nested_regions && in_array($region, $nested_regions)) {
      $region_style = 'nested';
      if ($adjusted_regions && in_array($region, array_keys($adjusted_regions))) {
        foreach ($adjusted_regions[$region] as $adjacent_region) {
          $region_width = $region_width - $grid[$adjacent_region . '_width'];
        }
      }
    }
    $grid['regions'][$region] = array('width' => $region_width, 'style' => $region_style, 'total' => count(fusion_core_block_list($region)), 'count' => 0);
  }

  // Adjustments for fluid width regions & groups
  $grid['fluid_adjustments'] = array();
  // Regions
  $adjusted_regions_fluid = (theme_get_setting('grid_adjusted_regions_fluid')) ? theme_get_setting('grid_adjusted_regions_fluid') : array();
  foreach (array_keys($adjusted_regions_fluid) as $adjusted_region) {
    $width = $grid['width'];
    foreach ($adjusted_regions_fluid[$adjusted_region] as $region) {
      $width = $width - $grid['regions'][$region]['width'];         // Subtract regions outside parent group to get correct parent width
    }
    $grid['fluid_adjustments'][$adjusted_region] = round(($grid['regions'][$adjusted_region]['width'] / $width) * 100, 2);
  }
  // Groups
  $adjusted_groups_fluid = (theme_get_setting('grid_adjusted_groups_fluid')) ? theme_get_setting('grid_adjusted_groups_fluid') : array();
  foreach (array_keys($adjusted_groups_fluid) as $adjusted_group) {
    $width = 100;
    foreach ($adjusted_groups_fluid[$adjusted_group] as $region) {
      $width = $width - $grid['fluid_adjustments'][$region];         // Subtract previously calculated sibling region fluid adjustments
    }
    $grid['fluid_adjustments'][$adjusted_group] = $width;            // Group gets remaining width
  }

  return $grid;
}


/**
 * Theme paths function
 * Retrieves parent and current theme paths in parent-to-current order.
 */
function fusion_core_theme_paths($theme) {
  $base_themes = system_find_base_themes(list_themes(), $theme);
  foreach ($base_themes as $key => $base_theme) {
    $base_themes[$key] = drupal_get_path('theme', $key);     // Base theme paths
  }
  $base_themes[$theme] = drupal_get_path('theme', $theme);   // Current theme path
  return $base_themes;
}
