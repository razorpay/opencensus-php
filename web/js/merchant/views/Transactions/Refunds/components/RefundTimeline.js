import Time from 'common/ui/Time';
import { RefundStatusLabel } from 'merchant/components/StatusLabel';
import Popover, { PopoverBody } from 'common/ui/Popover';

function ShowTime({ time }) {
  return <Time value={time} format="DD MMM YYYY, hh:mm:ss a" />;
}

export default class RefundStatusTimeline extends React.Component {
  getMilestones = (refund) => {
    const mileStones = [];

    if (refund.speed_processed === 'normal') {
      if (refund.speed_requested === 'normal') {
        mileStones.push({
          status: 'processing',
          mode: `Normal Refund`,
          timeStamp: refund.created_at,
        });
      } else {
        mileStones.push({
          status: 'processing',
          mode: `Instant Refund`,
          timeStamp: refund.created_at,
        });

        let speedChangeTime = refund.speed_change_time
          ? refund.speed_change_time
          : refund.created_at;

        mileStones.push(
          {
            text: (
              <div>
                Refund speed updated to Normal &nbsp;
                <span>
                  <i class="i i-help" />
                  <Popover align="right" theme="dark">
                    <PopoverBody>
                      &nbsp; Instant Refund was unsuccessful, the fee &nbsp; for instant refund has
                      been reversed.
                    </PopoverBody>
                  </Popover>
                </span>
              </div>
            ),
            timeStamp: speedChangeTime,
          },
          {
            status: 'processing',
            mode: `Normal Refund`,
            timeStamp: speedChangeTime,
          },
        );
      }

      if (refund.status === 'processed') {
        mileStones.push({
          status: 'processed',
          mode: 'Normal Refund',
          timeStamp: refund.processed_at,
        });
      }
      if (refund.status === 'failed') {
        mileStones.push({
          status: 'failed',
          mode: 'Normal Refund',
          timeStamp: refund.failed_at,
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

      if (refund.status === 'failed') {
        // If gateway_refund_support is false, means it failed due to upstream and was older payment
        if (refund.gateway_refund_support === false) {
          mileStones.push({
            text: (
              <div style={{ wordBreak: 'break-word' }}>
                Instant Refund for this payment has failed due to a bank issue and normal refund
                can't be processed for this payment as it was created more than 6 months ago{' '}
                <a
                  href="https://razorpay.com/docs/payment-gateway/refunds/#handling-errors"
                  href="https://razorpay.com/docs/payment-gateway/refunds/#handling-errors"
                  rel="noopener noreferrer"
                  target="_blank"
                >
                  Learn more
                </a>
              </div>
            ),
          });
        }

        mileStones.push({
          status: 'failed',
          mode: 'Instant Refund',
          timeStamp: refund.failed_at,
          infoText: `Instant Refund was unsuccessful, the fee & amount for instant refund has been reversed.`,
        });
      }
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
                <p class="refund-timeline-mode">
                  {item.mode}
                  {item.infoText && (
                    <span className="status-infotext">
                      <i class="i i-help" />
                      <Popover align="top" theme="dark">
                        <PopoverBody>{item.infoText}</PopoverBody>
                      </Popover>
                    </span>
                  )}
                </p>
                <p class="refund-timeline-timestamp">
                  <ShowTime time={item.timeStamp} />
                </p>
              </li>
            );
          } else {
            return (
              <li key={idx} class="hide-after">
                <div class="refund-timeline-text">{item.text}</div>
                {item.timeStamp && (
                  <p class="refund-timeline-timestamp">
                    <ShowTime time={item.timeStamp} />
                  </p>
                )}
              </li>
            );
          }
        })}
      </ul>
    );
  }
}
