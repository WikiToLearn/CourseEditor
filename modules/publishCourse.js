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

})
