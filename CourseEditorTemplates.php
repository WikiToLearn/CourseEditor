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

class CourseCreatorTemplate extends QuickTemplate {
  public function execute(){
    $topic = $this->data['topic'];
    ?>
    <form>
      <div class="form-group">
        <label for="courseTopic"><?php echo wfMessage( 'courseeditor-input-topic-label' ) ?></label>
        <input type="text" class="form-control" id="courseTopic" disabled="true" value="<?php echo $topic ?>">
      </div>
      <div class="form-group">
        <label for="courseName"><?php echo wfMessage( 'courseeditor-input-course-label' ) ?></label>
        <input type="text" class="form-control" id="courseName" placeholder="<?php echo wfMessage( 'courseeditor-input-course-placeholder' ) ?>" required>
      </div>
    <div class="alert alert-warning alert-dismissible" id="alert" role="alert"  style="display:none;">
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
      <?php echo wfMessage( 'courseeditor-alert-message' ) ?>
      <div id="coursesList"></div>
    </div>
      <div class="form-group">
        <label for="courseDescription"><?php echo wfMessage( 'courseeditor-input-description-label' ) ?></label>
        <input type="text" class="form-control" id="courseDescription" placeholder="<?php echo wfMessage( 'courseeditor-input-description-placeholder' ) ?>">
      </div>
      <label for="courseNamespace"><?php echo wfMessage('courseeditor-radiobutton-namespace') ?></label>
      <div class="radio" id="radioButtons">
        <label>
          <input type="radio" name="courseNamespace" value="NS_COURSE" checked>
          <?php echo wfMessage('courseeditor-radiobutton-namespace-public') ?>
        </label>
      </div>
      <div class="radio">
        <label>
          <input type="radio" name="courseNamespace" value="NS_USER">
          <?php echo wfMessage('courseeditor-radiobutton-namespace-private') ?>
        </label>
      </div>
      <button class="btn btn-primary btn-lg" id="createCourseButton">Submit</button>
    </form>
<?php
  }
}
