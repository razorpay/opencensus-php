<?php
namespace RZP\Service\Response;

class ApachePinotQueryResponse
{
    private $data = null;

    public  $count;

    public function __construct(array $response)
    {

        $this->count = $response["numRowsResultSet"];

        if ($this->count > 0)
        {
            $columnNames = $response["resultTable"]["dataSchema"]["columnNames"];

            $columnsCount = count($columnNames);

            $rows = $response["resultTable"]["rows"];

            $rowIndex = 0;

            foreach ($rows as $row)
            {
                for ($i = 0; $i < $columnsCount; $i++)
                {
                    $this->data[$rowIndex][$columnNames[$i]] = $row[$i];
                }

                $rowIndex++;
            }
        }
    }

    public function getResponse()
    {
        return $this->data;
    }
}
