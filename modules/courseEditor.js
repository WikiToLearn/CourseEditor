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
  var addButton = new OO.ui.ButtonWidget({id: 'addElementButton', label: ''});
  addButton.$label.append("<i class='fa fa-plus fa-lg'></i>");
  var fieldInput = 	new OO.ui.ActionFieldLayout( textInputWidget, addButton);

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

    var doTask = function(microOp, next){
      progressDialog.setCurrentOp(microOp);
      $.getJSON( mw.util.wikiScript(), {
        action: 'ajax',
        rs: 'CourseEditorOperations::applyCourseOp',
        rsargs: [$('#parentName').text(), JSON.stringify(microOp)]
      }, function ( data ) {
        if (data.success !== true){
          $('#alert').html(OO.ui.deferMsg('courseeditor-error-operation'));
          $('#alert').append(OO.ui.deferMsg('courseeditor-operation-action-' + data.action));
          if(data.elementName){
            var localizedMsg = " " + data.elementName + OO.ui.msg('courseeditor-error-operation-fail');
            $('#alert').append(localizedMsg);
          }else {
            $('#alert').append(OO.ui.deferMsg('courseeditor-error-operation-fail'));
          }
          $('#alert').show();
          windowManager.closeWindow(progressDialog);
          $(document).clearQueue('tasks');
        }else {
          progressDialog.updateProgress(unitaryIncrement);
          next();
        }
      });
    };

    var createTask = function(microOp){
      return function(next){
        doTask(microOp, next);
      }
    };

    /*function prepareCreateMicroOperations(operation) {
      var dfd=$.Deferred();
      createMicroOperations(operation, function(microOps){
        for (var i = 0; i < microOps.length; i++) {
          console.log(microOps[i]);
          $(document).queue('tasks', createTask(microOps[i]));
        }
        dfd.resolve();
      });
      return dfd.promise();
    }

    var promises = [];

    while (editStack.length > 0) {
      var operation = editStack.shift();
      promises.push(prepareCreateMicroOperations(operation));
    }

    $.when.apply($, promises).done(function(){
      $(document).queue('tasks', createTask({
        action: 'update',
        elementsList: JSON.stringify(newSections)
      }));

      $(document).queue('tasks', createTask({
          action: 'update-collection'
      }));

      $(document).queue('tasks', function(){
        windowManager.closeWindow(progressDialog);
        //window.location.assign('/' +  $('#courseName').text());
      });

      dequeue('tasks');
    });*/

    while( editStack.length > 0) {
      var operation =  editStack.shift();
      var microOps = createMicroOperations(operation);
      for (var i = 0; i < microOps.length; i++) {
        $(document).queue('tasks', createTask(microOps[i]));
      }
    };


    $(document).queue('tasks', function(){
      windowManager.closeWindow(progressDialog);
      window.location.assign('/' +  $('#parentName').text());
    });

    dequeue('tasks');
  });
})
