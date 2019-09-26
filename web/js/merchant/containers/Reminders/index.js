import { connect } from 'react-redux';

import { fetchReminders } from 'merchant/modules/reminders';

import HeaderAction from 'rzp/ui/HeaderAction';

import PaymentLinksSettings from './PaymentLinksSettings';

@connect(state => state.reminders, {
  fetchReminders,
})
export default class extends React.Component {
  componentDidMount() {
    this.props.fetchReminders();
  }

  render() {
    const { items } = this.props;

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
          {items.map(item => remindersMap[item.namespace])}
        </div>
      </div>
    );
  }
}

const remindersMap = {
  payment_links: PaymentLinksSettings,
};
