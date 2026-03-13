<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Traits\SupportTicketManager;

class SupportTicketController extends Controller
{
    use SupportTicketManager;

    public function __construct()
    {
        parent::__construct();
        $this->userType = 'admin';
        $this->column = 'admin_id';
        $this->user = auth()->guard('admin')->user();
        $this->check_permission("View - Support Ticket");
    }

    public static function checkPermission($user, $scope){
        if($user->id == 1 || $user->is_superadmin){
            return true;
        }
        if( $scope == 'Pending Ticket' && $user->can("View - Pending Ticket")){
            return true;
        }
        if( $scope == 'Closed Ticket' && $user->can("View - Closed Ticket")){
            return true;
        }
        if( $scope == 'Answered Ticket' && $user->can("View - Answered Ticket")){
            return true;
        }
        if( $scope == 'All Ticket' && $user->can("View - All Ticket")){
            return true;
        }
    }

    public function tickets()
    {
        $this->checkPermission($this->user, 'All Ticket');
        $pageTitle = 'Support Tickets';
        $items = SupportTicket::searchable(['name', 'subject', 'ticket'])->orderBy('id', 'desc')->with('user')->paginate(getPaginate());

        return view('admin.support.tickets', compact('items', 'pageTitle'));
    }

    public function pendingTicket()
    {
        $this->checkPermission($this->user, 'Pending Ticket');
        $pageTitle = 'Pending Tickets';
        $items = SupportTicket::searchable(['name', 'subject', 'ticket'])->pending()->orderBy('id', 'desc')->with('user')->paginate(getPaginate());

        return view('admin.support.tickets', compact('items', 'pageTitle'));
    }

    public function closedTicket()
    {
        $this->checkPermission($this->user, 'Closed Ticket');
        $pageTitle = 'Closed Tickets';
        $items = SupportTicket::searchable(['name', 'subject', 'ticket'])->closed()->orderBy('id', 'desc')->with('user')->paginate(getPaginate());
        
        return view('admin.support.tickets', compact('items', 'pageTitle'));
    }
    
    public function answeredTicket()
    {
        $this->checkPermission($this->user, 'Answered Ticket');
        $pageTitle = 'Answered Tickets';
        $items = SupportTicket::searchable(['name', 'subject', 'ticket'])->orderBy('id', 'desc')->with('user')->answered()->paginate(getPaginate());

        return view('admin.support.tickets', compact('items', 'pageTitle'));
    }

    public function ticketReply($id)
    {
        $this->check_permission('View - Ticket Details');
        $ticket = SupportTicket::with('user')->where('id', $id)->firstOrFail();
        $pageTitle = 'Reply Ticket';
        $messages = SupportMessage::with('ticket', 'admin', 'attachments')->where('support_ticket_id', $ticket->id)->orderBy('id', 'desc')->get();

        return view('admin.support.reply', compact('ticket', 'messages', 'pageTitle'));
    }

    public function ticketDelete($id)
    {
        $this->check_permission('View - Ticket Details');
        $message = SupportMessage::findOrFail($id);
        $path = getFilePath('ticket');
        if ($message->attachments()->count() > 0) {
            foreach ($message->attachments as $attachment) {
                fileManager()->removeFile($path.'/'.$attachment->attachment);
                $attachment->delete();
            }
        }
        $message->delete();
        $notify[] = ['success', 'Support ticket deleted successfully'];

        return back()->withNotify($notify);
    }
}
