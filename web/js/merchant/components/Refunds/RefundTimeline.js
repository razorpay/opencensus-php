import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { Link } from 'react-router-dom';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import { RefundStatusLabel } from 'merchant/components/StatusLabel';
export default class RefundStatusTimeline extends React.Component {
  getMilestones = refund => {
    const mileStones = [];
    mileStones.push({
      status: 'processing',
      mode: `(${refund.speed_processed} refund)`,
      timeStamp: refund.created_at,
    });

    if (refund.status === 'processed') {
      mileStones.push({
        status: 'processed',
        mode: `(${refund.speed_processed} refund)`,
        timeStamp: refund.created_at,
      });
    }

    return mileStones;
  };

  render() {
    console.log('R', this.props.refund);
    let mileStones = this.getMilestones(this.props.refund);

    console.log('MS', mileStones);

    return (
      <ul class="refund-timeline">
        {mileStones.map((item, idx) => {
          return (
            <li>
              <div class="refund-timeline-status">
                <RefundStatusLabel status={item.status} />
              </div>
              <p class="refund-timeline-type">{item.mode}</p>
              <p class="refund-timeline-timestamp">
                {
                  <Time
                    value={item.timeStamp}
                    format="DD MMM YYYY, hh:mm:ss a"
                  />
                }
              </p>
            </li>
          );
        })}
      </ul>
    );
  }
}

{
  /* <li>
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
        </li>  */
}
