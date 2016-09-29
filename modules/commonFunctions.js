/* Create a gloabal windowManager to open dialogs and append it to the body*/
var windowManager = new OO.ui.WindowManager();
$('body').append( windowManager.$element );

/******** UTIL METHODS ********/

var createMicroOperations =  function(operation){
  switch (operation.action) {
    case 'rename':
      return createRenameMicroOperations(operation);
      break;
    case 'delete':
      return createDeleteMicroOperations(operation);
      break;
    default:
      return createDefaultMicroOperations(operation);
  }
};

var createDefaultMicroOperations = function(operation){
  var microOps = [];
  microOps.push(operation);
  return microOps;
}

var createRenameMicroOperations = function(operation) {
  var microOps = [];
  microOps.push({
    action: 'rename-move-task',
    elementName: operation.elementName,
    newElementName: operation.newElementName
  });
  microOps.push({
    action: 'rename-update-task',
    elementName: operation.elementName,
    newElementName: operation.newElementName
  });
  return microOps;
};

var createDeleteMicroOperations = function(operation) {
  var microOps = [];
  microOps.push({
    action: 'delete-chapters-task',
    elementName: operation.elementName
  });
  microOps.push({
    action: 'delete-section-task',
    elementName: operation.elementName
  });
  return microOps;
};

/*
var createMicroDefaultOperations = function(operation, callback) {
  var microOps = [];
  microOps.push(operation);
  callback(microOps);
};

var createMicroRenameOperations =  function(operation, callback) {
  var title = new mw.Title($('#courseName').text());
  var microOps = [];
  getSubpages(title, operation, function(subpages){
    for (var i = 0; i < subpages.query.allpages.length; i++) {
      var page = subpages.query.allpages[i];
      //The better HACK ever: not return the callback until the for dosn't
      //  completed
      if(i === subpages.query.allpages.length - 1) {
        getMicroOpsFromBacklinks(page, operation, microOps, function(microOps) {
          //Move the page and all its subpages
          microOps.push(operation);
          callback(microOps);
        });
      } else {
        getMicroOpsFromBacklinks(page, operation, microOps, function(){});
      }
    }
  });
};


var getMicroOpsFromBacklinks = function(page, operation, microOps, returnMicroOps){
  var api = new mw.Api();
  api.get( {
    action: 'query',
    list: 'backlinks',
    bltitle:  page.title,
  } ).done( function ( data) {
    if(data.query.backlinks.length > 0){
      var backlinks = data.query.backlinks;
      backlinks.shift(); //the course with transcluded pages
      for (var i = 0; i < backlinks.length; i++) {
        microOps.push({
          action: 'fix-link',
          elementName: backlinks[i].title,
          linkToReplace: page.title,
          replacement: operation.newElementName
        });
      }
    }
    returnMicroOps(microOps);
  });
};

var getSubpages = function (title, operation, returnSubpages){
  var api = new mw.Api();
  api.get( {
    action: 'query',
    list: 'allpages',
    apprefix:  title.getMain() + "/" + operation.elementName,
    apnamespace: title.getNamespaceId()
  } ).done( function ( data) {
    returnSubpages(data);
  });
};*/

/**
 * Init handlers
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {TextInputWidget} [textInputWidget]
 * @param {Array} [editStack]
 */
var initHandlers = function(draggableWidget, textInputWidget, editStack){

  $('.deleteElementIcon').click(function(){
    deleteElement(draggableWidget, $(this).parent().text(), editStack);
  });
  $('.editElementIcon').click(function(){
    editElement(draggableWidget, $(this).parent().text(), editStack);
  });
  $('.moveElementIcon').click(function(){
    moveElement(draggableWidget, $(this).parent().text(), editStack);
  });
  $('#addElementButton').click(function(){
    $('#alert').hide();
    addElement(draggableWidget, textInputWidget.getValue(), editStack);
    textInputWidget.setValue('');
  });
  $('.oo-ui-inputWidget-input').attr('id', 'addElementInput');
  /*$('#addElementInput').blur(function(){
    $('#alert').hide();
    addElement(draggableWidget, textInputWidget.getValue(), editStack);
    textInputWidget.setValue('');
  });*/
  $('#addElementInput').keypress(function(keypressed) {
    $('#alert').hide();
    if(keypressed.which === 13) {
      addElement(draggableWidget, textInputWidget.getValue(), editStack);
      textInputWidget.setValue('');
    }
  });
};

/**
 * Find the index of a deleted element in the editStack
 * @param {String} [elementName]
 * @param {Array} [editStack]
 */
