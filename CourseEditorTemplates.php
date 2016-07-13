<?php
/**
* @defgroup Templates Templates
* @file
* @ingroup Templates
*/
if ( !defined( 'MEDIAWIKI' ) ) {
  die( -1 );
}

/**
* HTML template for Special:CourseEditor
* @ingroup Templates
*/
class SectionEditorTemplate extends QuickTemplate {
  public function execute() {
    $chaptersList = $this->data['chapters'];
    $section = $this->data['section'];
    ?>
    <h2><?php echo htmlspecialchars($section) ?></h2>
    <div id="chaptersList">
    </div>
    <?php
  }
}
