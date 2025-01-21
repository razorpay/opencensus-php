import React, { Fragment } from 'react';
import { Heading } from '@razorpay/blade/components';
import BillingTerminals from './components/BillingTerminals';

const DigitalBillingAdditionalDetails = () => {
  return (
    <Fragment>
      <Heading marginTop="spacing.4" marginBottom="spacing.7">
        Digital Billing
      </Heading>
      <BillingTerminals />
    </Fragment>
  );
};

export default DigitalBillingAdditionalDetails;
