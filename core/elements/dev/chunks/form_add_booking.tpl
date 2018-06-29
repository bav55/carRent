<form name="form_add_booking">
  <div class="form-group">
    <label for="selectClient">Клиент: </label>
	    <select class="form-control" id="selectClient" name="selectClient">
			{$_modx->getPlaceholder('optionClient')}
      </select>
  </div>
  <div class="form-group">
    <label for="selectCar">Автомобиль: </label>
	    <select class="form-control" name="selectCar" id="selectCar">
        {$_modx->getPlaceholder('optionCar')}
      </select>
  </div>
  <div class="form-group">
    <label class="control-label" for="dateBegin">Дата начала: </label>
    <input type="text" class="form-control" name="dateBegin" id="dateBegin" value="">
	<input type="hidden" name="action" value="">
	<input type="hidden" name="company_id" value="">
	<input type="hidden" name="user_id" value="">
	<input type="hidden" name="id" value="">
  </div>
  <div class="form-group">
    <label class="control-label" for="dateEnd">Дата окончания: </label>
    <input type="text" class="form-control" name="dateEnd" id="dateEnd" value="">
  </div>
</form>