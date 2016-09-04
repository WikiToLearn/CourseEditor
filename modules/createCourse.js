$(function () {
    var delay = (function(){
      var timer = 0;
      return function(callback, ms){
        clearTimeout (timer);
        timer = setTimeout(callback, ms);
      };
    })();

    $('#courseName').keypress(function() {
      delay(function(){
        $('#alert').hide();
        $('#createCourseButton').removeAttr('disabled');
        if($.trim($('#courseName').val()).length !== 0){
          var api = new mw.Api();
          /*api.get({
            action : 'query',
            list : 'prefixsearch',
            pssearch : $('#courseName').val().trim(),
            psnamespace: '2800',
            psprofile: 'classic'
          }*/
          api.get({
            action : 'query',
            titles : 'Course:' + $('#courseName').val().trim()
          }
          ).done( function ( data ) {
            /*var resultsArray = data.query.prefixsearch;
            if (resultsArray.length > 0) {
              for (var i = 0; i < resultsArray.length; i++){
                //Exit when the result is a subpage
                if(resultsArray[i].title.indexOf('/') >= 0) break;
                $('#alert').append('<a class="alert-link" href="/' + resultsArray[i].title + '">' +  resultsArray[i].title + '</a><br>');
              }
              $('#alert').show();
              $('#courseKeywordDiv').show();
            }*/
            var pages = data.query.pages;
            if (!pages['-1']) {
              for (var pageId in pages) {
                $('#coursesList').html('<a class="alert-link" href="/' + pages[pageId].title + '">' + pages[pageId].title + '</a><br>');
              }
              $('#createCourseButton').attr('disabled', true);
              $('#alert').show();
            }
          } );
        }
      }, 500 );
    });

  $('#createCourseButton').click(function(){
    var courseTopic = $('#courseTopic').val().trim();
    var courseName = $('#courseTopic').val().trim();
    var courseNamespace = $('input[name="courseNamespace"]').val();
    $.post( mw.util.wikiScript(), {
      action: 'ajax',
      rs: 'CourseEditorOperations::createCourseOp',
      rsargs: [courseTopic, courseName, courseNamespace]
    }, function ( data ) {});
  });
})
