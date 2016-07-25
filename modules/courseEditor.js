/* Create a gloabal windowManager to open dialogs and append it to the body*/
var windowManager = new OO.ui.WindowManager();
$('body').append( windowManager.$element );

/******** HELPER METHODS ********/

/**
 * Delete a section from the draggableWidget and add a item to the
 * RecycleBin list.
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [sectionName]
 * @param {Array} [editStack]
 */
var deleteSection = function(draggableWidget, sectionName, editStack){
  var sectionToRemove = draggableWidget.getItemFromData(sectionName);
  draggableWidget.removeItems([sectionToRemove]);
  editStack.push({
    action: 'delete',
    sectionName: sectionName
  });
  createRecycleBinItem(draggableWidget, sectionName, editStack);
};

/**
 * Restore a section from the RecycleBin and remove its deletion
 * from the editStack
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [sectionName]
 * @param {Array} [editStack]
 */
var restoreSection = function(draggableWidget, sectionName, editStack){
  createDragItem(draggableWidget, sectionName, editStack);
  editStack.splice(editStack.indexOf({action: 'delete', section: sectionName}));
};

/**
 * Add a section to the draggableWidget automatically if its name isn't
 * in the RecycleBin list, otherwise open a MessageDialog and ask to the user
 * if he/she prefer to restore the section or create a new one.
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [sectionName]
 * @param {Array} [editStack]
 */
var addSection = function(draggableWidget, sectionName, editStack){
  if($.trim(sectionName).length !== 0){
    if(findIndexOfDeletedSection(editStack, sectionName) === null){
      createDragItem(draggableWidget, sectionName, editStack);
      editStack.push({
        action: 'add',
        sectionName: sectionName
      });
    }else {
      var messageDialog = new OO.ui.MessageDialog();
      windowManager.addWindows( [ messageDialog ] );
      windowManager.openWindow( messageDialog, {
        title: 'Ops...',
        message: 'There\'s a deleted section with the same name, what do you want to do?',
        actions: [
          { action: 'reject', label: 'Cancel', flags: 'safe' },
          { action: 'restore', label: 'Restore' },
          {
            action: 'confirm',
            label: 'Create new',
            flags: [ 'primary', 'constructive' ]
          }
        ]
      } ).then( function ( opened ) {
        opened.then( function ( closing, data ) {
          if ( data && data.action === 'restore' ) {
            restoreSection(draggableWidget, sectionName, editStack);
            $('button[id="' +  sectionName + '"]').remove();
          } else if(data && data.action === 'confirm') {
            createDragItem(draggableWidget, sectionName, editStack);
            editStack.push({
              action: 'add',
              sectionName: sectionName
            });
          }
        } );
      } );
    }
  }
};

/**
 * Rename a section
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [sectionName]
 * @param {Array} [editStack]
 */
var editSection = function(draggableWidget, sectionName, editStack){
  var dialog = new EditDialog(draggableWidget, sectionName, editStack);
  windowManager.addWindows( [ dialog ] );
  windowManager.openWindow( dialog );
};

/******** UTIL METHODS ********/

/**
 * Find the index of a deleted section in the editStack
 * @param {String} [sectionName]
 * @param {Array} [editStack]
 */
var findIndexOfDeletedSection = function(editStack, sectionName) {
  for (var i = 0; i < editStack.length; i++) {
    if (editStack[i]['action'] === 'delete' && editStack[i]['sectionName'] === sectionName) {
      return i;
    }
  }
  return null;
};
/**
 * Create a drag item, its handlers on edit and remove icons and append it to
 * to the draggableWidget.
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [sectionName]
 * @param {Array} [editStack]
 */
var createDragItem = function(draggableWidget, sectionName, editStack){
  //Create item and icons
  var dragItem = new DraggableHandledItemWidget( {
    data: sectionName,
    icon: 'menu',
    label: sectionName
  } );
  var iconDelete = $("<i class='fa fa-trash fa-lg deleteSectionIcon pull-right'></i>");
  var iconEdit = $("<i class='fa fa-pencil fa-lg editSectionIcon pull-right'></i>");

  //Append icons and add the item to draggableWidget
  dragItem.$label.append(iconDelete, iconEdit);
  draggableWidget.addItems([dragItem]);

  //Create handlers
  $(iconDelete).click(function(){
    deleteSection(draggableWidget, $(this).parent().text(), editStack);
  });
  $(iconEdit).click(function(){
    editSection(draggableWidget, $(this).parent().text(), editStack);
  });
};

/**
 * Create a button list group item, its handler on undo and append it to
 * to the RecycleBin list group.
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [sectionName]
 * @param {Array} [editStack]
 */
var createRecycleBinItem = function(draggableWidget, sectionName, editStack){
  //Create item and icon
  var liButton = $('<button type="button" class="list-group-item" id="' + sectionName +'" >&nbsp;&nbsp;' + sectionName +'</button>');
  var undoDeleteIcon = $('<i class="fa fa-undo undoDeleteIcon"></i>');

  //Append icon and add the item to the list
  liButton.prepend(undoDeleteIcon);
  $('.list-group').append(liButton);

  //Create handler
  $(undoDeleteIcon).click(function(){
    var sectionToRestore = $(this).parent().attr('id');
    $(this).parent().remove();
    restoreSection(draggableWidget, sectionToRestore, editStack);
  });
}

