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
    var courseName = $('input[name=readyCourses]:checked').val();
    var courseTree,
    courseNameClean = courseName.split('/')[1],
    user = courseName.split('/')[0].split(':')[1];

    var courseNamespace = mw.config.get( 'wgCourseEditor' ).Course;
    var metadataNamespace = mw.config.get( 'wgCourseEditor' ).CourseMetadata;
    var courseNameInPublic = courseNamespace + ':' + courseNameClean;
    var metadataPagePublic = metadataNamespace + ':' + courseNameClean;

    $.getJSON( mw.util.wikiScript(), {
      action: 'ajax',
      rs: 'CourseEditorUtils::getCourseTree',
      rsargs: [courseName]
    }).success(function(result){
      courseTree = result;
    }).then(function(){
      $.each(courseTree.levelsThree, function(index, array){
        $.each(array, function(key, value){
          editStack.push({
            action: 'rename-move-task',
            elementName: courseTree.root + '/' + courseTree.levelsTwo[index] + '/' + value,
            newElementName: courseNameInPublic + '/' + courseTree.levelsTwo[index] + '/' + value
          });
        });
        editStack.push({
          action: 'rename-move-task',
          elementName: courseTree.root + '/' + courseTree.levelsTwo[index],
          newElementName: courseNameInPublic + '/' + courseTree.levelsTwo[index]
        });
        editStack.push({
          action: 'rename-update-task',
          elementName: courseNameInPublic + '/' + courseTree.levelsTwo[index]
        });
      });
      editStack.push({
        action: 'remove-ready-texts',
        elementName: courseName
      });
      editStack.push({
        action: 'move-root',
        elementName: courseTree.root,
        newElementName: courseNameInPublic
      });
      editStack.push({
        action: 'move-metadata',
        elementName: metadataNamespace + ':' + user + '/' + courseNameClean,
        newElementName: metadataPagePublic
      });
      editStack.push({
        action: 'purge',
        elementName: courseNameInPublic
      });
      editStack.push({
        action: 'update-collection',
        elementName: courseNameInPublic
      });

      // Create task for the jQuery queue
      var createTask = function(operation){
        return function(next){
          doTask(operation, next);
        }
      };

      // Execute the task of the queue
      var doTask = function(operation, next){
        progressDialog.setCurrentOp(operation);
        $.getJSON( mw.util.wikiScript(), {
          action: 'ajax',
          rs: 'CourseEditorOperations::applyPublishCourseOp',
          rsargs: [JSON.stringify(operation)]
        }, function ( data ) {
          // If errors occurs show an alert and clear the queue
          if (data.success !== true) {
            $('#alert').html(OO.ui.msg('courseeditor-error-operation'));
            $('#alert').append(OO.ui.msg('courseeditor-operation-action-' + data.action));
            if(data.elementName){
              var localizedMsg = " " + data.elementName + OO.ui.msg('courseeditor-error-operation-fail');
              $('#alert').append(localizedMsg);
            }else {
              $('#alert').append(OO.ui.msg('courseeditor-error-operation-fail'));
            }
            $('#alert').show();
            windowManager.closeWindow(progressDialog);
            $(document).clearQueue('tasks');
          }else{
            // Otherwise update the progress and execute the next task
            progressDialog.updateProgress(unitaryIncrement);
            next();
          }
        });
      };

      // Create tasks form editStack
      $.each(editStack, function(key, value){
        $(document).queue('tasks', createTask(value));
      });

      $(document).queue('tasks', function(){
        windowManager.closeWindow(progressDialog);
        $('#publicCourseLink').html('<a href="/' + courseNameInPublic + '">' + courseNameInPublic +'</a>');
        $('#alertSuccess').show();
      });


      // Create and open the progressDialog
      var progressDialog = new ProgressDialog( {
        size: 'medium'
      } );
      var unitaryIncrement = 100/editStack.length;

      windowManager.addWindows( [ progressDialog ] );
      windowManager.openWindow( progressDialog );

      // Start to execute the queue
      dequeue('tasks');
    });
  });
})
