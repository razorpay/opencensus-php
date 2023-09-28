import React from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import { Button } from '@razorpay/blade/components';

import { BATCH_PAYMENT_PAGES_BASE_URL } from 'merchant/views/PaymentPages/PaymentPages/constants';

const Header = ({ history, id }) => {
  const handlePuplishPage = () => {
    history.push(`${BATCH_PAYMENT_PAGES_BASE_URL}/${id}/success`);
  };

  return (
    <div className="page-nav-container">
      <div className="payment-page-nav">
        <div className="nav-left">
          <div className="nav-title">Create New Payment Page (Step 2/2)</div>
        </div>
        <div className="nav-right">
          <Button
            className="Button--primary"
            variant="primary"
            size="medium"
            onClick={handlePuplishPage}
            testID="bpp-publish-btn"
          >
            Create and publish page
          </Button>
        </div>
      </div>
    </div>
  );
};

export default withRouter(Header);
