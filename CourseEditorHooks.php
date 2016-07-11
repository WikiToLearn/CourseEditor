<?php
if ( !defined( 'MEDIAWIKI' ) ){
  die( );
}

class EasyLinkHooks{

  function onParserFirstCallSetup( Parser $parser ) {
    $parser->setHook( 'courseeditor', 'EasyLinkHooks::easyLinkRender' );
    return true;
  }

  static function easyLinkRender( $input, array $args, Parser $parser, PPFrame $frame ) {
    $parser->disableCache();
    $parser->enableOOUI(true);

    $id = $args['id'];

    $title = $args['data-title'];
    $gloss = $args['data-gloss'];
    $glossSource = $args['data-gloss-source'];
    if($args['data-wiki-link'] != 'undefined'){
      $wikiLink =  '<a target="_blank" href="' . $args['data-wiki-link'] . '"><img src="http://image005.flaticon.com/28/png/16/33/33949.png"></a>';
    }
    $babelLink ='<a target="_blank" href="' .  $args['data-babel-link'] . '"><img src="http://babelnet.org/imgs/babelnet.png"></a>';

    /*$output = '<span role="button" class="btn btn-link" data-placement="auto bottom" data-html="true" id="'
    . $id . '" data-toggle="popover" data-trigger="focus" data-title ="<strong>'
    . $title . '</strong>" data-content="<p>'
    . $gloss . '</p><p>' . wfMessage( 'courseeditor-ve-dialog-gloss-source' )->inContentLanguage()
    . $glossSource
    . '</p><p>' . htmlspecialchars($babelLink);
    if($wikiLink){
      $output .= htmlspecialchars($wikiLink) . '">' . $title . '</span>';
    }else{
      $output .= '">' . $title . '</span>';
    }*/

    $output = '<span  id="'
    . $id . '"  data-title="'
    . $title . '" data-gloss="'
    . $gloss . '" data-gloss-source="'
    . $glossSource .'">' . $title . '</span>';
    return $output;
  }
}
