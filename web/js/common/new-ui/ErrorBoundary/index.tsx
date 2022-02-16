import React, { Component, ErrorInfo, ReactNode } from 'react';
import InlineFallbackComponent from './FallbackComponent';
import errorService from '@razorpay/universe-utils/errorService';
// TODO: Fix the import .ts issue
import { Ranks, Teams } from './constants'; // Failing to load in .ts format
import { getTeamName } from 'common/new-ui/ErrorBoundary/utils';

interface FallbackComponentProps extends React.FC<any> {
  eventId?: string | null;
}

interface Props {
  children: ReactNode;
  FallbackComponent?: FallbackComponentProps;
  tags?: any;
  rank?: Ranks;
  team?: Teams;
  resetOnProps?: any;
  location?: any;
}

interface State {
  error: any;
  info: ErrorInfo | null;
  eventId: string | null;
}

class ErrorBoundary extends Component<Props, State> {
  state = {
    error: false,
    info: null,
    eventId: null,
  };

  private node = React.createRef<HTMLDivElement>();
  componentDidCatch(error: Error, info: ErrorInfo): void {
    const rank = this.props.rank || errorService.ErrorRank.P0;
    let tags = this.props.tags;

    const pathname = window.location.pathname;
    const team = this.props.team || getTeamName(pathname);

    // merge tags with extra tags
    tags = { ...tags, route: pathname, team };

    errorService.captureError(error, {
      tags,
      rank,
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

export default ErrorBoundary;

export { InlineFallbackComponent, Ranks, Teams };
