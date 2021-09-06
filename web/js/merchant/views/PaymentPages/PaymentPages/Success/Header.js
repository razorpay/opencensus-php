import Button, { AsyncBtn } from 'common/new-ui/Button';
import { withRouter } from 'react-router-dom';

const Header = (props) => {
  return (
    <div class="page-nav-container">
      <div class="payment-page-nav">
        <div class="nav-left">
          <div class="nav-title">Page Saved & Published</div>
        </div>
        <div class="nav-right">
          <Button.Primary
            type="button"
            onClick={() => {
              props.history.push(`/paymentpages/`);
            }}
          >
            <span>Back to Dashboard</span>
          </Button.Primary>
        </div>
      </div>
    </div>
  );
};

export default withRouter(Header);
