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
    <p><?php echo wfMessage( 'courseeditor-organize-chapters' ); ?></p>
    <h2 id="sectionName"><?php echo htmlspecialchars($section) ?></h2>
    <div class="col-md-8">
      <div id="chaptersList"></div>
      <div id="saveDiv"></div>
    </div>
    <div class="col-md-4">
      <div id="undoStack">
        <div class="panel panel-default">
  <!-- Default panel contents -->
  <div class="panel-heading"><i class="fa fa-recycle" aria-hidden="true"></i>&nbsp;&nbsp;<?php echo wfMessage( 'courseeditor-recycle-bin' ); ?></div>
  <!-- List group -->
  <div class="list-group">
  </div>
</div>
      </div>
    </div>
    <?php
  }
}

/**
* HTML template for Special:CourseEditor
* @ingroup Templates
*/
class CourseEditorTemplate extends QuickTemplate {
  public function execute() {
    $courseName = $this->data['course'];
    ?>
    <p><?php echo wfMessage( 'courseeditor-organize-sections' ); ?></p>
    <h2 id="courseName"><?php echo htmlspecialchars($courseName) ?></h2>
    <div class="col-md-8">
      <div id="sectionsList"></div>
      <div id="saveDiv"></div>
    </div>
    <div class="col-md-4">
      <div id="undoStack">
        <div class="panel panel-default">
  <!-- Default panel contents -->
  <div class="panel-heading"><i class="fa fa-recycle" aria-hidden="true"></i>&nbsp;&nbsp;<?php echo wfMessage( 'courseeditor-recycle-bin' ); ?></div>
  <!-- List group -->
  <div class="list-group">
  </div>
</div>
      </div>
    </div>
    <?php
  }
}
