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
    refund.speed_change_time = 1562927620;
    const mileStones = [];
    mileStones.push({
      status: 'processing',
      mode: `(${refund.speed_processed} refund)`,
      timeStamp: refund.created_at,
    });

    if (refund.speed_change_time) {
      mileStones.push(
        {
          text: 'Refund mode updated to Normal',
          timeStamp: refund.speed_change_time,
        },
        {
          status: 'processing',
          mode: `(${refund.speed_processed} refund)`,
          timeStamp: refund.speed_change_time,
        }
      );
    }

    if (refund.status === 'processed') {
      mileStones.push({
        status: 'processed',
        mode: `(${refund.speed_processed} refund)`,
        timeStamp: refund.created_at,
      });
    }

    return mileStones.reverse();
  };

  render() {
    let mileStones = this.getMilestones(this.props.refund);

    return (
      <ul class="refund-timeline">
        {mileStones.map((item, idx) => {
          if (item.status) {
            return (
              <li>
                <div class="refund-timeline-status">
                  <RefundStatusLabel status={item.status} />
                </div>
                <p class="refund-timeline-type">{item.mode}</p>
                <p class="refund-timeline-timestamp">
                  <Time
                    value={item.timeStamp}
                    format="DD MMM YYYY, hh:mm:ss a"
                  />
                </p>
              </li>
            );
          } else {
            return (
              <li>
                <p class="refund-timeline-type">{item.text}</p>
                <p class="refund-timeline-timestamp">
                  <Time
                    value={item.timeStamp}
                    format="DD MMM YYYY, hh:mm:ss a"
                  />
                </p>
              </li>
            );
          }
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
