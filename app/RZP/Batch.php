<?php

namespace App\RZP;

class Batch extends Entity
{
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
