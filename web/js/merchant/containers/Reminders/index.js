import { connect } from 'react-redux';

import {
  fetchReminders,
  createReminders,
  fetchRemindersConfigs,
} from 'merchant/modules/reminders';

import HeaderAction from 'rzp/ui/HeaderAction';
import Spinner from 'rzp/ui/Spinner';

import PaymentLinksSettings from './PaymentLinksSettings';

@connect(state => state.reminders, {
  fetchReminders,
  createReminders,
  fetchRemindersConfigs,
})
export default class extends React.Component {
  componentDidMount() {
    this.fetchDataForReminders();
  }

  fetchDataForReminders = () => {
    return Promise.all([
      this.fetchReminders(),
      this.props.fetchRemindersConfigs(),
    ]);
  };

  fetchReminders = () => {
    return this.props.fetchReminders().then(resp => {
      if (resp.data.count === 0) {
        const promiseList = REMINDERS_TYPES_LIST.map(key =>
          this.props.createReminders(key)
        );

        return new Promise.all(promiseList);
      }

      return resp;
    });
  };

  render() {
    const { items } = this.props.reminders,
      loading = this.props.reminders.loading || this.props.configs.loading;

    if (loading) {
      return (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    }

    const settingsProps = {
      disableReminder: this.disableReminder,
    };

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

        <div class="Reminders-settings">
          {items.length &&
            items.map(
              item =>
                REMINDERS_TYPE_MAP[item.namespace] &&
                REMINDERS_TYPE_MAP[item.namespace](settingsProps)
            )}
        </div>
      </div>
    );
  }
}

const REMINDERS_TYPES_LIST = ['payment_link'];

const REMINDERS_TYPE_MAP = {
  payment_link: props => <PaymentLinksSettings {...props} />,
};
