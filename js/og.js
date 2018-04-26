$(document).ready(function(){

	query($('#pnumber').attr('data-val'), null, null);

});


$('#frequency').find('.dropdown-item').click(function() {

	$(this).closest('.dropdown').attr('data-val', $(this).data('frequency') );

	$('#frequency').find('button').text($(this).text() );

	updatePreferences( $(this).data('frequency'), $('#pnumber').attr('data-val'), $('#pickup_location').attr('data-val'), "frequency" );

});


$('#pickup_location').find('.dropdown-item').click(function() {

	$(this).closest('.dropdown').attr('data-val', $(this).data('pickup') );

	$('#pickup_location').find('button').text( $(this).text());

	updatePreferences( $('#frequency').attr('data-val'), $('#pnumber').attr('data-val'), $(this).data('pickup'), "pickup_location" );

});


$('#filter').find('.dropdown-item').click(function() {

	$('#og-list').html('<img src="https://upload.wikimedia.org/wikipedia/commons/b/b1/Loading_icon.gif">');

	$(this).closest('.dropdown').attr('data-val', $(this).data('filter') );

	$('#filter').find('button').text( $(this).text());

	query($('#pnumber').attr('data-val'), $('#filter').attr('data-val'), $('#sort').attr('data-val'));

});


$('#sort').find('.dropdown-item').click(function() {

	$('#og-list').html('<img src="https://upload.wikimedia.org/wikipedia/commons/b/b1/Loading_icon.gif">');

	$(this).closest('.dropdown').attr('data-val', $(this).data('sort') );

	$('#sort').find('button').text( $(this).text());

	query($('#pnumber').attr('data-val'), $('#filter').attr('data-val'), $('#sort').attr('data-val'));

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


function thumbsDisplay(pnumber, bib_record_metadata_id, rating) {
	if(rating == 0) {
		return '<i class="mdi mdi-thumb-down-outline" onclick="rate(' + pnumber + ', ' + bib_record_metadata_id + ', -1)"></i>&nbsp;&nbsp;&nbsp;&nbsp;<i class="mdi mdi-thumb-up-outline" onclick="rate(' + pnumber + ', ' + bib_record_metadata_id + ', 1)"></i>'
	} else if(rating == 1) {
		return '<i class="mdi mdi-thumb-down-outline" onclick="rate(' + pnumber + ', ' + bib_record_metadata_id + ', -1)"></i>&nbsp;&nbsp;&nbsp;&nbsp;<i class="mdi mdi-thumb-up" onclick="rate(' + pnumber + ', ' + bib_record_metadata_id + ', 0)"></i>'
	} else if(rating == -1) {
		return '<i class="mdi mdi-thumb-down" onclick="rate(' + pnumber + ', ' + bib_record_metadata_id + ', 0)"></i>&nbsp;&nbsp;&nbsp;&nbsp;<i class="mdi mdi-thumb-up-outline" onclick="rate(' + pnumber + ', ' + bib_record_metadata_id + ', 1)"></i>'
	}
}


function query(pnumber, filter, sort ) {

	console.log(pnumber + ' - ' + filter  + ' - ' + sort)

	$.ajax({
  			method: "GET",
  			url: "readingHistoryJson.php?v=1.1",
 			data: { pnumber: pnumber, filter: filter, sort: sort   }
		})
  		.done(function( books_json ) {

			$('#og-list').empty();

			$.each(books_json, function( index, value ) {

				var encoreURL = 'http://encore.wblib.org/iii/encore/record/C__R' + value['record_num'];
				var imgURL = 'http://www.syndetics.com/index.aspx?isbn=' + value['ident'] + '/MC.GIF&client=arfayetteville&type=xw10\" alt=\"\"';
				var thumbs = thumbsDisplay(pnumber, value['bib_record_metadata_id'], value['rating']);

				$('#og-list').append('<li id="' + value['bib_record_metadata_id'] + '"><a href="' + encoreURL + '"><img src="' + imgURL + '" onload="checkCovers(this)" alt="" /></a><a href="' + encoreURL + '" class="details"><span class="title">' + value['title'] + '</span><span class="author">' + value['author'] + '</span></a><br><div class="thumbs">' + thumbs + '</div></li>');

			});

		});

}

function updatePreferences(freq, pnumber, pickup, pref) {

	console.log(freq + ' - ' + pnumber + ' - ' + pickup + ' - ' + pref )

	$('#og-list').empty();

		$.ajax({
				method: "GET",
				url: "updatePreferences.php?v=1.1",
				data: { frequency: freq, patron_num: pnumber, pickup_location: pickup, preference: pref  }
			})

}


function rate(pnumber, bib_record_metadata_id, rating) {

	$.ajax({
		method: "GET",
		url: "rateTitle.php?v=1.1",
		data: { pnumber: pnumber, bib_record_metadata_id: bib_record_metadata_id, rating: rating  }
	})
	.done(function( rate_result ) {

		$('#' + bib_record_metadata_id + ' .thumbs').html(thumbsDisplay(pnumber, bib_record_metadata_id, rating));

	});

}


function checkCovers(img) {

	if(img.width <= 1) {
		$(img).parent().parent().addClass("no-img");
		$(img).parent().next().removeClass("details");
		$(img).parent().remove();
	}

}