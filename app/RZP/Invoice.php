<?php

namespace App\RZP;

use Razorpay\Api\Request as ApiRequest;
use Auth;

class Invoice extends Entity
{
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
    protected function appendUserId()
    {
        $role = Auth::user()->getUserRoleWithCurrentMerchant();
        if ($role === 'sellerapp')
        {
            ApiRequest::addHeader('X-USER', Auth::user()->getAuthIdentifier());
        }
    }

    public function create($params = [])
    {
        $this->appendUserId();
        return parent::create($params);
    }

    public function edit($id, $params = [])
    {
        $this->appendUserId();
        $entityUrl = $this->getEntityUrl().$id;
        return $this->request('PATCH', $entityUrl, $params);
    }

    public function all($options = [])
    {
        $this->appendUserId();
        return parent::all($options);
    }

    public function delete($id)
    {
        $this->appendUserId();
        $entityUrl = $this->getEntityUrl().$id;
        return $this->request('DELETE', $entityUrl);
    }

    public function fetch($id)
    {
        $this->appendUserId();
        return parent::fetch($id);
    }

    public function sendNotification($id, $medium)
    {
        $relativeUrl = 'invoices/' . $id . '/notify/' . $medium;
        $this->appendUserId();
        return $this->request('POST', $relativeUrl, []);
    }

    public function markAsIssued($id)
    {
        $relativeUrl = 'invoices/' . $id . '/issue';
        $this->appendUserId();
        return $this->request('POST', $relativeUrl, []);
    }

    public function markAsExpired($id)
    {
        $relativeUrl = 'invoices/' . $id . '/expire';
        $this->appendUserId();
        return $this->request('POST', $relativeUrl, []);
    }
}
