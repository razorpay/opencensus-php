import React, { Component, ErrorInfo, ReactNode } from 'react';
import InlineFallbackComponent from './FallbackComponent';

enum Ranks {
  P0 = 'P0',
  P1 = 'P1',
  P2 = 'P2',
  P3 = 'P3',
}

interface FallbackComponentProps extends React.FC<any> {
  eventId?: string | null;
}

interface Props {
  children: ReactNode;
  FallbackComponent?: FallbackComponentProps;
  tags?: any;
  rank?: Ranks;
  resetOnProps?: any;
}

interface State {
  error: any;
  info: ErrorInfo | null;
  eventId: string | null;
}

export default class ErrorBoundary extends Component<Props, State> {
  state = {
    error: false,
    info: null,
    eventId: null,
  };

  private node = React.createRef<HTMLDivElement>();
  componentDidCatch(error: Error, info: ErrorInfo): void {
    let eventId = null;
    if (window.Sentry) {
      window.Sentry.withScope((scope) => {
        let tags = this.props.tags || {};
        tags = { ...tags, rank: this.props.rank || Ranks.P0 };
        scope.setExtras(info);
        eventId = window.Sentry.captureException(error, {
          tags,
        });
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

  render(): ReactNode {
    const { children, FallbackComponent } = this.props;
    // eslint-disable-next-line @typescript-eslint/naming-convention
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
            /* @ts-expect-error */
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
                  {/* @ts-expect-error */}
                  <pre>{info.componentStack.replace(/^\n/gm, '')}</pre>
                  {/* Ignoring TS error as info can not be null */}
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

export { InlineFallbackComponent, Ranks };
