<?php

namespace App\RZP;

use Razorpay\Api\Errors\BadRequestError as BadRequestError;
use Razorpay\Api\Errors\ServerError as ServerError;

class Feature extends Entity
{
	public function getFeatures($entityType, $entityId)
	{
		$relativeUrl = $this->getEntityUrl().$entityType.'/'.$entityId;

		return $this->request('GET', $relativeUrl)->toArray();
	}

	public function setFeatures($params)
    {
        $relativeUrl = $this->getEntityUrl();

        return $this->request('POST', $relativeUrl, $params)->toArray();
    }

    public function deleteFeature($featureId)
    {
        $relativeUrl = $this->getEntityUrl().$featureId;

        return $this->request('DELETE', $relativeUrl)->toArray();
    }

}