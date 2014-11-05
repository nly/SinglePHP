<?php
namespace Controller\Event
{
    class Prize  extends \Controller\Base implements \Controller\Inter\Test
    {
        public function _run()
        {
            $this->add();
            $this->del();
            $this->modify();
        }

        public function add()
        {
            echo 'add';
        }

        public function del ()
        {
            echo 'del';
        }

        public function modify()
        {
            echo 'modify';
        }
    }
}
