<?php


namespace RZP\Models\BankingAccountStatement\StatementGenerator;


class RBLStatementGenerator extends StatementGeneratorStrategy
{

    public function pdf()
    {
        # this is where after you have the account number, you will have to get the rest of the details, and create the PDF?
        # actually, PDF generation, the difference is only in the PDF template right?, then rest all should go in the parent class?, right ...
        # same for the CSV and XLSX stuff, the base data should be the same...for all of them ....
        #
        return $this->account_number;
    }

    public function csv()
    {
        // TODO: Implement csv() method.
    }

    public function xlsx()
    {
        // TODO: Implement xlsx() method.
    }
}
