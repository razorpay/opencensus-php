import Button from 'common/new-ui/Button';
import { withRouter } from 'common/deprecated/withRouter';

import { BATCH_PAYMENT_PAGES_BASE_URL } from 'merchant/views/PaymentPages/PaymentPages/constants';

const Header = (props) => {
  const onBackToDashboardClick = () => {
    const { isBatchPaymentPages, history } = props;
    const url = isBatchPaymentPages ? BATCH_PAYMENT_PAGES_BASE_URL : `/paymentpages/`;
    history.push(url);
  };

  return (
    <div className="page-nav-container">
      <div className="payment-page-nav">
        <div className="nav-left">
          <div className="nav-title">Page Published</div>
        </div>
        <div className="nav-right">
          <Button.Primary type="button" onClick={onBackToDashboardClick}>
            <span>Back to Dashboard</span>
          </Button.Primary>
        </div>
      </div>
    </div>
  );
};

export default withRouter(Header);
