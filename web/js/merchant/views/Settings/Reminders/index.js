import React from 'react';
import { connect } from 'react-redux';
import { withI18Service } from 'common/i18';
import {
  fetchReminders,
  fetchRemindersConfigs,
  fetchRemindersMerchantConfigs,
} from 'merchant/reducers/reminders';
import Spinner from 'common/ui/Spinner';
import ShowWhen from 'merchant/components/ShowWhen';
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
    const {
      i18: { isConfigTagEnabled },
    } = this.props;

    if (loading) {
      return (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      );
    }

    return (
      <div className="content-wrapper content-sm" id="settings-content">
        <ShowWhen additionalCondition={() => !isConfigTagEnabled('documentation.documentation')}>
          <div className="documentation-section-link">
            <DocsLink url="https://razorpay.com/docs/payment-links/reminders-payment-links/" />
          </div>
        </ShowWhen>

        {this.state.errors ? (
          <Alert type="error" message={this.state.errors} showDismiss={false} />
        ) : (
          <div className="ReminderSettings">
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
})(withI18Service(Reminders));
