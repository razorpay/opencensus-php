<?php

namespace RZP\Models\Card\IIN\Import;

use App;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Card\IIN;
use RZP\Models\Card\Network;

/**
 * This class is called by the service function with the input data.
 * The handles the rest of processing.
 */
class XLSImporter
{
    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = \Trace::getFacadeRoot();
    }

    /**
     * This is the main function.
     *
     * @param array $input    the post data
     *
     * @return array $input   contains the duplicates and db conflicts
     * @throws Exception\BadRequestException
     */
    public function import($input)
    {
        if (isset($input['network']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Please pass network name as input for given file');
        }

        // Extracts and returns the columns and data
        $ret = (new XLSFileHandler)->getData($input);

        $formattedData = (new Formatter)->formatData($ret['columns'], $ret['data']);

        $dataCleaner = new DataCleaner();
        $cleaned = $dataCleaner->parse($input['network'], $formattedData);
        $duplicates = $dataCleaner->getDuplicateEntries();
        $conflicts = $dataCleaner->getDBConflicts();
        $networkCheckFails = $dataCleaner->getNetworkCheckFails();

        $this->enterIntoDB($cleaned);
        $this->updateIntoDB($conflicts);

        $successCount = count($cleaned);

        return array(
            'duplicates'     => $duplicates,
            'db_conflicts'   => $conflicts,
            'network_errors' => $networkCheckFails,
            'success'        => $successCount,
        );
    }

    public function importWithoutNetwork($file)
    {
        $ret = (new XLSFileHandler)->getCsvData($file);

        // Header of Csv data
        $ret['columns'] = array(
            IIN\Entity::IIN,
            IIN\Entity::NETWORK,
            IIN\Entity::ISSUER_NAME,
            IIN\Entity::TYPE,
            IIN\Entity::CATEGORY,
            'country_full_name',
            IIN\Entity::COUNTRY,
            'ISO_code_2',
            'ISO numeric code');

        // Network Mapping
        $networkMapping = array(
            'JCB'                       => Network::JCB,
            'MASTERCARD'                => Network::MC,
            'MasterCard'                => Network::MC,
            'RuPay'                     => Network::RUPAY,
            'RUPAY'                     => Network::RUPAY,
            'CHINA UNION PAY'           => Network::UNP,
            'Maestro'                   => Network::MAES,
            'MAESTRO'                   => Network::MAES,
            'Visa'                      => Network::VISA,
            'VISA'                      => Network::VISA,
            'DISCOVER'                  => Network::DISC,
            'AMERICAN EXPRESS'          => Network::AMEX,
            'DINERS CLUB INTERNATIONAL' => Network::DICL,
            'unknown'                   => Network::UNKNOWN,
        );

        $formatter = (new Formatter);
        $formattedData = $formatter->formatDataNew(
            $ret['columns'],
            $ret['data'],
            $networkMapping,
            array('country_full_name', 'ISO_code_2', 'ISO numeric code')
        );

        $errArray = array();

        foreach (array_chunk($formattedData, 1) as $chunks)
        {
            try
            {
                $this->enterIntoDB($chunks, 1);
            }
            catch (\Exception $e)
            {
                \Log::info('exception occurred', ['message' => $e->getMessage(), 'code' => $e->getCode()]);
                $msg = $e->getMessage();
                $msg = explode('Duplicate entry', $msg)[1];

                $failedIin = explode('for key', $msg)[0];

                $this->trace->info(
                    TraceCode::IIN_INSERT_FAILED,
                    ['iin' => $failedIin]);

                array_push($errArray, $failedIin);
            }
        }

        return array(
            'Failed Iin'        => $errArray,
            'Credit Card'       => $formatter->creditCard,
            'Debit Card'        => $formatter->debitCard,
            'Other Card'        => $formatter->otherCardType,
            'Unknown Network'   => $formatter->unknownNetworkType,
            'Failed Count'      => count($errArray),
            'Sucessful Entries' => count($formattedData) - count($errArray),
        );
    }
    /**
     * This enter the unique entries into the database.
     *
     * The input array should be associative and contian uniqe entries.
     *
     * @param array $cleaned        the input entries.
     */
    protected function enterIntoDB($cleaned, $chunkSize = 5000)
    {
        // Too many entries crashes the sql query
        foreach (array_chunk($cleaned, $chunkSize) as $chunks)
        {
            $iins = new Base\PublicCollection;

            foreach ($chunks as & $chunk)
            {
                $iinEntity = (new IIN\Entity)->build($chunk);

                $iins->push($iinEntity);
            }

            $this->app['repo']->saveOrFailCollection($iins);
        }
    }

    protected function updateIntoDB(& $conflicts)
    {
        $columns = array(IIN\Entity::TYPE, IIN\Entity::COUNTRY, IIN\Entity::ISSUER);

        foreach ($conflicts as $iinId => $entry)
        {
            list($input, $conflict, $diff) =
                $this->getInputForIinUpdate($entry['db_entry'], $entry['file_entry'], $columns);

            if (($conflict === false) and
                (empty($input) === false))
            {
                $entity = $this->app['repo']->iin->find($iinId);

                $entity->edit($input);

                $this->app['repo']->saveOrFail($entity);
            }

            if ($conflict === false)
            {
                unset($conflicts[$iinId]);
            }
            else
            {
                $conflicts[$iinId] = $diff;
            }
        }
    }

    protected function getInputForIinUpdate($dbEntry, $fileEntry, $columns)
    {
        unset($fileEntry[IIN\Entity::IIN]);
        unset($fileEntry[IIN\Entity::NETWORK]);

        $conflict = false;

        $diff = [];

        foreach ($columns as $column)
        {
            if (empty($fileEntry[$column]) === true)
            {
                unset($fileEntry[$column]);

                continue;
            }

            if (empty($dbEntry[$column]) === false)
            {
                if ($dbEntry[$column] !== $fileEntry[$column])
                {
                    $conflict = true;

                    $diff[$column] = ['db' => $dbEntry[$column], 'file' => $fileEntry[$column]];
                }

                unset($fileEntry[$column]);
            }
        }

        return [$fileEntry, $conflict, $diff];
    }
}
