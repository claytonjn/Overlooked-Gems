$(document).ready(function(){

	query($('#patnum').attr('data-val'), null, null);

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

	if($(this).data('pat-id') == null) {
		$('#patron_id').find('button').text('Login');
	} else {
		$('#patron_id').find('button').text( 'Hello, ' + $(this).text().toLowerCase());
	}
	query( $('#media').closest('.dropdown').attr('data-val'), $('#available').closest('.dropdown').attr('data-val'), $(this).data('pat-id') );

});



function query(pnumber, filter, sort ) {

	console.log(pnumber + ' - ' + filter  + ' - ' + sort)

	$('#og-list').empty();

	$.ajax({
  			method: "GET",
  			url: "readingHistoryJson.php?v=1.1",
 			data: { pnumber: pnumber, filter: filter, sort: sort   }
		})
  		.done(function( books_json ) {

			$.each(books_json, function( index, value ) {

				var encoreURL = 'http://encore.wblib.org/iii/encore/record/C__R' + value['record_num'];
				var imgURL = 'http://www.syndetics.com/index.aspx?isbn=' + value['ident'] + '/MC.GIF&client=arfayetteville&type=xw10\" alt=\"\"';

				$('#og-list').append('<li><a href="' + encoreURL + '"><img src="' + imgURL + '" onload="checkCovers(this)" alt="" /></a><a href="' + encoreURL + '" class="details"><span class="title">' + value['title'] + '</span><span class="author">' + value['author'] + '</span></a><br><i class="mdi mdi-thumb-down-outline"></i>&nbsp;&nbsp;&nbsp;&nbsp;<i class="mdi mdi-thumb-up-outline"></i></li>');

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