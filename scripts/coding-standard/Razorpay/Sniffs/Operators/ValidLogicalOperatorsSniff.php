<?php

class Razorpay_Sniffs_Operators_ValidLogicalOperatorsSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
                T_BOOLEAN_AND,
                T_BOOLEAN_OR,
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
            '&&' => 'and',
            '||' => 'or',
        );

        $operator = $tokens[$stackPtr]['content'];

        if (isset($replacements[$operator]) === false)
        {
            return;
        }

        $error = 'Logical operator "%s" is prohibited; use "%s" instead';

        $data  = array(
            $operator,
            $replacements[$operator],
        );

        $phpcsFile->addError($error, $stackPtr, 'NotAllowed', $data);
    }//end process()
}//end class