/**
* This script handle the asynchronous operations related to course publication
* process.
* In particular perform the AJAX POST to publish the course.
*/
$(function(){
  $('#publishCourseButton').click(function(){
    var operationRequested = {
      type: 'publishCourse',
      courseName: mw.config.values.wgPageName
    };
    /**
    * Perform a POST on the CourseEditorOperations::createCourseOp.
    * If an error occurs an alert is showed, otherwise the page is reloaded.
    */
    $.post( mw.util.wikiScript(), {
      action: 'ajax',
      rs: 'CourseEditorOperations::publishCourseOp',
      rsargs: [JSON.stringify(operationRequested)]
    }, function ( result ) {
      var resultObj = JSON.parse(result);
      if(resultObj.success !== true){
        $('#alertError').show();
      }else {
        window.location.assign('/' +  mw.config.values.wgPageName);
      }
    });
  });

  $('#undoPublishCourseButton').click(function(){
    var operationRequested = {
      type: 'undoPublishCourse',
      courseName: mw.config.values.wgPageName
    };
    /**
    * Perform a POST on the CourseEditorOperations::createCourseOp.
    * If an error occurs an alert is showed, otherwise the page is reloaded.
    */
    $.post( mw.util.wikiScript(), {
      action: 'ajax',
      rs: 'CourseEditorOperations::undoPublishCourseOp',
      rsargs: [JSON.stringify(operationRequested)]
    }, function ( result ) {
      var resultObj = JSON.parse(result);
      if(resultObj.success !== true){
        $('#alertError').show();
      }else {
        window.location.assign('/' +  mw.config.values.wgPageName);
      }
    });
  });

  $('#confirmPublishCourseButton').click(function(){

    var editStack = [];
    //var courseName = $('input[name=readyCourses]:checked').val();
    var courseName = 'Utente:Admin/Yollo';
    var levelsTwoList,
    courseNameClean = courseName.split('/')[1],
    user = courseName.split('/')[0].split(':')[1];

    $.getJSON( mw.util.wikiScript(), {
      action: 'ajax',
      rs: 'CourseEditorUtils::getLevelsTwoJson',
      rsargs: [courseName]
    }).success(function(result){
      levelsTwoList = result;
    }).then(function(){
      var courseNamespace = mw.config.get( 'wgCourseEditor' ).Course;
      var metadataNamespace = mw.config.get( 'wgCourseEditor' ).CourseMetadata;
      var courseNameInPublic = courseNamespace + ':' + courseNameClean;
      var metadataPagePublic = metadataNamespace + ':' + user + '/' + courseNameClean;

      $.each(levelsTwoList, function(key, value){
        editStack.push({
          action: 'rename',
          elementName: courseName + '/' + value,
          newElementName: courseNameInPublic + '/' + value
        });
      });
      editStack.push({
        action: 'move-root',
        elementName: courseName,
        newElementName: courseNameInPublic
      });
      editStack.push({
        action: 'remove-ready-texts',
        elementName: courseNameInPublic
      });
      editStack.push({
        action: 'move-metadata',
        elementName: metadataNamespace + ':' + user + '/' + courseNameClean,
        newElementName: metadataPagePublic
      });
      editStack.push({
        action: 'update-collection',
        elementName: courseNameInPublic
      });

      var progressDialog = new ProgressDialog( {
        size: 'medium'
      } );
      var unitaryIncrement = 100/editStack.length;

      windowManager.addWindows( [ progressDialog ] );
      windowManager.openWindow( progressDialog );

      var createTask = function(operation){
        return function(next){
          doTask(operation, next);
        }
      };

      var doTask = function(operation, next){
        progressDialog.setCurrentOp(operation);
        $.getJSON( mw.util.wikiScript(), {
          action: 'ajax',
          rs: 'CourseEditorOperations::applyPublishCourseOp',
          rsargs: [JSON.stringify(operation)]
        }, function ( data ) {
            console.log(data);
            progressDialog.updateProgress(unitaryIncrement);
            next();
        });
      };

      while( editStack.length > 0 ) {
        var operation =  editStack.shift();
        var microOps = createMicroOperations(operation);
        for (var i = 0; i < microOps.length; i++) {
          $(document).queue('tasks', createTask(microOps[i]));
        }
      };

      $(document).queue('tasks', function(){
        windowManager.closeWindow(progressDialog);

      });

      dequeue('tasks')

    });
  });
})
