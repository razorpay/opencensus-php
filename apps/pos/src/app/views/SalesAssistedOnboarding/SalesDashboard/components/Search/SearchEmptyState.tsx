import React from 'react';
import { Text, Box } from '@razorpay/blade/components';
import emptySearchIcon from 'apps/pos/src/assets/EmptySearch.svg';
import noResultsFoundIcon from 'apps/pos/src/assets/NoResultsFound.svg';
import {
  NO_RESULTS_MESSAGE,
  EMPTY_SEARCH_MESSAGE,
  NO_RESULTS_FOUND_PROMPT,
  SEARCH_INSTRUCTIONS,
} from './constants';

interface SearchEmptyState {
  noResultsFound: boolean;
}

const SearchEmptyState = ({ noResultsFound }: SearchEmptyState) => {
  return (
    <Box
      display={'flex'}
      justifyContent={'center'}
      alignItems={'center'}
      flexDirection={'column'}
      height={'50vh'}
    >
      <img
        src={noResultsFound ? noResultsFoundIcon : emptySearchIcon}
        alt={noResultsFound ? NO_RESULTS_MESSAGE : EMPTY_SEARCH_MESSAGE}
        loading="lazy"
      />
      <Box marginTop={'spacing.5'} width={'50%'}>
        <Text textAlign={'center'} color="surface.text.gray.muted">
          {noResultsFound ? NO_RESULTS_FOUND_PROMPT : SEARCH_INSTRUCTIONS}
        </Text>
      </Box>
    </Box>
  );
};

export default SearchEmptyState;
