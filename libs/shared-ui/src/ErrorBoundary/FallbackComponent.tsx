import React from 'react';
import errorService from '@razorpay/universe-utils/errorService';

interface Props {
  eventId: string | null;
}

const FallbackComponent: React.FC<Props> = ({ eventId }) => {
  return (
    <div className="rzp-error-boundary">
      <div className="warning">
        <p>
          <b>An error occured, please try again later!</b>
        </p>
        <pre className="inline">Error Code: {eventId || 'NA'}</pre>
        <p>
          Our team has been notified, but{' '}
          {/* eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions, jsx-a11y/anchor-is-valid */}
          <a
            className="error-report-link"
            onClick={() => {
              errorService.showReportDialog();
            }}
          >
            click here
          </a>{' '}
          to fill out a report.
        </p>
      </div>
    </div>
  );
};

export default FallbackComponent;
