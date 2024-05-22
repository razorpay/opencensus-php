import React from 'react';
import { Box, Heading } from '@razorpay/blade/components';

// import { useStore } from 'shell/commonStore';

const App = (): React.ReactElement => {
  // const session = useStore((state) => state.session);
  // console.log('Session', session);

  return (
    <Box padding="spacing.5">
      <Heading size="large" weight="semibold">
        Assisted Onboarding
      </Heading>
      <Box marginY="spacing.2">Assisted Onboarding Content Comes Here!</Box>
    </Box>
  );
};

export default App;
