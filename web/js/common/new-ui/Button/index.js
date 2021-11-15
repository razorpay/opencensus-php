import React from 'react';
import { prevent, classList } from 'common/utils/rzp-utils';
import ErrorBoundary, { Ranks } from 'common/new-ui/ErrorBoundary';

const getPrimaryColorClass = (className) => classList(className, 'Button--primary');
const getTransparentColorClass = (className) => classList(className, 'Button--transparent');
const getSecondaryColorClass = (className) => classList(className, 'Button--secondary');

export default class Button extends React.PureComponent {
  render() {
    const { iconBefore, iconAfter, children, onClick, className, ...restProps } = this.props;

    return (
      <button
        {...restProps}
        onClick={restProps.disabled ? undefined : onClick}
        className={classList(className, 'Button')}
      >
        {iconBefore && <i className={`Button-icon Button-icon--before i-${iconBefore}`} />}
        {children}
        {iconAfter && <i className={`Button-icon Button-icon--after i-${iconAfter}`} />}
      </button>
    );
  }
}

// eslint-disable-next-line babel/new-cap
Button.Primary = (props) => <Button {...props} className={getPrimaryColorClass(props.className)} />;

Button.Secondary = (props) => (
  <Button {...props} className={getSecondaryColorClass(props.className)} />
);

Button.Transparent = (props) => (
  <Button {...props} className={getTransparentColorClass(props.className)} />
);

/*
* Async Button to show spinner if onClick returns promise
* @props *  - {Promise} onClick: optional, however if passed circular loader would be shown
  - Other props are passed as it is to Button component
* */
export class AsyncBtn extends React.PureComponent {
  state = { isPending: !!this.props.isPending };

  static getDerivedStateFromProps(nextProps, prevState) {
    if (typeof nextProps.isPending !== 'undefined' && nextProps.isPending !== prevState.isPending) {
      return {
        isPending: nextProps.isPending,
      };
    }
    return null;
  }

  onClick = (e) => {
    e.persist(); // e.prevenDefault makes synthetic even to get removed. Synthetic event is needed for performance reasons
    prevent(e);

    if (!this.state.isPending) {
      const returnValue = this.props.onClick && this.props.onClick(e);

      if (returnValue instanceof Promise) {
        this.setState({ isPending: true });

        returnValue.then((_) => this.setState({ isPending: false }));
        returnValue.catch((_) => this.setState({ isPending: false }));
      }
    }
  };

  render() {
    /*
     * Only 3 props are different than Button and has to be consumed here, not sent to Button component
     * Note: onClick needs to be consumed here
     * */
    let { children } = this.props;
    const { pendingState, onClick, showLoader = true, ...rest } = this.props;

    if (this.state.isPending) {
      children = (
        <span className="btn-pending">
          {pendingState}
          {showLoader && <span className="spin-btn white" />}
        </span>
      );
    }

    return (
      <ErrorBoundary
        FallbackComponent={() => (
          <button disabled className="Button">
            There was an issue, please try later!
          </button>
        )}
        rank={Ranks.P0}
        resetOnProps
      >
        <Button {...rest} onClick={this.onClick}>
          {children}
        </Button>
      </ErrorBoundary>
    );
  }
}

/*
 *  Same as Button.Primary along with Async functionality
 * */
AsyncBtn.Primary = (props) => (
  <AsyncBtn {...props} className={getPrimaryColorClass(props.className)} />
);

AsyncBtn.Secondary = (props) => (
  <AsyncBtn {...props} className={getSecondaryColorClass(props.className)} />
);

AsyncBtn.Transparent = (props) => (
  <AsyncBtn {...props} className={getTransparentColorClass(props.className)} />
);
