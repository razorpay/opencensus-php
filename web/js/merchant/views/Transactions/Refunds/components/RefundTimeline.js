import Time from 'common/ui/Time';
import { RefundStatusLabel } from 'merchant/components/StatusLabel';
import Popover, { PopoverTitle, PopoverBody } from 'common/ui/Popover';

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
            text: (
              <div>
                Refund speed updated to Normal &nbsp;
                <span>
                  <i class="i i-help" />
                  <Popover align="right" theme="dark">
                    <PopoverBody>
                      &nbsp; Instant Refund was unsuccessful, the fee &nbsp; for
                      instant refund has been reversed.
                    </PopoverBody>
                  </Popover>
                </span>
              </div>
            ),
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
        timeStamp: refund.created_at,
      });

      if (refund.status === 'processed') {
        mileStones.push({
          status: 'processed',
          mode: 'Instant Refund',
          timeStamp: refund.processed_at,
        });
      }
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
              <li key={idx}>
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
              <li key={idx}>
                <div class="refund-timeline-text">{item.text}</div>
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
