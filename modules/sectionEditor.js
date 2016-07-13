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

function EditDialog(draggableWidget, chapterName, config ) {
    EditDialog.parent.call( this, config );
    this.draggableWidget = draggableWidget;
    this.chapterName = chapterName;
    this.textInputWidget = new OO.ui.TextInputWidget($.extend( { validate: 'non-empty' }, config ) );
    this.textInputWidget.setValue(chapterName);
}
OO.inheritClass( EditDialog, OO.ui.ProcessDialog );

EditDialog.static.title = 'Edit Dialog';
EditDialog.static.actions = [
    { action: 'save', label: 'Rename', flags: 'primary' },
    { label: 'Cancel', flags: 'safe' }
];

EditDialog.prototype.initialize = function () {
    EditDialog.parent.prototype.initialize.apply( this, arguments );
    this.content = new OO.ui.PanelLayout( { padded: true, expanded: false } );
    this.content.$element.append(this.textInputWidget.$element );
    this.$body.append( this.content.$element );
};

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
                var iconDelete = $("<i class='fa fa-trash deleteChapterIcon pull-right'></i>");
                var iconEdit = $("<i class='fa fa-pencil editChapterIcon pull-right'></i>");
                chapter.$label.append(iconDelete, iconEdit);
                $(iconDelete).click(function(){
                  deleteChapter(dialog.draggableWidget, $(this).parent().text());
                });
                $(iconEdit).click(function(){
                  editChapter(dialog.draggableWidget, $(this).parent().text());
                });
              }
            });
            dialog.close( { action: action } );
        } );
    }
    return EditDialog.parent.prototype.getActionProcess.call( this, action );
};


var deleteChapter = function(draggableWidget, chapterName){
  var chapterToRemove = draggableWidget.getItemFromData(chapterName);
  draggableWidget.removeItems([chapterToRemove]);
};

var addChapter = function(draggableWidget, chapterName){
  if($.trim(chapterName).length !== 0){
    var dragItem = new DraggableHandledItemWidget( {
      data: chapterName,
      icon: 'menu',
      label: chapterName
    } );
    var iconDelete = $("<i class='fa fa-trash fa-lg deleteChapterIcon pull-right'></i>");
    var iconEdit = $("<i class='fa fa-pencil fa-lg editChapterIcon pull-right'></i>");
    dragItem.$label.append(iconDelete, iconEdit);
    draggableWidget.addItems([dragItem]);
    $(iconDelete).click(function(){
      deleteChapter(draggableWidget, $(this).parent().text());
    });
    $(iconEdit).click(function(){
      editChapter(draggableWidget, $(this).parent().text());
    });
  }
};

var editChapter = function(draggableWidget, chapterName){
  var windowManager = new OO.ui.WindowManager();
  $('body').append( windowManager.$element );
  var dialog = new EditDialog(draggableWidget, chapterName);
  windowManager.addWindows( [ dialog ] );
  windowManager.openWindow( dialog );
};

$(function () {
  var dragChapters = [];
  $.each(chapters, function(key, value){
    var dragItem = new DraggableHandledItemWidget( {
      data: value,
      icon: 'menu',
      label: value
    } );
    dragItem.$label.append("<i class='fa fa-trash deleteChapterIcon pull-right'></i>",
    "<i class='fa fa-pencil editChapterIcon pull-right'></i>");
    dragChapters.push(dragItem);
  });
  var draggableWidget = new DraggableGroupWidget( {
    items: dragChapters
  } );
  var fieldDrag = new OO.ui.FieldLayout(draggableWidget);
  var textInputWidget = new OO.ui.TextInputWidget( { placeholder: "Add a new chapter"} );
  var fieldInput = 	new OO.ui.FieldLayout( textInputWidget);
  var buttonSave = new OO.ui.ButtonWidget( {
    label: 'Save section',
    flags: ['constructive'],
  } );
  buttonSave.$label.append("<i class='fa fa-floppy-o pull-left' aria-hidden='true'></i>");
  $('#chaptersList').append(fieldDrag.$element, fieldInput.$element);
  $('#saveDiv').append('<br><br>', buttonSave.$element);
  $('.oo-ui-inputWidget-input').attr('id', 'addChapter');
  $('.deleteChapterIcon').click(function(){
    deleteChapter(draggableWidget, $(this).parent().text());
  });
  $('.editChapterIcon').click(function(){
    editChapter(draggableWidget, $(this).parent().text());
  });

  $('#addChapter').blur(function(){
    addChapter(draggableWidget, textInputWidget.getValue());
    textInputWidget.setValue('');
  });
  $('#addChapter').keypress(function(keypressed) {
    if(keypressed.which == 13) {
      addChapter(draggableWidget, textInputWidget.getValue());
      textInputWidget.setValue('');
    }
  });
})
