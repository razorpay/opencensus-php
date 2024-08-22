import React from 'react';
import { Box } from '@razorpay/blade/components';
import DigitalBillsRouter from '../bootstrap/Route/DigitalBillsRouter';

const App = (): React.ReactElement => {
  return (
    <Box padding="spacing.5">
      <DigitalBillsRouter />
    </Box>
  );
};

export default App;
