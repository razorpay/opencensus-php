import { classList } from 'common/util';

/*
  Custom Checkbox as a Switch toggle UI

  Usage:
    <SwitchField
      name='some_name'
      onChange={this.someHandler}
      defaultChecked={true}
      {...otherProps}
    />
*/

export default class SwitchField extends React.Component {
  buttonClass = 'checkbox-knob';
  type = this.props.type || 'default';

  state = {
    checked: !!this.props.defaultChecked || false,
    isActionPending: false,
  };

  get isControlled() {
    return (
      typeof this.props.checked !== 'undefined' &&
      typeof this.props.onChange === 'function'
    );
  }

  toggle = e => {
    // it's an actual click, not triggered syntheticmouseevent due to form submission
    if (e.pageX && e.pageY) {
      const onChange = this.props.onChange;
      const isChecked = !this.state.checked;

      if (this.props.disabled) return;

      this.setState({ checked: isChecked }, _ => {
        const self = this;

        if (onChange) {
          function postActionCB(isSuccess) {
            if (!isSuccess) {
              setTimeout(
                () =>
                  self.setState({
                    checked: !isChecked,
                    isActionPending: false,
                  }),
                100
              ); // Revert if false
            } else {
              self.setState({ isActionPending: false });
            }
          }

          const actionCall = onChange(isChecked, postActionCB);

          if (actionCall && actionCall.then) {
            self.setState({ isActionPending: true });
          }
        }
      });
    }

    e.preventDefault();
  };

  render() {
    let { checked, isActionPending } = this.state;
    checked = this.isControlled ? this.props.checked : checked;

    let buttonClass = this.buttonClass + ' checkbox-knob--' + this.props.type;
    if (checked) {
      buttonClass += ' checked';
    }

    return (
      <button
        {...this.props}
        class={classList(
          buttonClass,
          isActionPending && 'checkbox-knob--pending'
        )}
        value={checked}
        onClick={this.toggle}
        disabled={isActionPending}
      />
    );
  }
}
