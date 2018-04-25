$(document).ready(function(){

	query('book','n',null);

});


$('#frequency').find('.dropdown-item').click(function() {

	$(this).closest('.dropdown').attr('data-val', $(this).data('frequency') );

	$('#frequency').find('button').text($(this).text() );


	updatePreferences( $(this).data('frequency'), $('#patnum').attr('data-val'), $('#pickup_location').attr('data-val'), "frequency" );



});


$('#pickup_location').find('.dropdown-item').click(function() {

	$(this).closest('.dropdown').attr('data-val', $(this).data('pickup') );

	$('#pickup_location').find('button').text( $(this).text());


	updatePreferences( $('#frequency').attr('data-val'), $('#patnum').attr('data-val'), $(this).data('pickup'), "pickup_location" );



});



$('#patron_id').find('.dropdown-item').click(function() {


	$(this).closest('.dropdown').attr('data-val', $(this).data('pat-id') );

	if($(this).data('pat-id') == null)
	{
		$('#patron_id').find('button').text('Login');
	}
	else
	{
		$('#patron_id').find('button').text( 'Hello, ' + $(this).text().toLowerCase());
	}
	query( $('#media').closest('.dropdown').attr('data-val'), $('#available').closest('.dropdown').attr('data-val'), $(this).data('pat-id') );


});



function query(mat, loc, pat) {

	if(pat == 'null') { pat = null }

console.log(mat + ' - ' + loc  + ' - ' +pat)


$('#og-list').empty();


	$.ajax({
  			method: "GET",
  			url: "readingHistoryJson.php?v=1.1",
 			data: { format: mat, available: loc, patron_record_num: pat  }
		})
  		.done(function( books_json ) {

			$.each(books_json, function( index, value ) {

				// remove check digit for Encore display
				var check = value['bib_record_num'].slice(0,-1);

				var encoreURL = 'http://encore.wblib.org/iii/encore/record/C__R' + value['bib_record_num'];
				var imgURL = 'http://www.syndetics.com/index.aspx?isbn=' + value['ident'] + '/MC.GIF&client=arfayetteville&type=xw10\" alt=\"\"';

				$('#og-list').append('<li><a href="' + encoreURL + '"><img src="' + imgURL + '" onload="checkCovers(this)" alt="" /></a><a href="' + encoreURL + '" class="details"><span class="title">' + value['title'] + '</span><span class="author">' + value['author'] + '</span></a></li>');

				/*img.onload = function() {
  					if (this.width > 1) {
  						$('#og-list').append('<li class="no-img"><a href="http://encore.wblib.org/iii/encore/record/C__Rb' + value['bib_record_num'] + '" class="details"><span class="title">' + value['title'] + '</span><span class="author">' + value['author'] + '</span></a></li>');
  					} else {
  						$('#og-list').append('<li class="no-img"><a href="http://encore.wblib.org/iii/encore/record/C__Rb' + value['bib_record_num'] + '"><span class="title">' + value['title'] + '</span><span class="author">' + value['author'] + '</span></a></li>');
  					}
  				}

				//img.src = 'http://images.amazon.com/images/P/' + value['ident'] + '.01.TZZZZZZZ.jpg';

				img.src = 'http://www.syndetics.com/index.aspx?isbn=' + value['ident'] + '/MC.GIF&client=arfayetteville&type=xw10\" alt=\"\"'; */

			});

		});

}

function updatePreferences(freq, patnum, pickup, pref) {


	console.log(freq + ' - ' + patnum + ' - ' + pickup + ' - ' + pref )

	$('#og-list').empty();


		$.ajax({
				method: "GET",
				url: "updatePreferences.php?v=1.1",
				data: { frequency: freq, patron_num: patnum, pickup_location: pickup, preference: pref  }
			})


}


function checkCovers(img) {

	if(img.width <= 1) {
		$(img).parent().parent().addClass("no-img");
		$(img).parent().next().removeClass("details");
		$(img).parent().remove();
	}

}