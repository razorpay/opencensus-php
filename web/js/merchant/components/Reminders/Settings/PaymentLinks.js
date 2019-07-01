import Setting from './Setting';

export default class PaymentLinksSettings extends React.Component {
  state = {
    totalUnpaidLinks: 12,
  };

  saveSettings = () => {};

  saveToggleChange = () => {};

  render() {
    return (
      <div class="reminders-settings--payment_links">
        <Setting
          type="Payment Links"
          emailDetails={emailDetails}
          onSaveClick={this.saveSettings}
          saveToggleChange={this.saveToggleChange}
          totalUnpaidLinks={this.state.totalUnpaidLinks}
        />
      </div>
    );
  }
}

const emailDetails = {
  subject: '"We’ve not received your payment"',
  contentList: [
    'We have not received your payment. It will expire on 21st of April.',
    'You can start accepting payments with Razorpay within 2 minutes. Complete the signup and verify your PAN card details online to start transacting today.',
    <React.Fragment>
      Payment Link:{' '}
      <a href="www.razorpay.com/payment-link/4oejw68wbb9/">
        www.razorpay.com/payment-link/4oejw68wbb9/
      </a>
    </React.Fragment>,
    "Use our products like Payment Links or Invoices without any integration effort. Using these products you can collect payments using SMS, EMail, What'sapp, Chatbots, Messenger etc.",
  ],
};