var findIndexOfDeletedElement = function(editStack, elementName) {
  for (var i = 0; i < editStack.length; i++) {
    if (editStack[i]['action'] === 'delete' && editStack[i]['elementName'] === elementName) {
      return i;
    }
  }
  return null;
};
/**
 * Find the index of already added or renamed element in the editStack
 * @param {String} [elementName]
 * @param {DraggableWidget} [draggableWidget]
 * @return boolean
 */
var elementExist = function(draggableWidget, elementName, callback) {
  var api = new mw.Api();
  api.get({
    action : 'query',
    titles : $('#parentName').text() + '/' + elementName
  }).done( function ( data ) {
    var pages = data.query.pages;
    if (!pages['-1']) {
      callback(true);
      return;
    }
    var items = draggableWidget.getItems();
    for (var item in items) {
      if (items[item].data === elementName) {
        callback(true);
        return;
      }
    }
    callback(false);
  } );
};

/**
 * Create a drag item, its handlers on edit and remove icons and append it to
 * to the draggableWidget.
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [elementName]
 * @param {Array} [editStack]
 */
var createDragItem = function(draggableWidget, elementName, editStack){
  //Create item and icons
  var dragItem = new DraggableHandledItemWidget( {
    data: elementName,
    icon: 'menu',
    label: elementName
  } );
  var iconDelete = $("<i class='fa fa-trash fa-lg deleteElementIcon pull-right'></i>");
  var iconMove = $("<i class='fa fa-reply fa-lg moveElementIcon pull-right'></i>");
  var iconEdit = $("<i class='fa fa-pencil fa-lg editElementIcon pull-right'></i>");

  //Append icons and add the item to draggableWidget
  dragItem.$label.append(iconDelete, iconMove, iconEdit);
  draggableWidget.addItems([dragItem]);

  //Create handlers
  $(iconDelete).click(function(){
    deleteElement(draggableWidget, $(this).parent().text(), editStack);
  });
  $(iconEdit).click(function(){
    editElement(draggableWidget, $(this).parent().text(), editStack);
  });
  $(iconMove).click(function(){
    moveElement(draggableWidget, $(this).parent().text(), editStack);
  });
};

/**
 * Create a button list group item, its handler on undo and append it to
 * to the RecycleBin list group.
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [elementName]
 * @param {Array} [editStack]
 */
var createRecycleBinItem = function(draggableWidget, elementName, editStack){
  //Create item and icon
  var liButton = $('<li type="button" class="list-group-item" id="' + elementName +'" >&nbsp;&nbsp;' + elementName +'</li>');
  var undoDeleteIcon = $('<i class="fa fa-undo undoDeleteIcon"></i>');

  //Append icon and add the item to the list
  liButton.prepend(undoDeleteIcon);
  $('.list-group').append(liButton);

  //Create handler
  $(undoDeleteIcon).click(function(){
    var elementToRestore = $(this).parent().attr('id');
    restoreElement(draggableWidget, elementToRestore, editStack);
  });
}

/******** HELPER METHODS ********/

var dequeue = function(queueName){
  $(document).dequeue(queueName);
};

/**
 * Rename a element
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [elementName]
 * @param {Array} [editStack]
 */
var moveElement = function(draggableWidget, elementName, editStack){
  var dialog = new MoveDialog(draggableWidget, elementName, editStack);
  windowManager.addWindows( [ dialog ] );
  windowManager.openWindow( dialog );
};

/**
 * Delete a element from the draggableWidget and add a item to the
 * RecycleBin list.
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [elementName]
 * @param {Array} [editStack]
 */
var deleteElement = function(draggableWidget, elementName, editStack){
  var elementToRemove = draggableWidget.getItemFromData(elementName);
  draggableWidget.removeItems([elementToRemove]);
  editStack.push({
    action: 'delete',
    elementName: elementName
  });
  createRecycleBinItem(draggableWidget, elementName, editStack);
};

/**
 * Restore a element from the RecycleBin and remove its deletion
 * from the editStack
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [elementName]
 * @param {Array} [editStack]
 */
var restoreElement = function(draggableWidget, elementName, editStack){
  createDragItem(draggableWidget, elementName, editStack);
  editStack.splice(editStack.indexOf({action: 'delete', element: elementName}));
  $('li[id="' +  elementName + '"]').remove();
};

/**
 * Add a element to the draggableWidget automatically if its name isn't
 * in the RecycleBin list, otherwise open a MessageDialog and ask to the user
 * if he/she prefer to restore the element or create a new one.
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [elementName]
 * @param {Array} [editStack]
 */
