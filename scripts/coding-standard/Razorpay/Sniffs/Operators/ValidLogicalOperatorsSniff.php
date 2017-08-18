<?php

namespace Razorpay\Sniffs\Operators;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class ValidLogicalOperatorsSniff implements Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [
            T_BOOLEAN_OR,
            T_BOOLEAN_AND,
        ];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The current file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $replacements = [
            '&&' => 'and',
            '||' => 'or',
        ];

        $operator = $tokens[$stackPtr]['content'];

        if (isset($replacements[$operator]) === false)
        {
            return;
        }

        $expected = $replacements[$operator];

        $error = 'Logical operator "%s" is prohibited; use "%s" instead';

        $data  = [
            $operator,
            $expected,
        ];

        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'Found', $data);

        if ($fix === true)
        {
            $phpcsFile->fixer->replaceToken($stackPtr, $expected);
        }
    }
}