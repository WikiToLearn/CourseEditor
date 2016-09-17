$(function () {
  var dragElements = [];
  //Add all existing chapters to the dragElements array
  $.each(chapters, function(key, value){
    var dragItem = new DraggableHandledItemWidget( {
      data: value,
      icon: 'menu',
      label: value
    } );
    dragItem.$label.append("<i class='fa fa-trash fa-lg deleteElementIcon pull-right'></i>",
    "<i class='fa fa-pencil editElementIcon fa-lg pull-right'></i>");
    dragElements.push(dragItem);
  });

  //Create a draggableWidget with the items in the dragElements array
  var draggableWidget = new DraggableGroupWidget( {
    items: dragElements
  } );
  var fieldDrag = new OO.ui.FieldLayout(draggableWidget);

  //Create a textInputWidget for new chapters
  var textInputWidget = new OO.ui.TextInputWidget( { placeholder: OO.ui.deferMsg( 'courseeditor-add-new-chapter' ) } );
  var fieldInput = 	new OO.ui.FieldLayout( textInputWidget);

  //Append all created elements to DOM
  $('#chaptersList').append(fieldDrag.$element, fieldInput.$element);

  //Init Handlers
  initHandlers(draggableWidget, textInputWidget, editStack);

  $('#saveSectionButton').click(function(){
    var newChapters = [];
    $.each(draggableWidget.getItems(), function(key, value){
      newChapters.push(value.data);
    });

    editStack.push({
      action: 'update',
      elementsList: JSON.stringify(newChapters)
    });
    editStack.push({
      action: 'purge'
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
        rs: 'CourseEditorOperations::applySectionOp',
        rsargs: [$('#parentName').text(), JSON.stringify(operation)]
      }, function ( data ) {
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
      window.location.assign('/' +  $('#parentName').text());
    });

    dequeue('tasks')
  });
})
