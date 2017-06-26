<?php

namespace Razorpay\Sniffs\Operators;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class LowerCaseLogicalOperatorSniff implements Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [
            T_LOGICAL_AND,
            T_LOGICAL_OR,
        ];
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
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $replacements = [
            'and', 'or'
        ];

        $operator = strtolower($tokens[$stackPtr]['content']);

        if (in_array($operator, $replacements, true) === false)
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

            $data  = [
                strtolower($operatorName),
                $operatorName,
            ];

            $fix = $phpcsFile->addFixableError($error, $stackPtr, 'Found', $data);

            if ($fix === true)
            {
                $phpcsFile->fixer->replaceToken($stackPtr, $operator);
            }
        }
        else
        {
            $phpcsFile->recordMetric($stackPtr, 'Operator name case', 'lower');
        }

        return;
    }
}