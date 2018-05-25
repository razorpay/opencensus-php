import { titleCase } from 'rzp/utils/rzp-utils';

/**
 * Batch Payment Links Form
 * - Send Email/Send SMS
 */

export default function PaymentLinksForm({
  batchType,
  sms_notify,
  email_notify,
  onChange,
}) {
  return (
    <div>
      <h5 class="send-link-head">
        <strong>SEND {titleCase(batchType)}S</strong>
      </h5>
      <div class="form-group send-links-form">
        <div class="checkbox rzpCheckbox next m-r">
          <input
            name="sms_notify"
            id="sms_notify"
            type="checkbox"
            value={sms_notify}
            onChange={e => onChange('sms_notify', e.target.checked)}
          />
          <label for="sms_notify" class="icon i-check">
            Send SMS
          </label>
        </div>
        <div class="checkbox rzpCheckbox next m-r">
          <input
            name="email_notify"
            id="email_notify"
            type="checkbox"
            value={email_notify}
            onChange={e => onChange('email_notify', e.target.checked)}
          />
          <label for="email_notify" class="icon i-check">
            Send Email
          </label>
        </div>
      </div>
      <p>
        <i class="i i-info-circle m-r" />
        Payment Links with SMS and Email will be sent once the batch is created.
      </p>
    </div>
  );
}
