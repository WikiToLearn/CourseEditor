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

  //Create handlers
  $('.deleteElementIcon').click(function(){
    deleteElement(draggableWidget, $(this).parent().text(), editStack);
  });
  $('.editElementIcon').click(function(){
    editElement(draggableWidget, $(this).parent().text(), editStack);
  });
  $('.oo-ui-inputWidget-input').attr('id', 'addElement');
  $('#addElement').blur(function(){
    addElement(draggableWidget, textInputWidget.getValue(), editStack);
    textInputWidget.setValue('');
  });
  $('#addElement').keypress(function(keypressed) {
    if(keypressed.which === 13) {
      addElement(draggableWidget, textInputWidget.getValue(), editStack);
      textInputWidget.setValue('');
    }
  });
  $('#saveCourse').click(function(){
    var newSections = [];
    $.each(draggableWidget.getItems(), function(key, value){
      newSections.push(value.data);
    });
    console.warn(sections, editStack, newSections);
    $.post("/Special:CourseEditor?actiontype=savecourse", {
      courseName: $('#courseName').text(),
      originalSections: JSON.stringify(sections),
      editStack: JSON.stringify(editStack),
      newSections: JSON.stringify(newSections)
    }, function(response, status) {
      console.warn(response, status);
    });
  });
})
