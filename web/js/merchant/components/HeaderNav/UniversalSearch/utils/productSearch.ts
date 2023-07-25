import { ProductType } from 'merchant/components/HeaderNav/UniversalSearch/typings';
import { getOnlyKeywordSentence } from './keywordExtractor';

interface SearchResult extends ProductType {
  score: number;
  refIndex: number;
}

const floorDecimal = (num: number): number => Math.floor(num * 1000) / 1000;

const getUniqueSearchResults = ({ results = [] }: { results: SearchResult[] }): SearchResult[] => {
  const uniqueItems: Record<string, boolean> = {};
  const uniqueSearches: SearchResult[] = [];
  for (const eachResult of results) {
    const {
      item: { title },
    } = eachResult;
    if (!uniqueItems[title]) {
      uniqueItems[title] = true;
      uniqueSearches.push(eachResult);
    }
  }
  return uniqueSearches;
};

const sequencingSearchListing = ({ product, multiKey }): SearchResult[] => {
  if (multiKey.length === 0) return product;
  const listing = [...product, ...multiKey];

  listing.sort((a, b): number => {
    const scoreA = floorDecimal(a.score);
    const scoreB = floorDecimal(b.score);
    if (scoreA === scoreB) {
      return 0;
    }
    return a.score - b.score;
  });

  return getUniqueSearchResults({ results: listing });
};

const sequencingMultiKeyListing = (results): SearchResult[] => {
  const scoreSequencedResults = results.sort((a, b): number => a.score - b.score);
  return getUniqueSearchResults({ results: scoreSequencedResults });
};

const multiKeywordMatcher = ({
  query,
  FuseClient,
}: {
  query: string;
  FuseClient: any;
}): SearchResult[] => {
  if (!FuseClient || typeof FuseClient?.multiKey?.search !== 'function') {
    return [];
  }
  const words = query.trim().split(/\s+/);
  const results: SearchResult[] = [];
  if (words.length < 2 || words.length > 20) {
    return [];
  }
  words.forEach((word) => {
    const multiKeySearchResult = FuseClient.multiKey.search(word, { limit: 3 });
    results.push(...multiKeySearchResult);
  });
  return sequencingMultiKeyListing(results);
};

export const getProductSearchResults = ({
  query,
  FuseClient,
}: {
  query: string;
  FuseClient: any;
}): SearchResult[] => {
  if (!FuseClient || typeof FuseClient?.query?.search !== 'function') {
    return [];
  }
  try {
    const keywordOnlyQuery = getOnlyKeywordSentence(query);
    const multiKeywordMatchResults = multiKeywordMatcher({ query: keywordOnlyQuery, FuseClient });
    const productSearchResults = FuseClient.query.search(keywordOnlyQuery, { limit: 15 });
    return sequencingSearchListing({
      product: productSearchResults,
      multiKey: multiKeywordMatchResults,
    });
  } catch (error) {
    return [];
  }
};
