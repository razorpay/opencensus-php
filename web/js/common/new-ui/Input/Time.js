import React from 'react';
import Datetime from 'react-datetime';
import { classList } from 'common/utils/rzp-utils';

// eslint-disable-next-line import/no-cycle
import { Label, inputClass, separateDomProps } from './index';

export default class TimePicker extends React.Component {
  className = 'Input--TimePicker';
  state = {
    value: this.props.defaultValue, // Moment object
  };

  onChange = (value) => {
    this.setState({ value });

    if (this.props.onChange) {
      this.props.onChange(value, this.props.name);
    }
  };

  render() {
    const allProps = separateDomProps(this.props);
    const { onFocus, onBlur, ...restDOMProps } = allProps.props;

    return (
      <div class={inputClass(this)}>
        <Label text={this.props.label} />
        <div class="Input-content">
          <div class="Input-elWrapper" tabIndex="0">
            <Datetime
              defaultValue={allProps.defaultValue}
              value={this.state.value}
              onChange={this.onChange}
              inputProps={{
                ...restDOMProps,
                className: classList('Input-el', this.props.addonAfter && 'Input-el--after'),
              }}
              dateFormat={false}
              timeFormat="h:mm a"
              onFocus={onFocus}
              onBlur={onBlur}
            />
            {this.props.addonAfter && (
              <span class="Input-addons  Input-addons--after">{this.props.addonAfter}</span>
            )}
          </div>
        </div>
      </div>
    );
  }
}

/*
 * Helper fn. to be for onChange for Input.TimePicker
 * */
export function timeCalculator(date, curSelectedTS, onCalculation) {
  if (date && date.target) {
    // Check if date is not of event type
    return;
  }

  const selectedTime = date.valueOf();
  const dayStartTime = date.startOf('day').valueOf();

  const offsetTime = selectedTime - dayStartTime; // Offset since start of day

  const newSelectedTime = curSelectedTS.startOf('day').valueOf() + offsetTime;
  onCalculation(newSelectedTime);
}
