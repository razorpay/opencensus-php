import { Button } from '@razorpay/blade/components';
import { withRouter } from 'react-router-dom';

const Header = ({ isBatchPaymentPages, history, id }) => {
  const handlePuplishPage = () => {
    const url = isBatchPaymentPages
      ? `/paymentpages/batchpaymentpages/${id}/success`
      : `/paymentpages/${id}/success`;
    history.push(url);
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
          >
            Create and publish page
          </Button>
        </div>
      </div>
    </div>
  );
};

export default withRouter(Header);
