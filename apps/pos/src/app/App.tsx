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
    </Box>
  );
};

export default App;
