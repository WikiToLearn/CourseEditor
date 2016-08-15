/* Create a gloabal windowManager to open dialogs and append it to the body*/
var windowManager = new OO.ui.WindowManager();
$('body').append( windowManager.$element );

/******** HELPER METHODS ********/

/**
 * Delete a chapter from the draggableWidget and add a item to the
 * RecycleBin list.
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [chapterName]
 * @param {Array} [editStack]
 */
var deleteChapter = function(draggableWidget, chapterName, editStack){
  var chapterToRemove = draggableWidget.getItemFromData(chapterName);
  draggableWidget.removeItems([chapterToRemove]);
  editStack.push({
    action: 'delete',
    chapterName: chapterName
  });
  createRecycleBinItem(draggableWidget, chapterName, editStack);
};

/**
 * Restore a chapter from the RecycleBin and remove its deletion
 * from the editStack
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [chapterName]
 * @param {Array} [editStack]
 */
var restoreChapter = function(draggableWidget, chapterName, editStack){
  createDragItem(draggableWidget, chapterName, editStack);
  editStack.splice(editStack.indexOf({action: 'delete', chapter: chapterName}));
};

/**
 * Add a chapter to the draggableWidget automatically if its name isn't
 * in the RecycleBin list, otherwise open a MessageDialog and ask to the user
 * if he/she prefer to restore the chapter or create a new one.
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [chapterName]
 * @param {Array} [editStack]
 */
var addChapter = function(draggableWidget, chapterName, editStack){
  if($.trim(chapterName).length !== 0){
    if(findIndexOfDeletedChapter(editStack, chapterName) === null){
      createDragItem(draggableWidget, chapterName, editStack);
      editStack.push({
        action: 'add',
        chapterName: chapterName
      });
    }else {
      var messageDialog = new OO.ui.MessageDialog();
      windowManager.addWindows( [ messageDialog ] );
      windowManager.openWindow( messageDialog, {
        title: 'Ops...',
        message: 'There\'s a deleted chapter with the same name, what do you want to do?',
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
            restoreChapter(draggableWidget, chapterName, editStack);
            $('button[id="' +  chapterName + '"]').remove();
          } else if(data && data.action === 'confirm') {
            createDragItem(draggableWidget, chapterName, editStack);
            editStack.push({
              action: 'add',
              chapterName: chapterName
            });
          }
        } );
      } );
    }
  }
};

/**
 * Rename a chapter
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [chapterName]
 * @param {Array} [editStack]
 */
var editChapter = function(draggableWidget, chapterName, editStack){
  var dialog = new EditDialog(draggableWidget, chapterName, editStack);
  windowManager.addWindows( [ dialog ] );
  windowManager.openWindow( dialog );
};

/******** UTIL METHODS ********/

/**
 * Find the index of a deleted chapter in the editStack
 * @param {String} [chapterName]
 * @param {Array} [editStack]
 */
var findIndexOfDeletedChapter = function(editStack, chapterName) {
  for (var i = 0; i < editStack.length; i++) {
    if (editStack[i]['action'] === 'delete' && editStack[i]['chapterName'] === chapterName) {
      return i;
    }
  }
  return null;
};
/**
 * Create a drag item, its handlers on edit and remove icons and append it to
 * to the draggableWidget.
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [chapterName]
 * @param {Array} [editStack]
 */
var createDragItem = function(draggableWidget, chapterName, editStack){
  //Create item and icons
  var dragItem = new DraggableHandledItemWidget( {
    data: chapterName,
    icon: 'menu',
    label: chapterName
  } );
  var iconDelete = $("<i class='fa fa-trash fa-lg deleteChapterIcon pull-right'></i>");
  var iconEdit = $("<i class='fa fa-pencil fa-lg editChapterIcon pull-right'></i>");

  //Append icons and add the item to draggableWidget
  dragItem.$label.append(iconDelete, iconEdit);
  draggableWidget.addItems([dragItem]);

  //Create handlers
  $(iconDelete).click(function(){
    deleteChapter(draggableWidget, $(this).parent().text(), editStack);
  });
  $(iconEdit).click(function(){
    editChapter(draggableWidget, $(this).parent().text(), editStack);
  });
};

/**
 * Create a button list group item, its handler on undo and append it to
 * to the RecycleBin list group.
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [chapterName]
 * @param {Array} [editStack]
 */