var addElement = function(draggableWidget, elementName, editStack){
  if($.trim(elementName).length !== 0){
    elementExist(draggableWidget, elementName, function(result){
      if(result ===  true){
        $('#alert').show();
      }else if (findIndexOfDeletedElement(editStack, elementName) !== null){
        var messageDialog = new OO.ui.MessageDialog();
        windowManager.addWindows( [ messageDialog ] );
        windowManager.openWindow( messageDialog, {
          title: OO.ui.deferMsg('courseeditor-message-dialog-title'),
          message: OO.ui.deferMsg('courseeditor-message-dialog-message'),
          actions: [
            { action: 'reject', label: OO.ui.deferMsg('courseeditor-message-dialog-cancel'), flags: 'safe' },
            { action: 'restore', label: OO.ui.deferMsg('courseeditor-message-dialog-restore') },
            {
              action: 'confirm',
              label: OO.ui.deferMsg('courseeditor-message-dialog-create-new'),
              flags: [ 'primary', 'constructive' ]
            }
          ]
        } ).then( function ( opened ) {
          opened.then( function ( closing, data ) {
            if ( data && data.action === 'restore' ) {
              restoreElement(draggableWidget, elementName, editStack);
            } else if(data && data.action === 'confirm') {
              createDragItem(draggableWidget, elementName, editStack);
              editStack.push({
                action: 'add',
                elementName: elementName
              });
            }
          } );
        } );
      }else {
        createDragItem(draggableWidget, elementName, editStack);
        editStack.push({
          action: 'add',
          elementName: elementName
        });
      }
    });
  }
};

/**
 * Rename a element
 * @param {DraggableGroupWidget} [draggableWidget]
 * @param {String} [elementName]
 * @param {Array} [editStack]
 */
var editElement = function(draggableWidget, elementName, editStack){
  var dialog = new EditDialog(draggableWidget, elementName, editStack);
  windowManager.addWindows( [ dialog ] );
  windowManager.openWindow( dialog );
};

/******** OO.UI OBJECTS ********/
function ProgressDialogIndeterminate( config ) {
  ProgressDialogIndeterminate.parent.call( this, config );
};
OO.inheritClass( ProgressDialogIndeterminate, OO.ui.Dialog );
ProgressDialogIndeterminate.static.escapable = false;
ProgressDialogIndeterminate.prototype.initialize = function () {
  ProgressDialogIndeterminate.parent.prototype.initialize.call( this );
  this.progressBar = new OO.ui.ProgressBarWidget();
  this.currentOp = new OO.ui.LabelWidget( {
    label: OO.ui.msg('courseeditor-progressdialog-wait')
  } );
  this.content = new OO.ui.PanelLayout( { padded: true, expanded: false } );
  this.content.$element.append(this.progressBar.$element, this.currentOp.$element);
  this.$body.append( this.content.$element );
};
ProgressDialogIndeterminate.prototype.getBodyHeight = function () {
  return this.content.$element.outerHeight( true );
};


function ProgressDialog( config ) {
  ProgressDialog.parent.call( this, config );
};
OO.inheritClass( ProgressDialog, OO.ui.Dialog );
ProgressDialog.static.escapable = false;
ProgressDialog.prototype.initialize = function () {
  ProgressDialog.parent.prototype.initialize.call( this );
  this.progressBar = new OO.ui.ProgressBarWidget({
    progress: 0
  });
  this.currentOp = new OO.ui.LabelWidget( {
    label: ''
  } );
  this.content = new OO.ui.PanelLayout( { padded: true, expanded: false } );
  this.content.$element.append(this.progressBar.$element, this.currentOp.$element);
  this.$body.append( this.content.$element );
};
ProgressDialog.prototype.getBodyHeight = function () {
  return this.content.$element.outerHeight( true );
};
ProgressDialog.prototype.updateProgress =  function(unitaryIncrement){
  var currentProgress  = this.progressBar.getProgress();
  this.progressBar.setProgress(currentProgress + unitaryIncrement);
};
ProgressDialog.prototype.setCurrentOp = function(operation){
  var labelToSet = OO.ui.msg('courseeditor-operation-action-' + operation.action);
  if(operation.elementName){
    labelToSet += " " + operation.elementName;
  }
  this.currentOp.setLabel(labelToSet);
};

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
function EditDialog(draggableWidget, elementName, editStack, config ) {
    EditDialog.parent.call( this, config );
    this.draggableWidget = draggableWidget;
    this.elementName = elementName;
    this.editStack = editStack;
    this.textInputWidget = new OO.ui.TextInputWidget($.extend( { validate: 'non-empty' }, config ) );
    this.textInputWidget.setValue(elementName);
}

/* Inheritance */
OO.inheritClass( EditDialog, OO.ui.ProcessDialog );

