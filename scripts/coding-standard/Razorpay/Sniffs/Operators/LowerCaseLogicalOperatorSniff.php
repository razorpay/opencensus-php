<?php

class Razorpay_Sniffs_Operators_LowerCaseLogicalOperatorSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
                T_LOGICAL_AND,
                T_LOGICAL_OR,
               );
    }//end register()

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The current file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $replacements = array(
            'and', 'or'
        );

        $operator = strtolower($tokens[$stackPtr]['content']);

        if (in_array($operator, $replacements) === false)
        {
            return;
        }

        $operatorName = $tokens[$stackPtr]['content'];

        if (strtolower($operatorName) !== $operatorName)
        {
            if (strtoupper($operatorName) === $operatorName)
            {
                $phpcsFile->recordMetric($stackPtr, 'Operator name case', 'upper');
            }
            else
            {
                $phpcsFile->recordMetric($stackPtr, 'Operator name case', 'mixed');
            }

            $error = 'Logical Operators must be lowercase; expected "%s" but found "%s"';
            $data  = array(
                strtolower($operatorName),
                $operatorName,
            );

            $phpcsFile->addError($error, $stackPtr, 'LogicalOperatorNotLowerCase', $data);
        }
        else
        {
            $phpcsFile->recordMetric($stackPtr, 'Operator name case', 'upper');
        }

        return;
    }//end process()
}//end class