<?php

namespace App\RZP;

use Razorpay\Api\Errors\BadRequestError as BadRequestError;
use Razorpay\Api\Errors\ServerError as ServerError;

class Feature extends Entity
{
	public function getFeatures($entityId)
	{
		$relativeUrl = $this->getEntityUrl().$entityId;

		return $this->request('GET', $relativeUrl)->toArray();
	}

	public function setFeatures($params)
    {
        $relativeUrl = $this->getEntityUrl();

        return $this->request('POST', $relativeUrl, $params)->toArray();
    }

    public function deleteFeature($entityId, $featureName)
    {
        $relativeUrl = $this->getEntityUrl().$entityId.'/'.$featureName;

        return $this->request('DELETE', $relativeUrl)->toArray();
    }

}
