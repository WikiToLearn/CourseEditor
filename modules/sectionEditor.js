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
* Drag/drop items
*
* @param {Object} [config] Configuration options
*/
function DraggableItemWidget( config ) {
  // Configuration initialization
  config = config || {};

  // Parent constructor
  DraggableItemWidget.parent.call( this, config );

  // Mixin constructors
  OO.ui.mixin.DraggableElement.call( this, config );
}

/* Setup */
OO.inheritClass( DraggableItemWidget, OO.ui.DecoratedOptionWidget );
OO.mixinClass( DraggableItemWidget, OO.ui.mixin.DraggableElement );

$(function () {
  var dragChapters = [];
  var chapters = $("li[class='chapterItem']");
  $.each(chapters, function(key, value){
    // Create an icon.
    var removeIcon = new OO.ui.IconWidget( {
      icon: 'remove',
      flag: 'destructive',
      iconTitle: 'Remove'
    } );
    $(value).append("&nbsp;&nbsp;");
    $(value).append(removeIcon.$element);
    removeIcon.toggle(false);
    $(value).mouseover(function(){
      removeIcon.toggle(true);
    });
    $(value).mouseout(function(){
      removeIcon.toggle(false);
    });

    dragChapters.push(new DraggableItemWidget( {
      data: 'item' + key,
      icon: 'tag',
      label: 'Item ' + key
    } ));
  });

  var fieldDrag = new OO.ui.FieldLayout(
    new DraggableGroupWidget( {
      items: dragChapters
    } ),
    {
      label: 'DraggableGroupWidget (vertical)\u200E',
      align: 'top'
    }
  );
  $('div[id=chaptersList]').append(fieldDrag.$element);
})
