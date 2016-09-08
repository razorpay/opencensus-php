<?php

namespace App\Session;

use App\Base;

class Entity extends Base\Entity
{
    protected $table = 'sessions';

    protected $fillable = array(
        'id',
        'payload',
        'ip_address',
        'user_agent',
        'last_activity',
        'user_id',
        'admin_id'
    );

    public function getAllSessionsForAdmin($id)
    {
        // return $this->where('admin_id', $id)->get();
        
        $data = \DB::table('sessions')
                    ->where('admin_id','=',$id)
                    ->orderby('last_activity', 'DESC')
                    ->get();

        return $data;
    }

    public function deleteAllOtherSessionsForAdmin($id, $currentSessionId)
    {
        $this->where('admin_id', $id)
             ->where('id', '!=', $currentSessionId)->delete();
    }

    public function deleteOneSessionForAdmin($sessionId)
    {
        $this->where('id', $sessionId)->delete();
    }
}
