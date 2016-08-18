<?php
/**
 * Razorpay_Sniffs_ControlStructires_OpeningBraceBsdAllmanSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
/**
 * Razorpay_Sniffs_ControlStructires_OpeningBraceBsdAllmanSniff.
 *
 * Checks that the opening brace of a function is on the line after the
 * function declaration.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Razorpay_Sniffs_ControlStructures_OpeningBraceBsdAllmanSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * Should this sniff check if-statement braces?
     *
     * @var bool
     */
    public $checkIf = true;

    /**
     * Registers the tokens that this sniff wants to listen for.
     *
     * @return void
     */
    public function register()
    {
        return array(
            T_IF,
            T_ELSE,
            T_ELSEIF,
        );
    }//end register()

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $openingBrace = $tokens[$stackPtr]['scope_opener'];

        if ($tokens[$stackPtr]['code'] === T_ELSE)
        {
            $closeBracket = $stackPtr;
        }
        else
        {
            $closeBracket = $tokens[$stackPtr]['parenthesis_closer'];
        }

        $tokenLine = $tokens[$closeBracket]['line'];
        $braceLine    = $tokens[$openingBrace]['line'];
        $lineDifference = ($braceLine - $tokenLine);

        if ($lineDifference === 0)
        {
            $error = 'Opening brace should be on a new line';
            $fix   = $phpcsFile->addFixableError($error, $openingBrace, 'BraceOnSameLine');

            if ($fix === true)
            {
                $phpcsFile->fixer->beginChangeset();
                $indent = $phpcsFile->findFirstOnLine(array(), $openingBrace);

                if ($tokens[$indent]['code'] === T_WHITESPACE)
                {
                    $phpcsFile->fixer->addContentBefore($openingBrace, $tokens[$indent]['content']);
                }

                $phpcsFile->fixer->addNewlineBefore($openingBrace);
                $phpcsFile->fixer->endChangeset();
            }

            $phpcsFile->recordMetric($stackPtr, 'If opening brace placement', 'same line');
        }
        else if ($lineDifference > 1)
        {
            $error = 'Opening brace should be on the line after the declaration; found %s blank line(s)';

            $data  = array(($lineDifference - 1));

            $fix   = $phpcsFile->addFixableError($error, $openingBrace, 'BraceSpacing', $data);

            if ($fix === true)
            {
                for ($i = ($tokens[$stackPtr]['parenthesis_closer'] + 1); $i < $openingBrace; $i++)
                {
                    if ($tokens[$i]['line'] === $braceLine)
                    {
                        $phpcsFile->fixer->addNewLineBefore($i);
                        break;
                    }

                    $phpcsFile->fixer->replaceToken($i, '');
                }
            }
        }//end if

        $next = $phpcsFile->findNext(T_WHITESPACE, ($openingBrace + 1), null, true);

        if ($tokens[$next]['line'] === $tokens[$openingBrace]['line'])
        {
            if ($next === $tokens[$stackPtr]['scope_closer'])
            {
                // Ignore empty if statements.
                return;
            }

            $error = 'Opening brace must be the last content on the line';
            $fix   = $phpcsFile->addFixableError($error, $openingBrace, 'ContentAfterBrace');

            if ($fix === true)
            {
                $phpcsFile->fixer->addNewline($openingBrace);
            }
        }

        // Only continue checking if the opening brace looks good.
        if ($lineDifference !== 1)
        {
            return;
        }

        $phpcsFile->recordMetric($stackPtr, 'If opening brace placement', 'new line');
    }//end process()
}//end class