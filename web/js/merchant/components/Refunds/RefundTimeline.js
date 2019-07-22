import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { Link } from 'react-router-dom';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import { PaymentStatusLabel } from 'merchant/components/StatusLabel';

export default () => {
  return (
    <ul class="refund-timeline">
      <li>
        <div class="refund-timeline-status">
          <PaymentStatusLabel status={'captured'} />
        </div>
        <p class="refund-timeline-type">Instand Refund</p>
        <p class="refund-timeline-timestamp">30 APR 2019, 11:27 PM</p>
      </li>
      <li>
        <div class="refund-timeline-status">
          <PaymentStatusLabel status={'refunded'} />
        </div>
        <p class="refund-timeline-type">Instand Refund</p>
        <p class="refund-timeline-timestamp">30 APR 2019, 11:27 PM</p>
      </li>
      <li>
        <p class="refund-timeline-type">Instand Refund</p>
        <p class="refund-timeline-timestamp">30 APR 2019, 11:27 PM</p>
      </li>
      <li>
        <div class="refund-timeline-status">
          <PaymentStatusLabel status={'captured'} />
        </div>
        <p class="refund-timeline-type">Instand Refund</p>
        <p class="refund-timeline-timestamp">30 APR 2019, 11:27 PM</p>
      </li>
    </ul>
  );
};
