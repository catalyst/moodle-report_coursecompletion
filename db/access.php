<?php
$capabilities = array(
    'report/coursecompletion:viewreport' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'view',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),
 );
?>
