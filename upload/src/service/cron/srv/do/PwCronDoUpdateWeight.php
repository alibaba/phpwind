<?php

Wind::import('SRV:cron.srv.base.AbstractCronBase');

class PwCronDoUpdateWeight extends AbstractCronBase{
	
	public function run($cronId) {
            Wekit::load('native.srv.PwDynamicService')->weightCron();
	}
        
}
?>