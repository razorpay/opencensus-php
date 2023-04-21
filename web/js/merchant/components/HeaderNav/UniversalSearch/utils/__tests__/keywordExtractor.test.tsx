import { getOnlyKeywordSentence } from 'merchant/components/HeaderNav/UniversalSearch/utils/keywordExtractor';

const data = [
  {
    input: 'how to update bank account',
    response: 'update bank account',
  },
  {
    input: 'where to find subscriptions',
    response: 'find subscriptions',
  },
  {
    input: 'why is my settlement failed',
    response: 'settlement failed',
  },
  {
    input: 'why',
    response: 'why',
  },
];

describe('keywordExtractor', () => {
  test.each(data)('Extract keys words from sentences %s', ({ input, response }) => {
    const result = getOnlyKeywordSentence(input);
    expect(result).toEqual(response);
  });
});
