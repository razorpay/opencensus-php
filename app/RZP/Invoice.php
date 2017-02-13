<?php

namespace App\RZP;

use Auth;

class Invoice extends Entity
{
    public function create($params = [])
    {
        $this->appendUserId($params);
        return parent::create($params);
    }

    public function edit($id, $params = [])
    {
        $this->appendUserId($params);
        $entityUrl = $this->getEntityUrl().$id;
        return $this->request('PATCH', $entityUrl, $params);
    }

    public function all($options = [])
    {
        $this->appendUserId($options);
        return parent::all($options);
    }

    public function delete($id)
    {
        $entityUrl = $this->getEntityUrl().$id;
        return $this->request('DELETE', $entityUrl);
    }

    /**
     * The API decides to return only a subset
     * of invoices if the role of a user
     * = sellerapp. However, it decides to
     * do that if the user_id is present.
     *
     * So we don't send the user id for non-sellerapp
     * roles
     * @param  array  $params
     * @return array  $params
     */
    protected function appendUserId(array &$params)
    {
        $role = Auth::user()->getUserRoleWithCurrentMerchant();
        if ($role === 'sellerapp')
        {
            $params['user_id'] = Auth::user()->getAuthIdentifier();
        }
    }

    public function fetch($id)
    {
        // We don't send the user_id here
        // on the assumption that having the
        // id = having read rights on the same
        return parent::fetch($id);
    }

    public function sendNotification($id, $medium)
    {
        $relativeUrl = 'invoices/' . $id . '/notify/' . $medium;

        return $this->request('POST', $relativeUrl, []);
    }

    public function markAsIssued($id)
    {
        $relativeUrl = 'invoices/' . $id . '/issue';

        return $this->request('POST', $relativeUrl, []);
    }

    public function markAsExpired($id)
    {
        $relativeUrl = 'invoices/' . $id . '/expire';

        return $this->request('POST', $relativeUrl, []);
    }
}
