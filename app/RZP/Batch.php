<?php

namespace App\RZP;

class Batch extends Entity
{
    public function fetchBatchById($id)
    {
        $relativeUrl = 'batches/' .$id;

        return $this->request('GET', $relativeUrl);
    }

    public function fetchMultipleBatches($options = array())
    {
        $relativeUrl = 'batches';

        return $this->request('GET', $relativeUrl, $options);
    }

    public function uploadBatchFile($input)
    {
        $relativeUrl = 'batches';

        return $this->request('POST', $relativeUrl, $input);
    }

    public function downloadBatchFile($id)
    {
        $relativeUrl = 'batches/' .$id .'/download';

        return $this->request('GET', $relativeUrl);
    }

    public function retryBatchFile($id)
    {
        $relativeUrl = 'batches/' .$id .'/retry';

        return $this->request('POST', $relativeUrl);
    }
}
