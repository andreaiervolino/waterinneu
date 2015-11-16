<?php
/**
 * @file
 * vocabulary.license.inc
 */

$api = '2.0.0';

$data = (object) array(
  'vid' => '3',
  'name' => 'License',
  'machine_name' => 'license',
  'description' => 'Type of license for a tool or anything that requires a license.',
  'hierarchy' => '0',
  'module' => 'taxonomy',
  'weight' => '0',
  'language' => 'und',
  'i18n_mode' => '0',
  'rdf_mapping' => array(
    'rdftype' => array(
      0 => 'skos:ConceptScheme',
    ),
    'name' => array(
      'predicates' => array(
        0 => 'dc:title',
      ),
    ),
    'description' => array(
      'predicates' => array(
        0 => 'rdfs:comment',
      ),
    ),
  ),
);

$dependencies = array();

$optional = array();

$modules = array(
  0 => 'taxonomy',
);