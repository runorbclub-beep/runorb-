<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected $number;
    protected $emailSubject;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($number, $emailSubject)
    {
        $this->number = $number;
        $this->emailSubject = $emailSubject;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.verification_code')
            ->subject($this->emailSubject)
            ->with([
                'number' => $this->number
            ]);
//        return $this->from('example@example.com这里填邮箱','这里添写发送人名称，不然会报错的')
//            ->view('emails.orders.shipped');
    }
}
