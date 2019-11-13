import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import Alert from 'common/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { Link } from 'react-router-dom';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import { PaymentStatusLabel } from 'merchant/components/StatusLabel';
import RefundStatusTimeline from 'merchant/components/Refunds/RefundTimeline';
import ContentToggler from 'common/ui/Toggler/ContentToggler';

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
