import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { Link } from 'react-router-dom';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import { PaymentStatusLabel } from 'merchant/components/StatusLabel';
import RefundStatusTimeline from 'merchant/components/Refunds/RefundTimeline';
import ContentToggler from 'rzp/ui/Toggler/ContentToggler';

export default ({ strikeThroughContent, updatedContent, description }) => {
  return (
    <div class="refund-entity-update">
      <div class="refund-update">
        <div class="refund-strike-text">{strikeThroughContent}</div>
        <div>{updatedContent}</div>
      </div>
      <div class="update-description">{description}</div>
    </div>
  );
};
