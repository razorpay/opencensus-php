import keywordExtractor from 'keyword-extractor';

// this method extracts the main keywords from the sentence;

export const getOnlyKeywordSentence = (sentence: string): string => {
  const trimmedSentence = sentence.trim();
  const words = trimmedSentence.split(' ');
  if (words.length > 1) {
    const sentenceToValidate = words.slice(0, -1).join(' ');
    const extractionResult = keywordExtractor.extract(sentenceToValidate, {
      language: 'english',
      remove_digits: true,
      return_changed_case: true,
      remove_duplicates: true,
    });
    return [...extractionResult, words[words.length - 1]].join(' ');
  }
  return trimmedSentence;
};
