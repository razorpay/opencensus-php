/**
 * A mapping of common words to their respective acronyms used in the dashboard.
 */
export const acronymsForShorteningText: Record<string, string> = {
    payment: 'paymt',
    payments: 'paymts',
    method: 'meth',
    methods: 'meths',
    transaction: 'transctn',
    transactions: 'transctns',
    total: 'tot',
    number: 'no',
    platform: 'platfrm',
    platforms: 'platfrms',
    traffic: 'trffc',
    volume: 'vol',
  };
  
  /**
   * Shortens the words in a given sentence based on predefined dashboard acronyms.
   * 
   * This function takes a sentence and replaces any matching words with their corresponding
   * acronyms from the `acronyms` map. Words that are not present in the `acronyms` map remain unchanged.
   * 
   * @param {string} sentence - The input sentence that may contain words to be shortened.
   * @returns {string} The sentence with applicable words shortened using the dashboard acronyms.
   * 
   * @example
   * // Example 1: Shorten common terms in a sentence
   * const result = shortenTextBasedOnDashboardAcronyms('total payment volume');
   * console.log(result); // Output: "tot paymt vol"
   * 
   * @example
   * // Example 2: Sentence with no matching acronyms
   * const result = shortenTextBasedOnDashboardAcronyms('This is a test sentence');
   * console.log(result); // Output: "This is a test sentence" (unchanged)
   * 
   * @example
   * // Example 3: Empty sentence
   * const result = shortenTextBasedOnDashboardAcronyms('');
   * console.log(result); // Output: "" (empty string)
   */
  export const shortenTextBasedOnDashboardAcronyms = (sentence: string = ''): string => {
    // Split the sentence into words based on spaces
    const words = sentence.split(/\s+/);
  
    // Map through each word, replace it with the corresponding acronym if found, otherwise keep it as is
    return words
      .map((word) => acronymsForShorteningText[word.toLowerCase()] || word)
      .join(' ');
  };
  