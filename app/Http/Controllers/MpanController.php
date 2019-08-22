<?php


namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use Illuminate\Support\Facades\App;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class MpanController extends Controller
{
      const visaMpans= [
        '4604901005005823',
        '4604901005005856',
        '4604901005005880',
        '4604901005005914',
        '4604901005005971',
        '4604901005006003',
        '4604901005006037',
        '4604901005006060',
        '4604901005006094',
        '4604901005006128',
        '4604901005006151',
        '4604901005006185',
        '4604901005006219',
        '4604901005006243',
        '4604901005006276',
        '4604901005006300',
        '4604901005006334',
        '4604901005006367',
        '4604901005006391',
        '4604901005006425',
        '4604901005006458',
        '4604901005006482',
        '4604901005006516',
        '4604901005006540',
        '4604901005006573',
        '4604901005006607',
        '4604901005006631',
        '4604901005006664',
        '4604901005006698',
        '4604901005006722',
        '4604901005006755',
        '4604901005006789',
        '4604901005006813',
        '4604901005006847',
        '4604901005006870',
        '4604901005006904',
        '4604901005003364',
        '4604901005003216',
    ];

    const mastercardMpans = [
        '5122600005005813',
        '5122600005005847',
        '5122600005005870',
        '5122600005005904',
        '5122600005005961',
        '5122600005005995',
        '5122600005006027',
        '5122600005006050',
        '5122600005006084',
        '5122600005006118',
        '5122600005006142',
        '5122600005006175',
        '5122600005006209',
        '5122600005006233',
        '5122600005006266',
        '5122600005006290',
        '5122600005006324',
        '5122600005006357',
        '5122600005006381',
        '5122600005006415',
        '5122600005006449',
        '5122600005006472',
        '5122600005006506',
        '5122600005006530',
        '5122600005006563',
        '5122600005006597',
        '5122600005006621',
        '5122600005006654',
        '5122600005006688',
        '5122600005006712',
        '5122600005006746',
        '5122600005006779',
        '5122600005006803',
        '5122600005006837',
        '5122600005006860',
        '5122600005006894',
        '5122600005003354',
        '5122600005003206',
    ];

    const rupayMpans = [
        '6100020005005826',
        '6100020005005859',
        '6100020005005883',
        '6100020005005917',
        '6100020005005974',
        '6100020005006006',
        '6100020005006030',
        '6100020005006063',
        '6100020005006097',
        '6100020005006121',
        '6100020005006154',
        '6100020005006188',
        '6100020005006212',
        '6100020005006246',
        '6100020005006279',
        '6100020005006303',
        '6100020005006337',
        '6100020005006360',
        '6100020005006394',
        '6100020005006428',
        '6100020005006451',
        '6100020005006485',
        '6100020005006519',
        '6100020005006543',
        '6100020005006576',
        '6100020005006600',
        '6100020005006634',
        '6100020005006667',
        '6100020005006691',
        '6100020005006725',
        '6100020005006758',
        '6100020005006782',
        '6100020005006816',
        '6100020005006840',
        '6100020005006873',
        '6100020005006907',
        '6100020005003367',
        '6100020005003219',
    ];


    const networkMpansMap = [
       'visa'                => self::visaMpans,
       'mastercard'          => self::mastercardMpans,
       'rupay'               => self::rupayMpans,
    ];

    public function generateMpan()
    {
        $input = Request::all();

        $this->preProcessInput($input);

        $this->validateInputAndThrowExceptionIfApplicable($input);

        $formattedResponse = $this->getFormattedMpanResponse($input);

        return ApiResponse::json($formattedResponse);
    }

    private function preProcessInput(& $input)
    {
        $input['count'] = (int) $input['count'];

        $input['network'] = strtolower($input['network']);
    }

    private function validateInputAndThrowExceptionIfApplicable($input)
    {
        $app = App::getFacadeRoot();

        $mode = isset($app['rzp.mode']) === true ? $app['rzp.mode'] : Mode::LIVE;

        $count = $input['count'];

        $network = $input['network'];

        if ($mode !== Mode::TEST)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR,
                                          null,
                                          $input, 
                                          'Request allowed only in test mode');
        }

        if (isset($input['count']) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR,
                'network',
                $input,
                'Missing count field');

        }

        if ($count > 10)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR,
                                          'count',
                                          $input,
                                          'Count cannot be greater than 10');
        }

        if (isset($input['network']) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR,
                                          'network',
                                          $input,
                                          'Missing network field');
                
        }

        if (isset(self::networkMpansMap[$network]) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR,
                                          'network',
                                          $input,
                                          'Invalid network field');
            
        }

    }

    private function getFormattedMpanResponse($input)
    {
        $mpans =  $this->getMpansBelongingToNetwork($input['network'], $input['count']);

        $formattedMpanItems = array_map(function ($mpan) use ($input ) {
            return [
                    'mpan'      =>      $mpan,
                    'network'   =>      $input['network'],
                   ];
            }, $mpans);

        $response = [
            'entity'            =>      'collection',
            'count'             =>      $input['count'],
            'items'             =>      $formattedMpanItems,
        ];

        return $response;
    }

    private function getMpansBelongingToNetwork($network, $count)
    {
        $mpansBelongingToNetwork = self::networkMpansMap[$network];

        shuffle($mpansBelongingToNetwork);

        return array_slice($mpansBelongingToNetwork, 0, $count);
    }
}
