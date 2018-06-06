<?php
/**
 * @var modX $modx
 */

/* don't execute if in the Manager */
if ($modx->context->get('key') == 'mgr') {
    return;
}

switch ($_SERVER['HTTP_HOST']) {
    case 'dev.carrent.local':
        $modx->switchContext('dev');
        break;
    case 'carrent.local':
        break;
    default:
        $modx->log(modX::LOG_LEVEL_ERROR, 'Check this plugin! May be your headache coming from here.');
        break;
}
return;