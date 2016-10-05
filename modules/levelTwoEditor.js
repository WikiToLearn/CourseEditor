$(function () {
  var dragElements = [];
  //Add all existing levelsThree to the dragElements array
  $.each(levelsThree, function(key, value){
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

  //Create a textInputWidget for new levelsThree
  var textInputWidget = new OO.ui.TextInputWidget( { placeholder: OO.ui.deferMsg( 'courseeditor-add-new-levelThree' ) } );
  var addButton = new OO.ui.ButtonWidget({id: 'addElementButton', label: ''});
  addButton.$label.append("<i class='fa fa-plus fa-lg'></i>");
  var fieldInput = 	new OO.ui.ActionFieldLayout( textInputWidget, addButton);

  //Append all created elements to DOM
  $('#levelsThreeList').append(fieldDrag.$element, fieldInput.$element);

  //Init Handlers
  initHandlers(draggableWidget, textInputWidget, editStack);

  $('#saveLevelTwoButton').click(function(){
    var inputWidgetContent = textInputWidget.getValue();
    if ($.trim(inputWidgetContent).length !== 0) {
      $('#alertInputNotEmpty').show();
      return;
    }
    var newLevelsThree = [];
    $.each(draggableWidget.getItems(), function(key, value){
      newLevelsThree.push(value.data);
    });

    editStack.push({
      action: 'update',
      elementsList: JSON.stringify(newLevelsThree)
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
        rs: 'CourseEditorOperations::applyLevelTwoOp',
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
      var levelTwoName = $('#parentName').text();
      var splitted = levelTwoName.split('/');
      var courseName;
      if(splitted.length > 2){
        courseName = splitted[0] + '/' + splitted[1];
      }else {
        courseName = splitted[0];
      }
      window.location.assign('/' +  courseName);
    });

    dequeue('tasks')
  });
})
