$(function () {
  var dragElements = [];
  //Add all existing sections to the dragSections array
  $.each(sections, function(key, value){
    var dragItem = new DraggableHandledItemWidget( {
      data: value,
      icon: 'menu',
      label: value
    } );
    dragItem.$label.append("<i class='fa fa-trash fa-lg deleteElementIcon pull-right'></i>",
    "<i class='fa fa-pencil editElementIcon fa-lg pull-right'></i>");
    dragElements.push(dragItem);
  });

  //Create a draggableWidget with the items in the dragSections array
  var draggableWidget = new DraggableGroupWidget( {
    items: dragElements
  } );
  var fieldDrag = new OO.ui.FieldLayout(draggableWidget);

  //Create a textInputWidget for new sections
  var textInputWidget = new OO.ui.TextInputWidget( { placeholder: OO.ui.deferMsg( 'courseeditor-add-new-section' ) } );
  var fieldInput = 	new OO.ui.FieldLayout( textInputWidget);

  //Append all created elements to DOM
  $('#sectionsList').append(fieldDrag.$element, fieldInput.$element);

  initHandlers(draggableWidget, textInputWidget, editStack);

  $('#saveCourseButton').click(function(){
    var newSections = [];
    $.each(draggableWidget.getItems(), function(key, value){
      newSections.push(value.data);
    });
    editStack.push({
      action: 'update',
      elementsList: JSON.stringify(newSections)
    });
    editStack.push({
      action: 'update-collection'
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
        rs: 'CourseEditorOperations::applyCourseOp',
        rsargs: [$('#courseName').text(), JSON.stringify(operation)]
      }, function ( data ) {
        if (data.success !== true) {
          var alert = '<br><div class="alert alert-danger" id="alert" role="alert"></div>';
          $('#saveDiv').after(alert);
          $('#alert').html(OO.ui.deferMsg('courseeditor-error-operation'));
          $('#alert').append(OO.ui.deferMsg('courseeditor-operation-action-' + data.action));
          if(data.elementName){
            var localizedMsg = " " + data.elementName + OO.ui.msg('courseeditor-error-operation-fail');
            $('#alert').append(localizedMsg);
          }else {
            $('#alert').append(OO.ui.deferMsg('courseeditor-error-operation-fail'));
          }
          windowManager.closeWindow(progressDialog);
          $(document).clearQueue('tasks');
        }else {
          progressDialog.updateProgress(unitaryIncrement);
          next();
        }
      });
    };

    while( editStack.length > 0 ) {
      var operation =  editStack.shift();
      $(document).queue('tasks', createTask(operation));
    };

    $(document).queue('tasks', function(){
      windowManager.closeWindow(progressDialog);
      window.location.assign('/' +  $('#courseName').text());
    });

    dequeue('tasks');
  });
})
