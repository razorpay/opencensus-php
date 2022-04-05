import React from 'react';
import { formatDate } from 'razorx/helpers/utils';

export default function Timeline(props) {
  const { data, stateLogs } = props;

  return (
    <details className="timeline-accordion">
      <summary className="timeline-header">
        <b className="timeline-description">Created at {formatDate(data.created_at)}</b>
        <img
          src={`${window.cdnBaseUrl}/static/assets/rewards/rewards_list_up_vector.svg`}
          className="timeline-arrow"
          alt="arrow-icon"
        />
      </summary>
      {stateLogs?.map(({ created_at, to_status, triggered_by }, index) => {
        const numberOfItems = stateLogs.length;
        return (
          <div key={created_at} className="timeline-item-container">
            {index !== numberOfItems - 1 ? (
              <img
                src="/dist/css/assets/tick.svg"
                alt="Tick icon"
                className="timeline-point-previous"
              />
            ) : (
              <div
                className={
                  ['Activated', 'Created'].includes(to_status)
                    ? 'timeline-point-current'
                    : 'timeline-point-terminated'
                }
              />
            )}
            {index !== numberOfItems - 1 && <div className="timeline-line" />}
            <div className="timeline-item-text-container">
              <span
                className={`timeline-item-title${
                  index !== numberOfItems - 1 ? ' timeline-item-previous' : ''
                }`}
              >
                {to_status} at {formatDate(created_at)}
              </span>
              <p
                className={`timeline-item-subtitle${
                  index !== numberOfItems - 1 ? ' timeline-item-previous' : ''
                }`}
              >
                by {triggered_by}
              </p>
            </div>
          </div>
        );
      })}
    </details>
  );
}
