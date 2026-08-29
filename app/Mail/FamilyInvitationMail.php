<?php

namespace App\Mail;

use App\Models\FamilyInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FamilyInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invitation;
    public $inviteUrl;
    public $familyName;
    public $inviterName;
    public $role;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(FamilyInvitation $invitation, $inviteUrl)
    {
        $this->invitation = $invitation;
        $this->inviteUrl = $inviteUrl;
        
        // Setup variables for the view
        $this->familyName = $invitation->family ? $invitation->family->name : 'HomeSync Finance';
        $this->inviterName = $invitation->inviter ? $invitation->inviter->name : '家長';
        
        $roleMap = [
            'parent' => '家長',
            'child' => '兒童',
            'member' => '成員',
            'observer' => '觀察者'
        ];
        $this->role = $roleMap[$invitation->role] ?? '成員';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('【HomeSync Finance】您被邀請加入「' . $this->familyName . '」家庭記帳！')
                    ->view('emails.family_invitation');
    }
}
