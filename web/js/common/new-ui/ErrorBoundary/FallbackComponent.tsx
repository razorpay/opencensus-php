import React from 'react';

interface Props {
  eventId: string | null;
}

const FallbackComponent: React.FC<Props> = ({ eventId }) => {
  return (
    <div className="rzp-error-boundary">
      <banner className="warning">
        <p>
          <b>An error occured, please try again later!</b>
        </p>
        <pre className="inline">Error Code: {eventId || 'NA'}</pre>
        <p>
          Our team has been notified, but{' '}
          <a
            className="error-report-link"
            onClick={() => {
              console.log(`window.Sentry.showReportDialog({ eventId }); called`);
              window.Sentry.showReportDialog({ eventId });
            }}
          >
            click here
          </a>{' '}
          to fill out a report.
        </p>
      </banner>
    </div>
  );
};

export default FallbackComponent;
