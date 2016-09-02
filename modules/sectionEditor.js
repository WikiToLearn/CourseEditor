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

  //Create save button
  var buttonSave = new OO.ui.ButtonWidget( {
    id: 'saveSection',
    label: OO.ui.deferMsg( 'courseeditor-save-section' ),
    flags: ['constructive'],
  } );
  buttonSave.$label.append("<i class='fa fa-floppy-o pull-left' aria-hidden='true'></i>");

  //Append all created elements to DOM
  $('#chaptersList').append(fieldDrag.$element, fieldInput.$element);
  $('#saveDiv').append('<br><br>', buttonSave.$element);

  //Init Handlers
  initHandlers(draggableWidget, textInputWidget, editStack);

  $('#saveSection').click(function(){
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

    var progressDialog = new ProgressDialog( {
      size: 'medium'
    } );
    windowManager.addWindows( [ progressDialog ] );
    windowManager.openWindow( progressDialog );

    var createTask = function(operation){
      return function(next){
        doTask(operation, next);
      }
    };

    var doTask = function(operation, next){
      $.getJSON( mw.util.wikiScript(), {
        action: 'ajax',
        rs: 'CourseEditorOperations::applySectionOp',
        rsargs: [$('#sectionName').text(), JSON.stringify(operation)]
      }, function ( data ) {
        if (data.success !== true) {
          var alert = '<br><div class="alert alert-danger" id="alert" role="alert"></div>';
          $('#saveDiv').after(alert);
          $('#alert').html(OO.ui.msg('courseeditor-error-operation'));
          $('#alert').append(OO.ui.msg('courseeditor-error-operation-action-' + data.action));
          if(data.elementName){
            var localizedMsg = " " + data.elementName + OO.ui.msg('courseeditor-error-operation-fail');
            $('#alert').append(localizedMsg);
          }else {
            $('#alert').append(OO.ui.msg('courseeditor-error-operation-fail'));
          }
          windowManager.closeWindow(progressDialog);
          $(document).clearQueue('tasks');
        }else{
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
      window.location.assign('/' +  $('#sectionName').text());
    });

    dequeue('tasks')
  });
})
