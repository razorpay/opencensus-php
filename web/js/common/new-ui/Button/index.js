import React from 'react';
import { prevent, classList } from 'common/utils/rzp-utils';

const PRIMARY_COLOR = (className) => classList(className, 'Button--primary');
const TRANSPARENT_COLOR = (className) => classList(className, 'Button--transparent');
const SECONDARY_COLOR = (className) => classList(className, 'Button--secondary');

export default class Button extends React.PureComponent {
  render() {
    let { iconBefore, iconAfter, children, onClick, className, ...restProps } = this.props;

    return (
      <button
        {...restProps}
        onClick={restProps.disabled ? undefined : onClick}
        class={classList(className, 'Button')}
      >
        {iconBefore && <i class={'Button-icon Button-icon--before i-' + iconBefore} />}
        {children}
        {iconAfter && <i class={'Button-icon Button-icon--after i-' + iconAfter} />}
      </button>
    );
  }
}

Button.Primary = (props) => <Button {...props} class={PRIMARY_COLOR(props.className)} />;

Button.Secondary = (props) => <Button {...props} class={SECONDARY_COLOR(props.className)} />;

Button.Transparent = (props) => <Button {...props} class={TRANSPARENT_COLOR(props.className)} />;

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
      let returnValue = this.props.onClick && this.props.onClick(e);

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
    let { children, pendingState, onClick, showLoader = true, ...rest } = this.props;

    if (this.state.isPending) {
      children = (
        <span class="btn-pending">
          {pendingState}
          {showLoader && <span class="spin-btn white" />}
        </span>
      );
    }

    return (
      <Button {...rest} onClick={this.onClick}>
        {children}
      </Button>
    );
  }
}

/*
 *  Same as Button.Primary along with Async functionality
 * */
AsyncBtn.Primary = (props) => <AsyncBtn {...props} class={PRIMARY_COLOR(props.className)} />;

AsyncBtn.Secondary = (props) => <AsyncBtn {...props} class={SECONDARY_COLOR(props.className)} />;

AsyncBtn.Transparent = (props) => (
  <AsyncBtn {...props} class={TRANSPARENT_COLOR(props.className)} />
);
