import React from 'react';

import Input, { Description, Label } from 'common/new-ui/Input';
import ErrorBoundary, { InlineFallbackComponent } from 'common/new-ui/ErrorBoundary';
import { classList } from 'common/utils/rzp-utils';

export default class SelectBox extends React.Component {
  static defaultProps = {
    onClick: () => {},
  };

  constructor(props) {
    super();

    this.state = {
      checked: !!props.defaultChecked,
    };
  }

  get isControlled() {
    return typeof this.props.checked !== 'undefined';
  }

  onChange = () => {
    if (this.isControlled) {
      this.props.onClick(!this.props.checked);

      return;
    }

    this.setState(
      {
        // eslint-disable-next-line react/no-access-state-in-setstate
        checked: !this.state.checked,
      },
      () => {
        this.props.onClick(this.state.checked);
      },
    );
  };

  render() {
    const { props } = this;

    const otherProps = {};

    if (props.disabled) {
      otherProps.disabled = props.disabled;
    }

    const checked = this.isControlled ? props.checked : this.state.checked;

    return (
      <ErrorBoundary FallbackComponent={InlineFallbackComponent} resetOnProps>
        <div
          className={classList('SelectBox', props.className, checked && 'checked')}
          {...otherProps}
        >
          <div className="SelectBox-content">
            <Label text={props.label} />

            {props.children}

            <Description text={props.description} />
          </div>

          <div className="SelectBox-action">
            <Input.Check
              name={props.name}
              checked={checked}
              onBlur={props.onBlur}
              onChange={this.onChange}
              autoRender
            />
          </div>
        </div>
      </ErrorBoundary>
    );
  }
}
