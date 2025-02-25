import React, { Component, ErrorInfo, ReactNode } from 'react';
import errorService from '@razorpay/universe-cli/errorService';
import { DASHBOARD_PRIORITY_RANKS, DASHBOARD_TEAMS } from '@libs/shared-types'; // Failing to load in .ts format
import {DASHBOARD_ROUTES, getTeamName} from "@libs/shared-utils";

interface FallbackComponentProps {
  /** Optional event ID associated with the error */
  eventId?: string | null;
  /** The error that occurred */
  error?: boolean;
  /** Information about the error */
  info?: ErrorInfo | null;
}

/**
 * Props for the ErrorBoundary component.
 */
interface Props {
  /** The components to wrap with the Error Boundary */
  children: ReactNode; 
  /** Optional fallback component to render on error */
  FallbackComponent?: React.FC<FallbackComponentProps>; 
  /** Additional tags for error reporting */
  tags?: Record<string, any>; 
  /** Priority rank for the error (defined in shared types) */
  rank?: DASHBOARD_PRIORITY_RANKS; 
  /** Team associated with the error (defined in shared types) */
  team?: DASHBOARD_TEAMS; 
  /** Reset error state when this prop changes */
  resetOnProps?: boolean; 
  /** Optional location object (if using with routing) */
  location?: any; 
}

/**
 * State for the ErrorBoundary component.
 */
interface State {
  /** Indicates if an error has occurred */
  error?: boolean; 
  /** Error information if an error has occurred */
  info: ErrorInfo | null; 
  /** Event ID for the captured error */
  eventId: string | null; 
}

/**
 * ErrorBoundary component that captures JavaScript errors in its child component tree.
 *
 * @example
 * <ErrorBoundary>
 *   <MyComponent />
 * </ErrorBoundary>
 */
export class ErrorBoundary extends Component<Props, State> {
  state: State = {
    error: false,
    info: null,
    eventId: null,
  };

  private node = React.createRef<HTMLDivElement>();

  componentDidCatch(error: Error, info: ErrorInfo): void {
    const rank = this.props.rank || errorService.ErrorRank.P0;
    let tags = this.props.tags;

    const pathname = window.location.pathname;
    const team = this.props.team || getTeamName(pathname as keyof typeof DASHBOARD_ROUTES);

    // Merge tags with extra tags
    tags = { ...tags, route: pathname, team };

    errorService.captureError(error, {
      tags,
      rank,
      extra: {
        info,
      },
    });

    this.setState({ error: true, info, eventId: errorService.lastEventId() });
  }

  UNSAFE_componentWillReceiveProps(nextProps: Props): void {
    if (nextProps.resetOnProps && !this.props.resetOnProps) {
      this.setState({ error: false, info: null });
    }
  }

  render(): ReactNode {
    const { children, FallbackComponent } = this.props;
    const { error, info, eventId } = this.state;

    if (error) {
      if (FallbackComponent) {
        return <FallbackComponent eventId={eventId} error={error} info={info} />;
      } else {
        return (
          <div
            ref={this.node}
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
                  {eventId ? (
                    <p>
                      Error Code: <code>{eventId}</code>
                    </p>
                  ) : null}
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
