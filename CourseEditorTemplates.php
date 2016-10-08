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
class LevelTwoEditorTemplate extends QuickTemplate {
  public function execute() {
    $levelTwo = $this->data['levelTwo'];
    ?>
    <p><?php echo wfMessage( 'courseeditor-organize-levelsThree' ); ?></p>
    <h2 id="parentName"><?php echo htmlspecialchars($levelTwo) ?></h2>
    <div class="row">
      <div class="col-md-8">
        <div id="levelsThreeList"></div>
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
    </div>
    <br>
    <br>
    <div id="saveDiv" class="text-center">
      <div class="alert alert-warning" id="alertInputNotEmpty" role="alert"  style="display:none;">
        <?php echo wfMessage('courseeditor-alert-message-input-notempty') ?>
      </div>
      <div class="alert alert-danger" id="alert" role="alert"  style="display:none;">
        <?php echo wfMessage('courseeditor-alert-message-existing-element') ?>
      </div>
      <br><br>
      <button type="button" class="btn btn-lg btn-success" id="saveLevelTwoButton"><?php echo wfMessage('courseeditor-save-levelTwo') ?></button>
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
    <p><?php echo wfMessage( 'courseeditor-organize-levelsTwo' ); ?></p>
    <h2 id="parentName"><?php echo htmlspecialchars($courseName) ?></h2>
    <div class="row">
      <div class="col-md-8">
        <div id="levelsTwoList"></div>
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
    </div>
    <br>
    <br>
    <div id="saveDiv" class="text-center">
      <div class="alert alert-warning" id="alertInputNotEmpty" role="alert"  style="display:none;">
        <?php echo wfMessage('courseeditor-alert-message-input-notempty') ?>
      </div>
      <div class="alert alert-danger" id="alert" role="alert"  style="display:none;">
        <?php echo wfMessage('courseeditor-alert-message-existing-element') ?>
      </div>
      <br><br>
      <button type="button" class="btn btn-lg btn-success" id="saveCourseButton"><?php echo wfMessage('courseeditor-save-course') ?></button>
    </div>
    <?php
  }
}

class CourseCreatorTemplate extends QuickTemplate {
  public function execute(){
    ?>
    <form>
      <?php
    if($this->data['topic']){
        $topic = $this->data['topic'];
      ?>
      <div class="form-group">
        <label for="courseTopic"><?php echo wfMessage( 'courseeditor-input-topic-label' ) ?></label>
        <input type="text" class="form-control" id="courseTopic" disabled="true" value="<?php echo $topic ?>">
      </div>
    <?php
  }else if($this->data['department']){
    $department = $this->data['department'];
    ?>
    <div class="form-group">
      <label for="courseDepartment"><?php echo wfMessage( 'courseeditor-input-department-label' ) ?></label>
      <input type="text" class="form-control" id="courseDepartment" disabled="true" value="<?php echo $department ?>">
    </div>
    <?php
  }
    ?>
      <div class="form-group">
        <label for="courseName"><?php echo wfMessage( 'courseeditor-input-course-label' ) ?></label>
        <input type="text" class="form-control" id="courseName" placeholder="<?php echo wfMessage( 'courseeditor-input-course-placeholder' ) ?>" required>
      </div>
    <div class="alert alert-warning alert-dismissible" id="alertSame" role="alert"  style="display:none;">
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
      <?php echo wfMessage( 'courseeditor-alert-same-title-message' ) ?>
      <div id="coursesListSame"></div>
    </div>
    <div class="alert alert-warning alert-dismissible" id="alertSimilar" role="alert"  style="display:none;">
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
      <?php echo wfMessage( 'courseeditor-alert-similar-title-message' ) ?>
      <div id="coursesListSimilar"></div>
    </div>
      <div class="form-group">
        <label for="courseDescription"><?php echo wfMessage( 'courseeditor-input-description-label' ) ?></label>
        <input type="text" class="form-control" id="courseDescription" placeholder="<?php echo wfMessage( 'courseeditor-input-description-placeholder' ) ?>">
      </div>
      <label for="courseNamespace"><?php echo wfMessage('courseeditor-radiobutton-namespace') ?></label>
      <div class="radio">
        <label>
          <input type="radio" name="courseNamespace" value="NS_USER" checked>
          <?php echo wfMessage('courseeditor-radiobutton-namespace-private') ?>
        </label>
      </div>
      <div class="radio" id="radioButtons">
        <label>
          <input type="radio" name="courseNamespace" value="NS_COURSE">
          <?php echo wfMessage('courseeditor-radiobutton-namespace-public') ?>
        </label>
      </div>
      <div class="alert alert-danger alert-dismissible" id="alertError" role="alert"  style="display:none;">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <?php echo wfMessage( 'courseeditor-error-operation' ) ?>
      </div>
      <br><br>
      <button class="btn btn-success btn-lg" id="createCourseButton"><?php echo wfMessage('courseeditor-create-button') ?></button>
    </form>
<?php
  }
}

