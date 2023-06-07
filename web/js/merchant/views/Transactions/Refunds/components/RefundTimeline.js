import React from 'react';
import Time from 'common/ui/Time';
import { RefundStatusLabel } from 'merchant/components/StatusLabel';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';

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

        const speedChangeTime = refund.speed_change_time
          ? refund.speed_change_time
          : refund.created_at;

        mileStones.push(
          {
            text: (
              <div>
                Refund speed updated to Normal &nbsp;
                <span>
                  <i className="i i-help" />
                  <PopoverComponent align="right" theme="dark">
                    <PopoverBody>
                      Instant Refund was unsuccessful, the fee for instant refund has been reversed.
                    </PopoverBody>
                  </PopoverComponent>
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

    return mileStones;
  };

  render() {
    const { refund } = this.props;
    const mileStones = this.getMilestones(refund);

    return (
      <ul className="refund-timeline" data-testid="refund-timeline">
        {mileStones.map((item, idx) => {
          if (item.status) {
            const showProcessingTooltip =
              refund?.status === 'processing' && item.status === 'processing' && idx === 0;

            return (
              <li key={idx}>
                <div className="refund-timeline-status">
                  <RefundStatusLabel status={item.status} />
                  {showProcessingTooltip && (
                    <span className="refund-timeline-text refund-status-help">
                      <i className="i i-help" />
                      <PopoverComponent align="top" theme="dark">
                        <PopoverBody>
                          The refund has been initiated. Once the refund is completed, the status of
                          the refund will change to &#39;Processed&#39;.
                        </PopoverBody>
                      </PopoverComponent>
                    </span>
                  )}
                </div>
                <p className="refund-timeline-mode">
                  {item.mode}
                  {item.infoText && (
                    <span className="status-infotext">
                      <i className="i i-help" />
                      <PopoverComponent align="top" theme="dark">
                        <PopoverBody>{item.infoText}</PopoverBody>
                      </PopoverComponent>
                    </span>
                  )}
                </p>
                <p className="refund-timeline-timestamp">
                  <ShowTime time={item.timeStamp} />
                </p>
              </li>
            );
          } else {
            return (
              <li key={idx} className="hide-after">
                <div className="refund-timeline-text">{item.text}</div>
                {item.timeStamp && (
                  <p className="refund-timeline-timestamp">
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
