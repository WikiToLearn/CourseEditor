$(function () {
  if($('#isImported:checked').length > 0){
    $('#courseOriginalAuthorsDiv').show();
  }
  if($('#isReviewed:checked').length > 0){
    $('#courseReviewedOnDiv').show();
  }

  $('#isImported').click(function() {
    $('#courseOriginalAuthorsDiv').toggle();
  });

  $('#isReviewed').click(function() {
    $('#courseReviewedOnDiv').toggle();
  });

  $('#manageMetadataButton').click(function(e){
    e.preventDefault();
    $('#alert').hide();
    var courseName = $('#courseName').val().trim();
    var courseTopic, courseDescription, courseExternalReferences,
      isImported = false, originalAuthors = "", isReviewed = false, reviewedOn = "";
    if($('#courseTopic').val().trim().length !== 0){
      courseTopic = $('#courseTopic').val().trim();
    }
    if($('#courseDescription').val().trim().length !== 0){
      courseDescription = $('#courseDescription').val().trim();
    }
    if($('#courseExternalReferences').val().trim().length !== 0){
      courseExternalReferences = $('#courseExternalReferences').val().trim();
    }
    if($('#isImported:checked').length > 0){
      isImported = true;
      originalAuthors = $('#courseOriginalAuthors').val().trim();
    }
    if($('#isReviewed:checked').length > 0){
      isReviewed = true;
      reviewedOn =  $('#courseReviewedOn').val().trim();
    }
    operationRequested = {
      type : 'saveMetadata',
      params : [
        courseName,
        courseTopic,
        courseDescription,
        courseExternalReferences,
        isImported,
        originalAuthors,
        isReviewed,
        reviewedOn
      ]
    };

    $.post( mw.util.wikiScript(), {
      action: 'ajax',
      rs: 'CourseEditorOperations::manageCourseMetadataOp',
      rsargs: [JSON.stringify(operationRequested)]
    }, function ( data ) {
      var dataObj = JSON.parse(data);
      if(dataObj.success !== true){
        $('#alert').show();
      }else {
        /*FIXME: courseTopic is valid only if the course is public, otherwise user should
        * be redirected to userpage
        */
        window.location.assign('/' +  courseTopic);
      }
    });
  });
})
