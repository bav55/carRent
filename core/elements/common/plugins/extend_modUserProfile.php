<?php
switch ($modx->event->name) {
    case "OnMODXInit":
        $map = array(
            'modUserProfile' => array(
                'fields' => array(
                    'company_id' => '',
                ),
                'fieldMeta' => array(
                    'company_id' => array(
                        'dbtype' => 'int',
                        'precision' => '6',
                        'phptype' => 'integer',
                        'null' => true,
                    ),
                ),
            ),
        );

        foreach ($map as $class => $data) {
            $modx->loadClass($class);

            foreach ($data as $tmp => $fields) {
                if ($tmp == 'fields') {
                    foreach ($fields as $field => $value) {
                        foreach (array('fields', 'fieldMeta', 'indexes') as $key) {
                            if (isset($data[$key][$field])) {
                                $modx->map[$class][$key][$field] = $data[$key][$field];
                            }
                        }
                    }
                } elseif ($tmp == 'composites' || $tmp == 'aggregates') {
                    foreach ($fields as $alias => $relation) {
                        if (!isset($modx->map[$class][$tmp][$alias])) {
                            $modx->map[$class][$tmp][$alias] = $relation;
                        }
                    }
                }
            }
        }
        break;
    
    case "OnUserFormPrerender":
        if (!isset($user) || $user->get('id') < 1) {
            return;
        }

        if (!$modx->getCount('modPlugin', array('name' => 'AjaxManager', 'disabled' => false)) && ($user->isMember('Агенты'))) {
            $data['company_id'] = $user->Profile->company_id;

            $modx->controller->addHtml("
                <script type='text/javascript'>
                    Ext.ComponentMgr.onAvailable('modx-user-tabs', function() {
                        this.on('beforerender', function() {
                            // Получаем колонки первой вкладки
                            var leftCol = this.items.items[0].items.items[0].items.items[0];
                            var rightCol = this.items.items[0].items.items[0].items.items[1];

                            // Добавляем новое поле в левую колонку 4ым по счёту полем (перед полем 'Email')
                            leftCol.items.insert(4, 'company_id', new Ext.form.TextField({
                                id: 'company_id',
                                name: 'company_id',
                                fieldLabel: 'ID компании (для агента)',
                                xtype: 'textfield',
                                anchor: '100%',
                                maxLength: 255,
                                value: '{$data['company_id']}',
                            }));
                        });
                    });
                </script>
            ");
        }
        break;
}