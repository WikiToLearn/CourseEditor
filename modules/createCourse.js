$(function () {
  $('input[name="wpname"]').blur(function(){
    var courseTitle = $(this).val();
    if($.trim(courseTitle).length !== 0){
      var cmtitle = 'Category:' + $('input[name="wptopic"]').val().trim();
      var api = new mw.Api();
      api.get( {
        action: 'query',
        list: 'categorymembers',
        cmtitle: cmtitle,
        cmnamespace: '2800' //NS_COURSE
      } ).done( function ( data ) {
        if(data.query.categorymembers.length !== 0){
          $('#mw-input-wpname').after('<br><div class="alert alert-warning alert-dismissible" id="alert" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
          $('#alert').append('Ehi, ci sono dei corsi dello stesso argomento! <br>Che ne diresti di modificarne uno al posto di crearlo nuovamente?<br>Se sei sicuro di voler continuare, ignora questo messaggio ma assicurati di associare una keyword al tuo corso, altrimenti verrà usata una keyword random.<br>');
          for (var page of data.query.categorymembers) {
            $('#alert').append('<a class="alert-link" href="/' + page.title + '">' + page.title + '</a><br>');
          }
        }
      } );
    }
  });
})