/******** OO.UI OBJECTS ********/

/****** Draggable Widget ******/

/**
* Draggable group widget containing drag/drop items
*
* @param {Object} [config] Configuration options
*/
function DraggableGroupWidget( config ) {
  // Configuration initialization
  config = config || {};

  // Parent constructor
  DraggableGroupWidget.parent.call( this, config );

  // Mixin constructors
  OO.ui.mixin.DraggableGroupElement.call( this, $.extend( {}, config, { $group: this.$element } ) );

}

/* Setup */
OO.inheritClass( DraggableGroupWidget, OO.ui.Widget );
OO.mixinClass( DraggableGroupWidget, OO.ui.mixin.DraggableGroupElement );

/**
* Drag/drop items with custom handle
*
* @param {Object} [config] Configuration options
*/
function DraggableHandledItemWidget( config ) {
  // Configuration initialization
  config = config || {};

  // Parent constructor
  DraggableHandledItemWidget.parent.call( this, config );

  // Mixin constructors
  OO.ui.mixin.DraggableElement.call( this, $.extend( { $handle: this.$icon }, config ) );
}

/* Setup */
OO.inheritClass( DraggableHandledItemWidget, OO.ui.DecoratedOptionWidget );
OO.mixinClass( DraggableHandledItemWidget, OO.ui.mixin.DraggableElement );

/****** Edit Dialog ******/

/* Create a dialog */
function EditDialog(draggableWidget, sectionName, editStack, config ) {
    EditDialog.parent.call( this, config );
    this.draggableWidget = draggableWidget;
    this.sectionName = sectionName;
    this.editStack = editStack;
    this.textInputWidget = new OO.ui.TextInputWidget($.extend( { validate: 'non-empty' }, config ) );
    this.textInputWidget.setValue(sectionName);
}

/* Inheritance */
OO.inheritClass( EditDialog, OO.ui.ProcessDialog );

/* Static Properties */
EditDialog.static.title = OO.ui.deferMsg( 'courseeditor-edit-dialog-course' );
EditDialog.static.actions = [
    { action: 'save', label: OO.ui.deferMsg( 'courseeditor-rename' ), flags: 'primary' },
    { label: OO.ui.deferMsg( 'courseeditor-cancel' ), flags: 'safe' }
];

/* Initialize the dialog elements */
EditDialog.prototype.initialize = function () {
    EditDialog.parent.prototype.initialize.apply( this, arguments );
    this.content = new OO.ui.PanelLayout( { padded: true, expanded: false } );
    this.content.$element.append(this.textInputWidget.$element );
    this.$body.append( this.content.$element );
};

/* Define actions */
EditDialog.prototype.getActionProcess = function ( action ) {
    var dialog = this;
    if ( action === 'save' ) {
        return new OO.ui.Process( function () {
            var newSectionName = dialog.textInputWidget.getValue();
            var items = dialog.draggableWidget.getItems();
            items.filter(function(section) {
              if(section.data === dialog.sectionName){
                section.setData(newSectionName);
                section.setLabel(newSectionName);
                var iconDelete = $("<i class='fa fa-trash fa-lg deleteSectionIcon pull-right'></i>");
                var iconEdit = $("<i class='fa fa-pencil fa-lg editSectionIcon pull-right'></i>");
                section.$label.append(iconDelete, iconEdit);
                $(iconDelete).click(function(){
                  deleteSection(dialog.draggableWidget, $(this).parent().text(), dialog.editStack);
                });
                $(iconEdit).click(function(){
                  editSection(dialog.draggableWidget, $(this).parent().text(), dialog.editStack);
                });
                dialog.editStack.push({
                  action: 'rename',
                  sectionName: dialog.sectionName,
                  newSectionName: newSectionName
                })
              }
            });
            dialog.close( { action: action } );
        } );
    }
    return EditDialog.parent.prototype.getActionProcess.call( this, action );
};

$(function () {
  var dragSections = [];
  //Add all existing sections to the dragSections array
  $.each(sections, function(key, value){
    var dragItem = new DraggableHandledItemWidget( {
      data: value,
      icon: 'menu',
      label: value
    } );
    dragItem.$label.append("<i class='fa fa-trash fa-lg deleteSectionIcon pull-right'></i>",
    "<i class='fa fa-pencil editSectionIcon fa-lg pull-right'></i>");
    dragSections.push(dragItem);
  });

  //Create a draggableWidget with the items in the dragSections array
  var draggableWidget = new DraggableGroupWidget( {
    items: dragSections
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
  $('.deleteSectionIcon').click(function(){
    deleteSection(draggableWidget, $(this).parent().text(), editStack);
  });
  $('.editSectionIcon').click(function(){
    editSection(draggableWidget, $(this).parent().text(), editStack);
  });
  $('.oo-ui-inputWidget-input').attr('id', 'addSection');
  $('#addSection').blur(function(){
    addSection(draggableWidget, textInputWidget.getValue(), editStack);
    textInputWidget.setValue('');
  });
  $('#addSection').keypress(function(keypressed) {
    if(keypressed.which == 13) {
      addSection(draggableWidget, textInputWidget.getValue(), editStack);
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
