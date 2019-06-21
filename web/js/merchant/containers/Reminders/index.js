import HeaderAction from 'rzp/ui/HeaderAction';

import PaymentLinksSettings from 'merchant/components/Reminders/Settings/PaymentLinks';

export default function Reminders() {
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

      <PaymentLinksSettings />
    </div>
  );
}
