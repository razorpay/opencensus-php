import React, { Component } from 'react';

export default class ErrorBoundary extends Component {
  state = {
    error: false,
    info: null,
    eventId: null,
  };
  componentDidMount() {
    if (typeof Sentry !== 'undefined') {
      Sentry.forceLoad();
    }
  }
  // componentDidCatch(error, info) {
  //   let eventId = null;
  //   if (window.Sentry) {
  //     Sentry.withScope(scope => {
  //       scope.setExtras(info);
  //       eventId = Sentry.captureException(error);
  //     });
  //   } else if (window.Raven) {
  //     console.log(error, info);
  //     Raven.captureException(error, { extra: info });
  //   } else {
  //     console.error(error, info);
  //   }

  //   this.setState({ error, info, eventId });
  // }

  componentWillReceiveProps() {
    if (this.props.resetOnProps) {
      this.setState({ error: false, info: null });
    }
  }

  render() {
    const SDK = window.Sentry || window.Raven;
    const hasSDK = !!SDK;
    const lastEventId = this.state.eventId || (window.Raven && Raven.lastEventId());

    if (this.state.error) {
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
                <pre>{this.state.error.toString()}</pre>
                <pre>{this.state.info.componentStack.replace(/^\n/gm, '')}</pre>
              </banner>
            </div>
          )}
        </div>
      );
    }
    return this.props.children || null;
  }
}
