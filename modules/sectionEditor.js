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

  var deleteChapter = function(draggableWidget, chapterName){
    var chapterToRemove = draggableWidget.getItemFromData(chapterName);
    draggableWidget.removeItems([chapterToRemove]);
  };

$(function () {
  var dragChapters = [];
  //var chapters = $("li[class='chapterItem']");
  $.each(chapters, function(key, value){
    var dragItem = new DraggableHandledItemWidget( {
					data: value,
					icon: 'menu',
					label: value
				} );
    dragItem.$label.append("<i class='fa fa-trash deleteChapterIcon pull-right'></i>");
    dragChapters.push(dragItem);
  });
  var draggableWidget = new DraggableGroupWidget( {
    items: dragChapters
  } );
  var fieldDrag = new OO.ui.FieldLayout(draggableWidget);
  var textInputWidget = new OO.ui.TextInputWidget( { placeholder: "Add a new chapter", autofocus: true } );
  var fieldInput = 	new OO.ui.FieldLayout( textInputWidget);
  $('div[id=chaptersList]').append(fieldDrag.$element, fieldInput.$element);
  $('.oo-ui-inputWidget-input').attr('id', 'addChapter');
  $('.deleteChapterIcon').click(function(){
    deleteChapter(draggableWidget, $(this).parent().text());
  });


  $('.oo-ui-inputWidget-input').keypress(function(keypressed) {
    if(keypressed.which == 13) {
      var dragItem = new DraggableHandledItemWidget( {
  					data: textInputWidget.getValue(),
  					icon: 'menu',
  					label: textInputWidget.getValue()
  		} );
      var iconDelete = $("<i class='fa fa-trash deleteChapterIcon pull-right'></i>");
      dragItem.$label.append(iconDelete);
      draggableWidget.addItems([dragItem]);
      textInputWidget.setValue('');
      $(iconDelete).click(function(){
        deleteChapter(draggableWidget, $(this).parent().text());
      });
    }});
})
