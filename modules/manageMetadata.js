$(function () {

  $('#courseTopic').change(function(){
    if($(this).val() === 'New'){
      // Make a subclass of ProcessDialog
      function PromptDialog( config ) {
        PromptDialog.super.call( this, config );
      }
      OO.inheritClass( PromptDialog, OO.ui.ProcessDialog );

      PromptDialog.static.name = 'promptDialog';
      // Specify the static configurations: title and action set
      PromptDialog.static.title = 'Crea un nuovo topic';
      PromptDialog.static.actions = [
        { flags: 'primary', label: 'Aggiungi', action: 'add' },
        { flags: 'safe', label: 'Annulla' }
      ];

      // Customize the initialize() function to add content and layouts
      PromptDialog.prototype.initialize = function () {
        PromptDialog.super.prototype.initialize.call( this );
        this.panel = new OO.ui.PanelLayout( { padded: true, expanded: false } );
        this.content = new OO.ui.FieldsetLayout();

        this.topicInput = new OO.ui.TextInputWidget();

        this.field = new OO.ui.FieldLayout( this.topicInput, { label: 'Inserisci il nome di un nuovo topic', align: 'top' } );

        this.content.addItems([ this.field ]);
        this.panel.$element.append( this.content.$element );
        this.$body.append( this.panel.$element );

        this.topicInput.connect( this, { 'change': 'onTopicInputChange' } );
      };

      // Specify any additional functionality required by the window (disable opening an empty topic, in this case)
      PromptDialog.prototype.onTopicInputChange = function ( value ) {
        this.actions.setAbilities( {
          add: !!value.length
        } );
      };

      // Specify processes to handle the actions.
      PromptDialog.prototype.getActionProcess = function ( action ) {
        if ( action === 'add' ) {
          // Create a new process to handle the action
          return new OO.ui.Process( function () {
            var newTopic = this.topicInput.getValue().trim();
            if (newTopic.length !== 0) {
              $('#courseTopic').append(new Option(newTopic, newTopic, true, true));
              this.close();
            }
          }, this );
        }
        // Fallback to parent handler
        return PromptDialog.super.prototype.getActionProcess.call( this, action );
      };

      // Create a new process dialog window
      var promptDialog = new PromptDialog();

      // Add the dialog to the windowManager and open it
      windowManager.addWindows( [ promptDialog ] );
      windowManager.openWindow( promptDialog);
    }
  });

  // Store the courseName before changes
  var originalCourseName = $('#courseName').val().trim();

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
    if(courseName.length !== 0 && courseName !== originalCourseName){
      renameAndUpdateMetadata(courseName, originalCourseName);
    }else if(courseName.length !== 0){
      updateMetadata(courseName);
    }else {
      // Create and open an alert dialog
      OO.ui.alert(OO.ui.msg('courseeditor-alert-dialog-message'));
      /*var alertDialog = new OO.ui.MessageDialog();
      windowManager.addWindows( [ alertDialog ] );
      windowManager.openWindow( alertDialog, {
        title: OO.ui.msg('courseeditor-alert-dialog-title'),
        message: OO.ui.msg('courseeditor-alert-dialog-message'),
        actions: [
          {
            action: 'accept',
            label: OO.ui.msg('courseeditor-alert-dialog-button'),
            flags: 'primary'
          }
        ]
      });*/
    }
  });


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
      //Hide alert and enable create button
      $('#alertSame').hide();
      $('#manageMetadataButton').removeAttr('disabled');

      if($.trim($('#courseName').val()).length !== 0){
        var api = new mw.Api();
        //Check if there's the topic or the department
        if($('#courseTopic').length !== 0){
          // Build title to search checking if the course is private or not
          var title = ($('#private').val() == 1) ? ('User:' + $('#username').val() + '/' + $('#courseName').val().trim()) : ('Course:' + $('#courseName').val().trim());
          api.get({
            action : 'query',
            titles :  title
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
              $('#manageMetadataButton').attr('disabled', true);
              $('#alertSame').show();
            }
          } );
        }
      }
    }, 500 );
  });
});

