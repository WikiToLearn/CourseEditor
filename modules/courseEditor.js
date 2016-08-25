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

  //Create save button
  var buttonSave = new OO.ui.ButtonWidget( {
    id: 'saveCourse',
    label: OO.ui.deferMsg( 'courseeditor-save-course' ),
    flags: ['constructive'],
  } );
  buttonSave.$label.append("<i class='fa fa-floppy-o pull-left' aria-hidden='true'></i>");

  //Append all created elements to DOM
  $('#sectionsList').append(fieldDrag.$element, fieldInput.$element);
  $('#saveDiv').append('<br><br>', buttonSave.$element);

  initHandlers(draggableWidget, textInputWidget, editStack);

  $('#saveCourse').click(function(){
    var newSections = [];
    $.each(draggableWidget.getItems(), function(key, value){
      newSections.push(value.data);
    });
    $.getJSON( mw.util.wikiScript(), {
      action: 'ajax',
      rs: 'SpecialCourseEditor::saveCourse',
      rsargs: [$('#courseName').text(), JSON.stringify(editStack), JSON.stringify(newSections)]
    }, function ( data ) {
      if(data.isSuccess){
        window.location.assign('/' +  $('#courseName').text());
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
