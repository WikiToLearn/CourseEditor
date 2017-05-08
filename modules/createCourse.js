/**
* This script handle the asynchronous operations related to course creation
* process.
* In particular checks courses with a similar or the same title and perform
* the AJAX POST to create the course.
*/
$(function(){

  /**
  * This function set a delay before to execute other functions within its
  * callback.
  */
  var delay = (function(){
    var timer = 0;
    return function(callback, ms){
      clearTimeout (timer);
      timer = setTimeout(callback, ms);
    };
  })();

  /**
  * Handler on keypress binded on '#courseName' input text.
  * If the user is creating a course from a topic page it checks courses with
  * the same name. Otherwise, if the user is creating a course from a department
  * page it checks if there are courses with a similar name.
  */
  $('#courseName').keypress(function() {
    delay(function(){
      //Hide alerts and enable create button.
      $('#alertSame').hide();
      $('#alertSimilar').hide();
      $('#createCourseButton').removeAttr('disabled');
      var courseNameTrimmed = $.trim($('#courseName').val());
      if(isBadElementName(courseNameTrimmed)){
        $('#createCourseButton').attr('disabled', true);
        return;
      }
      if(courseNameTrimmed.length !== 0){
        var api = new mw.Api();
        var courseNamespace = $('input[name="courseNamespace"]:checked').val();
        // In the course is private, check for similar titles in the user NS
        if(courseNamespace === 'NS_USER'){
          api.get({
            action : 'query',
            list : 'prefixsearch',
            pssearch : mw.user.getName() + '/' + $('#courseName').val().trim(),
            psnamespace: '2'
          }).done( function ( data ) {
            $('#coursesListSimilar').html('');
            var resultsArray = data.query.prefixsearch;
            if (resultsArray.length > 0) {
              for (var i = 0; i < resultsArray.length; i++){
                var resultTitle = resultsArray[i].title;
                //Exit when the result is a subpage of the private course
                if(resultTitle.split("/").length - 1 > 1) break;
                //Generate similar courses list
                $('#coursesListSimilar').append(
                  '<a class="alert-link" href="/'
                  + resultTitle
                  + '">'
                  +  resultTitle
                  + '</a><br>'
                );
                //Check if the name of the course is the same and disable button
                var courseNameNoNamespace = resultTitle.substring(resultTitle.indexOf(':') + 1, resultTitle.length);
                if($('#courseName').val().trim().toUpperCase() === courseNameNoNamespace.toUpperCase()){
                  $('#createCourseButton').attr('disabled', true);
                }
              }
              $('#alertSimilar').show();
            }
          });
        }
        //Check if there's the topic or the department
        else if($('#courseTopic').length !== 0){
          api.get({
            action : 'query',
            titles : 'Course:' + $('#courseName').val().trim()
          }
        ).done( function ( data ) {
            var pages = data.query.pages;
            if (!pages['-1']) {
              for (var pageId in pages) {
                if (pages.hasOwnProperty(pageId)) {
                  $('#coursesListSame').html('<a class="alert-link" href="/'
                    + pages[pageId].title
                    + '">'
                    + pages[pageId].title + '</a><br>'
                  );
                }
              }
              //Disable create button and show alert
              $('#createCourseButton').attr('disabled', true);
              $('#alertSame').show();
            }
          } );
        }else {
          api.get({
            action : 'query',
            list : 'prefixsearch',
            pssearch : $('#courseName').val().trim(),
            psnamespace: '2800',
            psprofile: 'classic'
          }).done( function ( data ) {
            $('#coursesListSimilar').html('');
            var resultsArray = data.query.prefixsearch;
            if (resultsArray.length > 0) {
              for (var i = 0; i < resultsArray.length; i++){
                //Exit when the result is a subpage
                var resultTitle = resultsArray[i].title;
                if(resultTitle.split("/").length - 1 > 0) break;
                //Generate similar courses list
                $('#coursesListSimilar').append(
                  '<a class="alert-link" href="/'
                  + resultTitle
                  + '">'
                  +  resultTitle
                  + '</a><br>'
                );
                //Check if the name of the course is the same and disable button
                var courseNameNoNamespace = resultTitle.substring(resultTitle.indexOf(':') + 1, resultTitle.length);
                if($('#courseName').val().trim().toUpperCase() === courseNameNoNamespace.toUpperCase()){
                  $('#createCourseButton').attr('disabled', true);
                }
              }
              $('#alertSimilar').show();
            }
          });
        }
      }
    }, 500 );
  });

  /**
  * Click handler binded on '#createCourseButton'.
  * This function build the params for the POST call and send it.
  * In the meanwhile show a progress dialog.
  */
  $('#createCourseButton').click(function(e){
    e.preventDefault();
    var courseName = $('#courseName').val().trim();
    if(isBadElementName(courseName)){
      $('#createCourseButton').attr('disabled', true);
      return;
    }

    var courseDescription = $('#courseDescription').val().trim();
    var courseNamespace = $('input[name="courseNamespace"]:checked').val();
    var courseTopic, courseDepartment, operationRequested;
    if($('#courseTopic').length !== 0){
      courseTopic = $('#courseTopic').val().trim();
      operationRequested = {
        type : 'fromTopic',
        params : [
          courseTopic,
          courseName,
          courseDescription,
          courseNamespace
        ]
      };
    }else{
      courseDepartment = $('#courseDepartment').val().trim();
      operationRequested = {
        type : 'fromDepartment',
        params : [
          courseDepartment,
          courseName,
          courseDescription,
          courseNamespace
        ]
      };
    }

    //Create and open the progress dialog
    var progressDialog = new ProgressDialogIndeterminate( {
      size: 'medium'
    } );

    windowManager.addWindows( [ progressDialog ] );
    windowManager.openWindow( progressDialog );

    /**
    * Perform a POST on the CourseEditorOperations::createCourseOp.
    * If no errors occur the user is redirected to the course page.
    */
    $.post( mw.util.wikiScript(), {
      action: 'ajax',
      rs: 'CourseEditorOperations::createCourseOp',
      rsargs: [JSON.stringify(operationRequested)]
    }, function ( result ) {
      var resultObj = JSON.parse(result);
      if(resultObj.success !== true){
        windowManager.closeWindow(progressDialog);
        $('#alertError').show();
      }else {
        windowManager.closeWindow(progressDialog);
        window.location.assign('/' +  resultObj.courseTitle);
      }
    });
  });
})
