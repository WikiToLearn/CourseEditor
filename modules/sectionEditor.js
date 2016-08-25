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
    $.getJSON( mw.util.wikiScript(), {
      action: 'ajax',
      rs: 'SpecialCourseEditor::saveSection',
      rsargs: [$('#sectionName').text(), JSON.stringify(editStack), JSON.stringify(newChapters)]
    }, function ( data ) {
      if(data.isSuccess){
        window.location.assign('/' +  $('#sectionName').text());
      }else {
        var alert = '<br><div class="alert alert-danger" id="alert" role="alert"></div>';
        $('#saveDiv').after(alert);
        $('#alert').html("Sorry :( Something went wrong!<br>");
        data.editStack.forEach(function(obj){
          if(obj.success === false){
            $('#alert').append(obj.action);
            if(obj.elementName){
              $('#alert').append(" " + obj.elementName + " fails!<br>");
            }else {
              $('#alert').append(" fails!<br>");
            }
          }
        });
      }
    });
  });
})
