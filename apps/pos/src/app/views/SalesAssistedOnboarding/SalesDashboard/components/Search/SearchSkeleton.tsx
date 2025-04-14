import React from 'react';
import { Box, Skeleton } from '@razorpay/blade/components';

const COLS = 8;

const SearchSkeleton = () => {
  return (
    <Box>
      {Array.from({ length: COLS }).map((_, colIndex) => (
        <Box marginBottom="spacing.4" key={colIndex}>
          <Box display={'flex'} marginBottom="spacing.4" alignItems={'center'}>
            <Skeleton
              width="32px"
              height="spacing.8"
              borderRadius={'round'}
              marginRight={'spacing.4'}
            />
            <Skeleton width="95%" height="spacing.8" borderRadius={'medium'} />
          </Box>
          <Box display={'flex'} alignItems={'center'}>
            <Skeleton
              width="32px"
              height="spacing.8"
              borderRadius={'round'}
              marginRight={'spacing.4'}
            />
            <Skeleton width="60%" height="spacing.8" borderRadius={'medium'} />
          </Box>
        </Box>
      ))}
    </Box>
  );
};

export default SearchSkeleton;
