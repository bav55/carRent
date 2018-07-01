<!doctype html>
<html lang="en">
<head>
    <title>[[*pagetitle]] - [[++site_name]]</title>
    <base href="[[!++site_url]]" />
    <meta charset="[[++modx_charset]]" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
	<!-- bootstrap -->
	<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!--[if lt IE 9]>
	<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
	<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->

	<!-- /bootstrap -->
	{$_modx->runSnippet('!ajax_call')}
    {set $ctx = $_modx->resource.context_key}
    {set $company = $_modx->user.company_id}
    {set $user = $_modx->user.id}

	{if $ctx == 'dev'}
		<script src="[[++assets_url]]components/themebootstrap/js/jquery.min.js"></script>
	    <script src="[[++assets_url]][[*context_key]]/js/timeline.js"></script>
        <script src="[[++assets_url]][[*context_key]]/js/timeline-locales.js"></script>
		<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
		<script src="[[++assets_url]]components/themebootstrap/js/bootstrap.min.js"></script>
		<link rel="stylesheet" type="text/css" media="screen and (min-device-width: 740px)" href="[[++assets_url]]components/themebootstrap/css/bootstrap.min.css">
		<link rel="stylesheet" type="text/css" media="screen and (min-device-width: 740px)" href="[[++assets_url]]components/themebootstrap/css/add.css">
        <link rel="stylesheet" type="text/css" media="screen and (min-device-width: 740px)" href="[[++assets_url]][[*context_key]]/css/timeline.css?v=20180618">
    {/if}
    {if $ctx == 'web'}
		<script src="[[++assets_url]]components/themebootstrap/js/jquery.min.js"></script>
		<script src="[[++assets_url]][[*context_key]]/js/timeline.js"></script>
		<script src="[[++assets_url]][[*context_key]]/js/timeline-locales.js"></script>
		<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
		<script src="[[++assets_url]]components/themebootstrap/js/bootstrap.min.js"></script>
		<link rel="stylesheet" type="text/css" media="screen and (min-device-width: 740px)" href="[[++assets_url]]components/themebootstrap/css/bootstrap.min.css">
		<link rel="stylesheet" type="text/css" media="screen and (min-device-width: 740px)" href="[[++assets_url]]components/themebootstrap/css/add.css">
        <link rel="stylesheet" type="text/css" media="screen and (min-device-width: 740px)" href="[[++assets_url]][[*context_key]]/css/timeline.css?v=20180618">
    {/if}

</head>
<body onload="drawVisualization();">
<div class="container">
    <section>
        <h1>
			{if $_modx->resource.longtitle != ''}
				{$_modx->resource.longtitle}
			{else}
				{$_modx->resource.pagetitle}
			{/if}
		</h1>
		<div class="userInfo">
			<span class="text">Пользователь: </span><span class="value">{$_modx->user.fullname} (ID: {$_modx->user.id})</span><br>
			<span class="text">Компания-агент (ID): </span><span class="value">{$_modx->user.company_id}</span>
		</div>
		<div id="carsTimeLine">
		
		</div>
        {$_modx->resource.content}
		<div id="info"></div>
    </section>
</div>
<footer class="disclaimer">
    <p>{include "file:$ctx/chunks/footer.tpl"}</p>
</footer>
<script>
function getSelectedRow() {
	var row = undefined;
	var sel = timeline.getSelection();
	if (sel.length) {
		if (sel[0].row != undefined) {
			row = sel[0].row;
		}
	}
	return row;
}

