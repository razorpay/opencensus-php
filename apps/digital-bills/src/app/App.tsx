import React from 'react';
import { Box } from '@razorpay/blade/components';

import DigitalBillsRouter from '@apps/digital-bills/src/bootstrap/Route/DigitalBillsRouter';

// Main App Component
const App = (): React.ReactElement => {
  return (
    <Box padding="spacing.5">
      <DigitalBillsRouter />
    </Box>
  );
};

export default App;
