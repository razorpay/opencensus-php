import React from 'react';
import { connect } from 'react-redux';
import {
  fetchReminders,
  fetchRemindersConfigs,
  fetchRemindersMerchantConfigs,
} from 'merchant/reducers/reminders';
import Spinner from 'common/ui/Spinner';
import Alert from 'common/ui/Forms/Alert';
import DocsLink from 'merchant/components/DocsLink';
import PaymentLinksSettings from './PaymentLinksSettings';

class Reminders extends React.Component {
  constructor(props) {
    super(props);

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
    ]).catch(() => {
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
        <div className="documentation-section-link">
          <DocsLink url="https://razorpay.com/docs/payment-links/reminders-payment-links/" />
        </div>

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

export default connect((state) => state.reminders, {
  fetchReminders,
  fetchRemindersConfigs,
  fetchRemindersMerchantConfigs,
})(Reminders);
