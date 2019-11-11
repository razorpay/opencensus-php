import { connect } from 'react-redux';

import {
  fetchReminders,
  fetchRemindersConfigs,
  fetchRemindersMerchantConfigs,
} from 'merchant/reducers/reminders';

import HeaderAction from 'rzp/ui/HeaderAction';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';

import PaymentLinksSettings from './PaymentLinksSettings';

@connect(state => state.reminders, {
  fetchReminders,
  fetchRemindersConfigs,
  fetchRemindersMerchantConfigs,
})
export default class extends React.Component {
  constructor(props) {
    super();

    this.state = {
      errors: '',
    };
  }

  componentDidMount() {
    this.fetchDataForReminders();
  }

  fetchDataForReminders = () => {
    return Promise.all([
      this.props.fetchReminders(),
      this.props.fetchRemindersConfigs(),
      this.props.fetchRemindersMerchantConfigs(),
    ]).catch(err => {
      this.setState({
        errors: 'Failed to fetch data',
      });
    });
  };

  render() {
    const loading =
      this.props.reminders.loading ||
      this.props.configs.loading ||
      this.props.merchant_config.loading;

    if (loading) {
      return (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    }

    return (
      <div class="content-wrapper content-sm" id="settings-content">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <a
              class="btn btn-link settlement-doc-btn"
              href="https://razorpay.com/docs/payment-pages/"
              target="_blank"
            >
              Know more about reminders <span class="icon i-external-link" />
            </a>
          </div>
        </HeaderAction>

        {this.state.errors ? (
          <Alert type="error" message={this.state.errors} showDismiss={false} />
        ) : (
          <div class="ReminderSettings">
            <PaymentLinksSettings />
          </div>
        )}
      </div>
    );
  }
}
