<?php
class Mailer{
    public function mail($content){
        echo "邮件内容为: ". $content;
    }
}

class UserReg{
    public $mailer ;
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;

    }

    public function reg(){
        $this->mailer->mail();
    }
}

make(UserReg::class);