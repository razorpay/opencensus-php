import React from 'react';
import { Alert } from '@razorpay/blade/components';

const EcosystemHealthError = (): JSX.Element => {
  return (
    <div data-testid="ecosystem-health-error" className="ecosystem-health-error">
      <Alert
        emphasis="subtle"
        title="Something went wrong"
        description="We are working on fixing the problem. You may refresh the page or try again after some
        time."
        isDismissible={false}
        color="negative"
      />
    </div>
  );
};

export default EcosystemHealthError;
