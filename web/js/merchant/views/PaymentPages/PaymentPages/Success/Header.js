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
    <div class="page-nav-container">
      <div class="payment-page-nav">
        <div class="nav-left">
          <div class="nav-title">Page Published</div>
        </div>
        <div class="nav-right">
          <Button.Primary type="button" onClick={onBackToDashboardClick}>
            <span>Back to Dashboard</span>
          </Button.Primary>
        </div>
      </div>
    </div>
  );
};

export default withRouter(Header);
