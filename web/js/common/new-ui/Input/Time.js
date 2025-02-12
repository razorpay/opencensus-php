import React from 'react';
import Datetime from 'react-datetime';
import { classList } from 'common/utils/rzp-utils';

import { Label, inputClass, separateDomProps } from './index';

export default class TimePicker extends React.Component {
  className = 'Input--TimePicker';
  state = {
    value: this.props.defaultValue, // Moment object
  };

  focus = () => {
    // eslint-disable-next-line react/no-unused-state
    this.setState({ focus: true });
  };

  blur = () => {
    // eslint-disable-next-line react/no-unused-state
    this.setState({ focus: false });
  };

  onChange = (value) => {
    this.setState({ value });

    this.props.onChange && this.props.onChange(value, this.props.name);
  };

  render() {
    const allProps = separateDomProps(this.props);
    const { onFocus, onBlur, ...restDOMProps } = allProps.props; // onFocus and onBlur are not to be controllled by <input> here

    return (
      <div className={inputClass(this)}>
        <Label text={this.props.label} />
        <div className="Input-content">
          <div className="Input-elWrapper" tabIndex="0">
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
              onFocus={this.focus}
              onBlur={this.blur}
              timeConstraints={this.props.timeConstraints || {}}
            />
            {this.props.addonAfter && (
              <span className="Input-addons  Input-addons--after">{this.props.addonAfter}</span>
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