var updateMetadata = function(courseName){
  var courseTopic, courseDescription, courseBibliography, courseExercises, courseBooks, courseExternalReferences,
    isImported = false, originalAuthors = "", isReviewed = false, reviewedOn = "";
  // Concatenate username to courseName if the course is private
  courseName = ($('#private').val() == 1) ? ($('#username').val() + '/' + courseName) : courseName;

  // Set metadata vars
  if($('#courseTopic').val().trim().length !== 0){
    courseTopic = $('#courseTopic').val().trim();
  }
  if($('#courseDescription').val().trim().length !== 0){
    courseDescription = $('#courseDescription').val().trim();
  }
  if($('#courseBibliography').val().trim().length !== 0){
    courseBibliography = $('#courseBibliography').val().trim();
  }
  if($('#courseExercises').val().trim().length !== 0){
    courseExercises = $('#courseExercises').val().trim();
  }
  if($('#courseBooks').val().trim().length !== 0){
    courseBooks = $('#courseBooks').val().trim();
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
      courseBibliography,
      courseExercises,
      courseBooks,
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
    // If an error occurs show the alert message
    if(dataObj.success !== true){
      $('#alert').show();
    }else {
      // Otherwise build the redirect url
      var splitted = courseName.split('/');
      var redirect;
      if(splitted.length > 1){
        redirect = 'User:' + splitted[0] + '/' + splitted[1];
      }else {
        redirect = 'Course:' + splitted[0];
      }
      window.location.assign('/' + redirect);
    }
  });
};

var renameAndUpdateMetadata = function(courseName, originalCourseName){
  // Create and open a confirmation dialog window
  OO.ui.confirm( OO.ui.msg('courseeditor-confirmation-dialog-message') ).done( function ( confirmed ) {
    if ( confirmed ) {
      // User confirmed, so we can proceed
      var editStack = [];
      var courseTree, newMetadataPage, originalMetadataPage;
      var courseNamespace = mw.config.get( 'wgCourseEditor' ).Course;
      var metadataNamespace = mw.config.get( 'wgCourseEditor' ).CourseMetadata;

      // Build vars using appropriate namespace
      if($('#private').val() == 1){
        newMetadataPage = metadataNamespace + ':' + $('#username').val() + '/' + courseName;
        originalMetadataPage = metadataNamespace + ':' + $('#username').val() + '/' + originalCourseName;
        originalCourseNameWithNamespace = 'User' + ':' + $('#username').val() + '/' + originalCourseName;
        courseNameWithNamespace = 'User' + ':' + $('#username').val() + '/' + courseName;

      }else {
        newMetadataPage = metadataNamespace + ':' + courseName;
        originalMetadataPage = courseNamespace + ':' + originalCourseName;
        originalCourseNameWithNamespace = courseNamespace + ':' + originalCourseName;
        courseNameWithNamespace = courseNamespace + ':' + courseName;
      }

      $.getJSON( mw.util.wikiScript(), {
        action: 'ajax',
        rs: 'CourseEditorUtils::getCourseTree',
        rsargs: [originalCourseNameWithNamespace]
      }).success(function(result){
        courseTree = result;
      }).then(function(){
        $.each(courseTree.levelsThree, function(index, array){
          $.each(array, function(key, value){
            editStack.push({
              action: 'rename-move-task',
              elementName: courseTree.root + '/' + courseTree.levelsTwo[index] + '/' + value,
              newElementName: courseNameWithNamespace + '/' + courseTree.levelsTwo[index] + '/' + value
            });
          });
          editStack.push({
            action: 'rename-move-task',
            elementName: courseTree.root + '/' + courseTree.levelsTwo[index],
            newElementName: courseNameWithNamespace + '/' + courseTree.levelsTwo[index]
          });
          editStack.push({
            action: 'rename-update-task',
            elementName: courseNameWithNamespace + '/' + courseTree.levelsTwo[index]
          });
        });
        editStack.push({
          action: 'move-root',
          elementName: courseTree.root,
          newElementName: courseNameWithNamespace
        });
        editStack.push({
          action: 'move-metadata',
          elementName: originalMetadataPage,
          newElementName: newMetadataPage
        });
        editStack.push({
          action: 'purge',
          elementName: courseNameWithNamespace
        });
        editStack.push({
          action: 'update-collection',
          elementName: courseNameWithNamespace
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

        // Append last two tasks
        $(document).queue('tasks', function(){
          windowManager.closeWindow(progressDialog);
          // Last task calls the updateMetadata function
          updateMetadata(courseName);
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
    }
  } );
};
