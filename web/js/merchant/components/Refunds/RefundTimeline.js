import Time from 'rzp/ui/Time';
import { RefundStatusLabel } from 'merchant/components/StatusLabel';

export default class RefundStatusTimeline extends React.Component {
  getMilestones = refund => {
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
        timeStamp:
          refund.speed_processed === 'normal'
            ? refund.speed_change_time
              ? refund.speed_change_time
              : refund.created_at
            : refund.processed_at,
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
                <p class="refund-timeline-mode">{item.mode}</p>
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
                <p class="refund-timeline-text">{item.text}</p>
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
