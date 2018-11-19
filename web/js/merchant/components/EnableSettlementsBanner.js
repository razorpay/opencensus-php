import React from 'react';
import { Link } from 'react-router-dom';

import Banner from 'rzp/ui/Banner';

export default () => (
  <div className="enable-settlements-banner">
    <Banner>
      Your settlements are on hold. You will need to fill the KYC Form to
      receive your payments in your bank account
      <span className="big-dot-separator" />
      <Link to="/activation">Complete KYC Form</Link>
    </Banner>
  </div>
);