var createRecycleBinItem = function(draggableWidget, chapterName, editStack){
  //Create item and icon
  var liButton = $('<button type="button" class="list-group-item" id="' + chapterName +'" >&nbsp;&nbsp;' + chapterName +'</button>');
  var undoDeleteIcon = $('<i class="fa fa-undo undoDeleteIcon"></i>');

  //Append icon and add the item to the list
  liButton.prepend(undoDeleteIcon);
  $('.list-group').append(liButton);

  //Create handler
  $(undoDeleteIcon).click(function(){
    var chapterToRestore = $(this).parent().attr('id');
    $(this).parent().remove();
    restoreChapter(draggableWidget, chapterToRestore, editStack);
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
function EditDialog(draggableWidget, chapterName, editStack, config ) {
    EditDialog.parent.call( this, config );
    this.draggableWidget = draggableWidget;
    this.chapterName = chapterName;
    this.editStack = editStack;
    this.textInputWidget = new OO.ui.TextInputWidget($.extend( { validate: 'non-empty' }, config ) );
    this.textInputWidget.setValue(chapterName);
}

/* Inheritance */
OO.inheritClass( EditDialog, OO.ui.ProcessDialog );

/* Static Properties */
EditDialog.static.title = OO.ui.deferMsg( 'courseeditor-edit-dialog-section' );
EditDialog.static.actions = [
    { action: 'save', label: OO.ui.deferMsg( 'courseeditor-rename-chapter' ), flags: 'primary' },
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
            var newChapterName = dialog.textInputWidget.getValue();
            var items = dialog.draggableWidget.getItems();
            items.filter(function(chapter) {
              if(chapter.data === dialog.chapterName){
                chapter.setData(newChapterName);
                chapter.setLabel(newChapterName);
                var iconDelete = $("<i class='fa fa-trash fa-lg deleteChapterIcon pull-right'></i>");
                var iconEdit = $("<i class='fa fa-pencil fa-lg editChapterIcon pull-right'></i>");
                chapter.$label.append(iconDelete, iconEdit);
                $(iconDelete).click(function(){
                  deleteChapter(dialog.draggableWidget, $(this).parent().text(), dialog.editStack);
                });
                $(iconEdit).click(function(){
                  editChapter(dialog.draggableWidget, $(this).parent().text(), dialog.editStack);
                });
                dialog.editStack.push({
                  action: 'rename',
                  chapterName: dialog.chapterName,
                  newChapterName: newChapterName
                })
              }
            });
            dialog.close( { action: action } );
        } );
    }
    return EditDialog.parent.prototype.getActionProcess.call( this, action );
};

$(function () {
  var dragChapters = [];
  //Add all existing chapters to the dragChapters array
  $.each(chapters, function(key, value){
    var dragItem = new DraggableHandledItemWidget( {
      data: value,
      icon: 'menu',
      label: value
    } );
    dragItem.$label.append("<i class='fa fa-trash fa-lg deleteChapterIcon pull-right'></i>",
    "<i class='fa fa-pencil editChapterIcon fa-lg pull-right'></i>");
    dragChapters.push(dragItem);
  });

  //Create a draggableWidget with the items in the dragChapters array
  var draggableWidget = new DraggableGroupWidget( {
    items: dragChapters
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

  //Create handlers
  $('.deleteChapterIcon').click(function(){
    deleteChapter(draggableWidget, $(this).parent().text(), editStack);
  });
  $('.editChapterIcon').click(function(){
    editChapter(draggableWidget, $(this).parent().text(), editStack);
  });
  $('.oo-ui-inputWidget-input').attr('id', 'addChapter');
  $('#addChapter').blur(function(){
    addChapter(draggableWidget, textInputWidget.getValue(), editStack);
    textInputWidget.setValue('');
  });
  $('#addChapter').keypress(function(keypressed) {
    if(keypressed.which === 13) {
      addChapter(draggableWidget, textInputWidget.getValue(), editStack);
      textInputWidget.setValue('');
    }
  });
  $('#saveSection').click(function(){
    /*$('<form action="/Special:CourseEditor?actiontype=savesection" method="POST">' +
    '<input type="hidden" name="pageName" value="' + $('#sectionName').text() + '">' +
    '<input type="hidden" name="originalChapters" value="' + JSON.stringify(chapters) + '">' +
    '<input type="hidden" name="editStack" value="' + JSON.stringify(editStack) + '">' +
    '</form>').submit();*/
    var newChapters = [];
    $.each(draggableWidget.getItems(), function(key, value){
      newChapters.push(value.data);
    });
    $.post("/Special:CourseEditor?actiontype=savesection", {
      sectionName: $('#sectionName').text(),
      originalChapters: JSON.stringify(chapters),
      editStack: JSON.stringify(editStack),
      newChapters: JSON.stringify(newChapters)
    }, function(response, status) {
      console.warn(response, status);
    });
  });
})
