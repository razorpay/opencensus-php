import React from 'react';
import { Box } from '@razorpay/blade/components';
import Loader from 'common/ui/Loader';

const SplitzEvalLoader = ({ relativeToWindow }: { relativeToWindow?: boolean }): JSX.Element => {
  return (
    <Box
      display="flex"
      justifyContent="center"
      alignItems="center"
      position="absolute"
      top="spacing.0"
      right="spacing.0"
      bottom="spacing.0"
      width={{
        l: relativeToWindow ? '100%' : 'calc(100% - 250px)',
        base: '100%',
      }}
    >
      <Loader />
    </Box>
  );
};

export default SplitzEvalLoader;