class ManageMetadataTemplate extends QuickTemplate {
  public function execute(){
    $courseName = $this->data['course'];
    $user = $this->data['user'];
    if($this->data['metadataResult']){
      $metadataResult = $this->data['metadataResult'];
    }
    ?>
    <div><p><?php echo wfMessage( 'courseeditor-managemetata-description' ) ?></p></div>
    <br>
    <form>
      <div class="form-group">
        <label for="courseName"><?php echo wfMessage( 'courseeditor-input-course-label' ) ?></label>
        <input type="text" class="form-control" id="courseName" disabled="true" value="<?php echo $courseName ?>">
      </div>
      <div class="form-group">
        <label for="courseTopic"><?php echo wfMessage( 'courseeditor-input-topic-label' ) ?></label>
        <input type="text" class="form-control" id="courseTopic" value="<?php echo $metadataResult['topic'] ?>" placeholder="<?php echo wfMessage( 'courseeditor-input-topic-placeholder' ) ?>">
      </div>
      <div class="form-group">
        <label for="courseDescription"><?php echo wfMessage( 'courseeditor-input-description-label' ) ?></label>
        <textarea row="3" class="form-control" id="courseDescription" placeholder="<?php echo wfMessage( 'courseeditor-input-description-placeholder' ) ?>"><?php echo $metadataResult['description'] ?></textarea>
      </div>
      <div class="form-group">
        <label for="courseBibliography"><?php echo wfMessage( 'courseeditor-input-bibliography-label' ) ?></label>
        <textarea class="form-control" rows="3" id="courseBibliography" placeholder="<?php echo wfMessage( 'courseeditor-input-bibliography-placeholder' ) ?>"><?php echo $metadataResult['bibliography'] ?></textarea>
      </div>
      <div class="form-group">
        <label for="courseExercises"><?php echo wfMessage( 'courseeditor-input-exercises-label' ) ?></label>
        <textarea class="form-control" rows="3" id="courseExercises" placeholder="<?php echo wfMessage( 'courseeditor-input-exercises-placeholder' ) ?>"><?php echo $metadataResult['exercises'] ?></textarea>
      </div>
      <div class="form-group">
        <label for="courseBooks"><?php echo wfMessage( 'courseeditor-input-books-label' ) ?></label>
        <textarea class="form-control" rows="3" id="courseBooks" placeholder="<?php echo wfMessage( 'courseeditor-input-books-placeholder' ) ?>"><?php echo $metadataResult['books'] ?></textarea>
      </div>
      <div class="form-group">
        <label for="courseExternalReferences"><?php echo wfMessage( 'courseeditor-input-externalreferences-label' ) ?></label>
        <textarea class="form-control" rows="3" id="courseExternalReferences" placeholder="<?php echo wfMessage( 'courseeditor-input-externalreferences-placeholder' ) ?>"><?php echo $metadataResult['externalreferences'] ?></textarea>
      </div>
      <?php if ($user->isAllowed( 'undelete' )) { ?>
        <div class="checkbox">
          <label for="importedCourse">
            <?php if(array_key_exists('isimported', $metadataResult)){ ?>
            <input type="checkbox" name="isImported" id="isImported" checked="true">
            <?php }else {
              ?>
              <input type="checkbox" name="isImported" id="isImported">
            <?php } echo wfMessage( 'courseeditor-input-imported-label' ) ?>
          </label>
        </div>
        <div class="form-group" id="courseOriginalAuthorsDiv" style="display:none;">
          <label for="courseOriginalAuthors"><?php echo wfMessage( 'courseeditor-input-originalauthors-label' ) ?></label>
          <input type="text" class="form-control" id="courseOriginalAuthors" placeholder="<?php echo wfMessage( 'courseeditor-input-originalauthors-placeholder' ) ?>"  value="<?php echo $metadataResult['originalauthors'] ?>" />
        </div>
        <div class="checkbox">
          <label for="reviewedCourse">
            <?php if(array_key_exists('isreviewed', $metadataResult)){ ?>
            <input type="checkbox"  name="isReviewed" id="isReviewed" checked="true">
            <?php }else {
              ?>
              <input type="checkbox" name="isReviewed" id="isReviewed">
            <?php } echo wfMessage( 'courseeditor-input-reviewed-label' ) ?>
          </label>
        </div>
        <div class="form-group" id="courseReviewedOnDiv" style="display:none;">
          <label for="courseReviewedOn"><?php echo wfMessage( 'courseeditor-input-reviewedon-label' ) ?></label>
          <input type="text" class="form-control" id="courseReviewedOn" placeholder="<?php echo wfMessage( 'courseeditor-input-reviewedon-placeholder' ) ?>"  value="<?php echo $metadataResult['reviewedon'] ?>" />
        </div>
      <?php } ?>
      <button class="btn btn-success btn-lg" id="manageMetadataButton"><?php echo wfMessage('courseeditor-save-button') ?></button>
      <br><br>
      <div class="alert alert-danger alert-dismissible" id="alert" role="alert"  style="display:none;">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <?php echo wfMessage( 'courseeditor-error-operation' ) ?>
      </div>
    </form>
<?php
  }
}
