/*
  Custom Checkbox for redux-form Field component
  Since our backend expects 0/1, this component addresses the indiscrepancy between the checkbox values (0/1 & false/true)

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
  };

  toggle = e => {
    // it's an actual click, not triggered syntheticmouseevent due to form submission
    if (e.pageX && e.pageY) {
      const onChange = this.props.onChange;
      const isChecked = !this.state.checked;

      this.setState(
        { checked: isChecked },
        _ => onChange && onChange(isChecked)
      );
    }

    e.preventDefault();
  };

  render() {
    let { checked } = this.state;

    let buttonClass = this.buttonClass + ' checkbox-knob--' + this.props.type;
    if (checked) {
      buttonClass += ' checked';
    }

    return (
      <button
        {...this.props}
        class={buttonClass}
        value={checked}
        onClick={this.toggle}
      />
    );
  }
}