/* Static Properties */
EditDialog.static.title = OO.ui.deferMsg( 'courseeditor-edit-dialog' );
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
            var newElementName = dialog.textInputWidget.getValue();
            var items = dialog.draggableWidget.getItems();
            elementExist(dialog.draggableWidget, newElementName, function(result){
              if(result === true){
                $('#alert').show();
              }else {
                items.filter(function(element) {
                  if(element.data === dialog.elementName){
                    element.setData(newElementName);
                    element.setLabel(newElementName);
                    var iconDelete = $("<i class='fa fa-trash fa-lg deleteElementIcon pull-right'></i>");
                    var iconEdit = $("<i class='fa fa-pencil fa-lg editElementIcon pull-right'></i>");
                    element.$label.append(iconDelete, iconEdit);
                    $(iconDelete).click(function(){
                      deleteElement(dialog.draggableWidget, $(this).parent().text(), dialog.editStack);
                    });
                    $(iconEdit).click(function(){
                      editElement(dialog.draggableWidget, $(this).parent().text(), dialog.editStack);
                    });
                    dialog.editStack.push({
                      action: 'rename',
                      elementName: dialog.elementName,
                      newElementName: newElementName
                    })
                  }
                });
              }
            });
            dialog.close( { action: action } );
        } );
    }
    return EditDialog.parent.prototype.getActionProcess.call( this, action );
};

/****** Move Dialog ******/

/* Create a dialog */
function MoveDialog(draggableWidget, elementName, editStack, config ) {
    MoveDialog.parent.call( this, config );
    this.draggableWidget = draggableWidget;
    this.elementName = elementName;
    this.editStack = editStack;
    this.dropdownWidget = new OO.ui.DropdownWidget({
      label: 'Seleziona una sezione',
      menu: {
        items: []
      }
    } );
};

/* Inheritance */
OO.inheritClass( MoveDialog, OO.ui.ProcessDialog );

/* Static Properties */
MoveDialog.static.title = OO.ui.deferMsg( 'courseeditor-edit-dialog' );
MoveDialog.static.actions = [
    { action: 'save', label: OO.ui.deferMsg( 'courseeditor-rename' ), flags: 'primary' },
    { label: OO.ui.deferMsg( 'courseeditor-cancel' ), flags: 'safe' }
];

/* Initialize the dialog elements */
MoveDialog.prototype.initialize = function () {
    MoveDialog.parent.prototype.initialize.apply( this, arguments );
    this.populateDropdown();
    this.content = new OO.ui.PanelLayout( { padded: true, expanded: false } );
    this.content.$element.append(this.dropdownWidget.$element, "<br><br>" );
    this.$body.append( this.content.$element );
};

MoveDialog.prototype.populateDropdown = function(config) {
  var sectionName = $('#parentName').text();
  var splitted = sectionName.split('/');
  var courseName;
  if(splitted.length > 2){
    courseName = splitted[0] + '/' + splitted[1];
  }else {
    courseName = splitted[0];
  }
  var dialog = this;
  $.getJSON( mw.util.wikiScript(), {
    action: 'ajax',
    rs: 'CourseEditorUtils::getSectionsJson',
    rsargs: [courseName]
  }, function ( data ) {
    var items = [];
    $.each(data, function(key, value){
      items.push(new OO.ui.MenuOptionWidget( {
          data: value,
          label: value,
      } ));
    });
    dialog.dropdownWidget.getMenu().addItems(items);
  });
};


/* Define actions
MoveDialog.prototype.getActionProcess = function ( action ) {
    var dialog = this;
    if ( action === 'save' ) {
        return new OO.ui.Process( function () {
            var newElementName = dialog.textInputWidget.getValue();
            var items = dialog.draggableWidget.getItems();
            elementExist(dialog.draggableWidget, newElementName, function(result){
              if(result === true){
                $('#alert').show();
              }else {
                items.filter(function(element) {
                  if(element.data === dialog.elementName){
                    element.setData(newElementName);
                    element.setLabel(newElementName);
                    var iconDelete = $("<i class='fa fa-trash fa-lg deleteElementIcon pull-right'></i>");
                    var iconEdit = $("<i class='fa fa-pencil fa-lg editElementIcon pull-right'></i>");
                    element.$label.append(iconDelete, iconEdit);
                    $(iconDelete).click(function(){
                      deleteElement(dialog.draggableWidget, $(this).parent().text(), dialog.editStack);
                    });
                    $(iconEdit).click(function(){
                      editElement(dialog.draggableWidget, $(this).parent().text(), dialog.editStack);
                    });
                    dialog.editStack.push({
                      action: 'rename',
                      elementName: dialog.elementName,
                      newElementName: newElementName
                    })
                  }
                });
              }
            });
            dialog.close( { action: action } );
        } );
    }
    return MoveDialog.parent.prototype.getActionProcess.call( this, action );
};*/