function drawVisualization() {
            // Create a JSON data table
            {$_modx->runSnippet('!getCarsBooking', ['company_id' => $company, 'user_id' => $user])}
            data = {$_modx->getPlaceholder('carsBooking')};

            for (var i in data) {
                data[i].start = new Date(data[i].start);
                data[i].end = new Date(data[i].end);
            }

            // specify options
            var options = {
                'width':  '100%',
                'height': '300px',
                'editable': true,   // enable dragging and editing events
                'style': 'range',
                'locale': 'ru',
            };

            // Instantiate our timeline object.
            timeline = new links.Timeline(document.getElementById('carsTimeLine'), options);

			function onChanged(properties) {
              var row = getSelectedRow();
				if (row != undefined) {
					// request approval from the user.
					// You can choose your own approval mechanism here, for example
					// send data to a server which responds with approved/denied
					var approve = confirm("Are you sure you want to move the event?");

					if (approve)  {
                        $('#carsTimeLine').addClass('disabledbutton');
                        var changedBooking = timeline.getItem(row);
                        
                        $.post(document.location.href, 
						{
							'dateBegin':	changedBooking['start'],
							'dateEnd':		changedBooking['end'],
							'task_id':		changedBooking['pf_task_id'],
							'company_id':	{$_modx->user.company_id},
							'user_id':		{$_modx->user.id},
							'action':		'modifyBooking',
							'id':			changedBooking['id']
						}, 
						function(data) {
                            $('#carsTimeLine').removeClass('disabledbutton');
                        });
					} else {
						// new date NOT approved. cancel the change
						timeline.cancelChange();
						document.getElementById("info").innerHTML += "change of event " + row + " cancelled<br>";
					}
				}
            }
			function onDelete(properties) {
                // retrieve the row to be deleted
                var row = getSelectedRow();

                if (row != undefined) {
                    // request approval from the user.
                    // You can choose your own approval mechanism here, for example
                    // send data to a server which responds with approved/denied
                    var approve = confirm("Are you sure you want to delete the event?");
                    if (approve)  {
                        document.getElementById("info").innerHTML += "event " + row + " deleted<br>";
                    } else {
                        // new date NOT approved. cancel the change
                        timeline.cancelDelete();
                        document.getElementById("info").innerHTML += "deleting event " + row + " cancelled<br>";
                    }
			
                }
            }
			function onAdd(properties) {
				var row = getSelectedRow();
				if (row != undefined) {
					// request approval from the user.
					// You can choose your own approval mechanism here, for example
					// send data to a server which responds with approved/denied
					var newBooking = timeline.getItem(row);
					var carOptions = $("#selectCar option");
					carOptions.each(function(indx, element){
						if ($(this).text().toLowerCase() == newBooking['group'].toLowerCase()){
							$(this).attr("selected", "selected");
						}
					});
					$('#dateBegin').val(newBooking['start']);
					$('#dateEnd').val(newBooking['end']);
					$("#modalForm").modal('show');
					$('button[name="booking_btn"]').on('click', function(e){
						if($('#selectClient').val() != -1){
							$('input[name="company_id"]').val({$_modx->user.company_id});
							$('input[name="user_id"]').val({$_modx->user.id});
							$('input[name="action"]').val('createBooking');
                            var data2 = $('form[name="form_add_booking"]').serialize();
                            timeline.cancelAdd(); //отменяем сейчас добавление. Данные остались, сохраним чуть позже.
							$.post(document.location.href, data2, function(data_) {
                                location.reload();
								$("#modalForm").modal('hide');
							});


                            // Не даем ссылке кликнуться - чтобы не перезагружалась страница
                            return false;
						}

					});
                }
			}
            // attach an event listener using the links events handler
			links.events.addListener(timeline, 'changed', onChanged);
			links.events.addListener(timeline, 'delete', onDelete);
			links.events.addListener(timeline, 'add', onAdd);
            // Draw our timeline with the created data and options
            timeline.draw(data);
}

</script>
{$_modx->runSnippet('!getData4Booking', ['company_id' => $company, 'user_id' => $user])}
<div id="modalForm" class="modal fade">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h4 class="modal-title">Новое бронирование автомобиля</h4>
      </div>
      <div class="modal-body">
		{include "file:$ctx/chunks/form_add_booking.tpl"}
      </div>
      <!-- Футер модального окна -->
      <div class="modal-footer">
        <button type="button" name="cancel_btn_booking" class="btn btn-default" data-dismiss="modal">Отменить</button>
        <button type="button" name="booking_btn" class="btn btn-primary">Забронировать</button>
      </div>
    </div>
  </div>
</div>
<script>
$("button[name='cancel_btn_booking']").click(function(){

});
$("button[name='booking_btn']").click(function(){

});
</script>					
 
</body>
</html>
