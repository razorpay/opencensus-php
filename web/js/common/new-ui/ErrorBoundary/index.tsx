import React, { Component, ErrorInfo, ReactNode } from 'react';
import InlineFallbackComponent from './FallbackComponent';
import errorService from '@razorpay/universe-utils/errorService';

enum Ranks {
  P0 = 'P0',
  P1 = 'P1',
  P2 = 'P2',
  P3 = 'P3',
}

enum Sections {
  ANALYTICS = 'analytics',
  HOME = 'home',
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
    errorService.captureError(error, {
      tags: this.props.tags,
      rank: this.props.rank || errorService.ErrorRank.P0,
      extra: {
        info,
      },
    });

    this.setState({ error, info, eventId: errorService.lastEventId() });
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

    if (error) {
      if (FallbackComponent) {
        return <FallbackComponent eventId={eventId} error={error} info={info} />;
      } else {
        return (
          <div
            /* @ts-expect-error */
            ref={(node) => (this.node = node)}
            className="rzp-error-boundary has-raven"
          >
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
                        errorService.showReportDialog();
                      }}
                    >
                      click here
                    </a>{' '}
                    to fill out a report.
                  </p>
                  {!!eventId && (
                    <p>
                      {' '}
                      Error Code: <code>{eventId}</code>
                    </p>
                  )}
                </div>
              </div>
            </div>
          </div>
        );
      }
    }
    return children || null;
  }
}

export { InlineFallbackComponent, Ranks, Sections };
