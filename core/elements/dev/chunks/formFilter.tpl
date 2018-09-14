<form class="form-inline" name="carsFilter" method="POST">
    <div class="form-group col-xs-12 col-md-2">
        <label class="sr-only" for="city">Город</label>
        <select class="form-control" name="city" id="city">
            {$_modx->getPlaceholder('cities')}
        </select>
    </div>
    <div class="form-group">
        <label class="sr-only" for="type">Тип автомобиля</label>
        <select class="form-control" name="type" id="type">
            {$_modx->getPlaceholder('types')}
        </select>
    </div>
    <div class="form-group">
        <label class="sr-only" for="mark_model">Марка и модель</label>
        <select class="form-control" name="mark_model" id="mark_model">
            {$_modx->getPlaceholder('mark_models')}
        </select>
    </div>
    <div class="form-group">
        <div class="input-group date" id="dpBegin">
            <input type="text" class="form-control" value="{$_modx->getPlaceholder('dateBegin')}"/>
            <span class="input-group-addon">
            <i class="glyphicon glyphicon-calendar"></i>
        </span>
        </div>
    </div>
    <div class="form-group">
        <div class="input-group date" id="dpEnd">
            <input type="text" class="form-control" value="{$_modx->getPlaceholder('dateEnd')}"/>
            <span class="input-group-addon">
            <i class="glyphicon glyphicon-calendar"></i>
        </span>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Поиск</button>
</form>

<script type="text/javascript">
    $(function () {
        // инициализация dpBegin и dpEnd для обработки периода времени
        $("#dpBegin").datetimepicker({
            locale: 'RU',
            format: 'DD.MM.YYYY',
        });
        $("#dpEnd").datetimepicker({
            locale: 'RU',
            format: 'DD.MM.YYYY',
            useCurrent: false
        });
        $("#dpBegin").on("dp.change", function (e) {
            $('#dpEnd').data("DateTimePicker").minDate(e.date);
        });
        $("#dpEnd").on("dp.change", function (e) {
            $('#dpBegin').data("DateTimePicker").maxDate(e.date);
        });
    });
</script>
