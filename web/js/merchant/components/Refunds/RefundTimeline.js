import Time from 'rzp/ui/Time';
import { RefundStatusLabel } from 'merchant/components/StatusLabel';

function ShowTime({ time }) {
  return <Time value={time} format="DD MMM YYYY, hh:mm:ss a" />;
}

export default class RefundStatusTimeline extends React.Component {
  getMilestones = refund => {
    const mileStones = [];

    if (refund.speed_processed === 'normal') {
      if (refund.speed_requested === 'normal') {
        mileStones.push({
          status: 'processing',
          mode: `Normal Refund`,
          timeStamp: refund.created_at,
        });
        mileStones.push({
          status: 'processed',
          mode: 'Normal Refund',
          timeStamp: refund.created_at,
        });
      } else {
        mileStones.push({
          status: 'processing',
          mode: `Instant Refund`,
          timeStamp: refund.created_at,
        });

        mileStones.push(
          {
            text: 'Refund mode updated to Normal',
            timeStamp: refund.speed_change_time
              ? refund.speed_change_time
              : refund.created_at,
          },
          {
            status: 'processing',
            mode: `Normal Refund`,
            timeStamp: refund.speed_change_time
              ? refund.speed_change_time
              : refund.created_at,
          }
        );

        mileStones.push({
          status: 'processed',
          mode: `Normal Refund`,
          timeStamp: refund.speed_change_time
            ? refund.speed_change_time
            : refund.created_at,
        });
      }
    }

    if (refund.speed_processed === 'instant') {
      mileStones.push({
        status: 'processing',
        mode: `Instant Refund`,
        timeStamp: refund.processed_at,
      });
      mileStones.push({
        status: 'processed',
        mode: 'Instant Refund',
        timeStamp: refund.processed_at,
      });
    }

    if (refund.speed_processed === null) {
      mileStones.push({
        status: 'processing',
        mode: `Instant Refund`,
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
                <p class="refund-timeline-mode">{item.mode}</p>
                <p class="refund-timeline-timestamp">
                  <ShowTime time={item.timeStamp} />
                </p>
              </li>
            );
          } else {
            return (
              <li>
                <p class="refund-timeline-text">{item.text}</p>
                <p class="refund-timeline-timestamp">
                  <ShowTime time={item.timeStamp} />
                </p>
              </li>
            );
          }
        })}
      </ul>
    );
  }
}
