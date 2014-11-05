<?php
namespace Controller\Event
{
    class Index extends \Controller\Base
    {
        public function _run()
        {
            \Single\W('test',array(1,2,3));
        }
    }
}
