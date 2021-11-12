import React, { Component } from 'react';

export default class ErrorBoundary extends Component {
  state = {
    error: false,
    info: null,
    eventId: null,
  };
  componentDidCatch(error, info) {
    let eventId = null;
    if (window.Sentry) {
      window.Sentry.withScope((scope) => {
        scope.setExtras(info);
        eventId = window.Sentry.captureException(error);
      });
    } else if (window.Raven) {
      console.log(error, info);
      window.Raven.captureException(error, { extra: info });
    } else {
      console.error(error, info);
    }

    this.setState({ error, info, eventId });
  }

  componentWillReceiveProps() {
    if (this.props.resetOnProps) {
      this.setState({ error: false, info: null });
    }
  }

  render() {
    const { children, FallbackComponent } = this.props;
    const { error, info, eventId } = this.state;
    const SDK = window.Sentry || window.Raven;
    const hasSDK = !!SDK;
    const lastEventId = eventId || (window.Raven && window.Raven.lastEventId());

    if (error) {
      if (FallbackComponent !== undefined) {
        return FallbackComponent ? (
          <FallbackComponent eventId={lastEventId} error={error} info={info} />
        ) : null;
      } else {
        return (
          <div
            ref={(node) => (this.node = node)}
            className={`rzp-error-boundary${hasSDK ? ' has-raven' : ''}`}
          >
            {hasSDK && (
              <div className="js-error-container">
                <div className="js-error-content">
                  <div className="js-error-illustration m-b" />
                  <div className="js-error-text">
                    <p>We're sorry — something's gone wrong.</p>
                    <p>
                      Our team has been notified, but{' '}
                      <a
                        className="error-report-link"
                        onClick={() => {
                          SDK.showReportDialog({ eventId: lastEventId });
                        }}
                      >
                        click here
                      </a>{' '}
                      to fill out a report.
                    </p>
                    {!!lastEventId && (
                      <p>
                        {' '}
                        Error Code: <code>{lastEventId}</code>
                      </p>
                    )}
                  </div>
                </div>
              </div>
            )}
            {!hasSDK && (
              <div className="js-error-details">
                <banner className="warning">
                  <p>
                    <b>An Error Occured</b>
                  </p>
                  <pre>{error.toString()}</pre>
                  <pre>{info.componentStack.replace(/^\n/gm, '')}</pre>
                </banner>
              </div>
            )}
          </div>
        );
      }
    }
    return children || null;
  }
}
