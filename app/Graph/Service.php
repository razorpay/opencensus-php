<?php

namespace App\Graph;

use Auth;
use Request;
use App\Base;
use App\Admin\GraphRequestAny;

class Service extends Base\Service
{
    public function handleGraphqlRequest($data): array
    {
        $merchantIdInHeader = Request::header('x-dashboard-merchant-id');

        $user = Auth::guard('user')->user();

        if (($merchantIdInHeader !== null) and
            ($merchantIdInHeader !== 'null') and
            ($user !== null))
        {
            $merchantInSession = $user
                ->merchants
                ->where('id', $merchantIdInHeader)
                ->first();

            if (empty($merchantInSession) === true)
            {
                $error = $this->getErrorResponse();

                return [$error, []];
            }
        }

        $request = new GraphRequestAny($data);

        return $request->send();
    }

    private function getErrorResponse(): array
    {
        $baseAppUrl = config('app.url');

        return [
            'errors'    => [
                [
                    'message'   => 'Unauthorized Access',
                    'extensions'    => [
                        'code'          => 'UNAUTHENTICATED',
                        'url'           => $baseAppUrl . '/user/signin',
                        'status'        => 401,
                        'statusText'    => 'Unauthorized',
                    ]
                ]
            ],

            'data'      => null,
        ];
    }
}
