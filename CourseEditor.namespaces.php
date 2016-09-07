<?php
$namespaceNames = array();

if ( !defined( 'NS_COURSE' ) ) {
	define( 'NS_COURSE', 2800 );
  define ( 'NS_COURSE_TALK', 2801 );
	define( 'NS_COURSEMETADATA', 2900 );
}

// Italian
$namespaceNames['it'] = array(
	NS_COURSE => 'Corso',
	NS_COURSE_TALK => 'Discussione_corso',
  NS_COURSEMETADATA => 'MetadatiCorso',
);
