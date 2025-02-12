import React, { Component } from 'react';

export default class TimeInput extends Component {
  constructor(props) {
    super(props);
    this.hrInput = React.createRef();
    this.minutesInput = React.createRef();
  }

  handleDaysChange = (e) => {
    const _val = e.target.value;

    if (_val >= 6) return;
    if (_val.length >= 1) this.hrInput.current.focus();
    this.props.handleValueChange(_val, 'days');
  };

  handleHoursChange = (e) => {
    let _val = e.target.value;

    if (_val >= 24 || _val === 0) return;

    if (_val >= 3 && _val <= 9) _val = `0${_val}`;

    if (_val.length >= 2) this.minutesInput.current.focus();

    this.props.handleValueChange(_val, 'hrs');
  };

  handleMinutesChange = (e) => {
    const _val = e.target.value;

    if (_val >= 61) return;

    this.props.handleValueChange(e.target.value, 'mins');
  };

  render() {
    const { values } = this.props;

    return (
      <div>
        <div className="input-mask" data-attr="day :">
          <input
            maxLength={1}
            placeholder="0"
            autoFocus
            onChange={this.handleDaysChange}
            value={values.days ? values.days : ''}
            type="number"
          />
        </div>
        <div className="input-mask" data-attr="hr :">
          <input
            maxLength={2}
            placeholder="00"
            ref={this.hrInput}
            onChange={this.handleHoursChange}
            value={values.hrs ? values.hrs : ''}
            type="number"
          />
        </div>
        <div className="input-mask" data-attr="minutes">
          <input
            maxLength={2}
            placeholder="00"
            ref={this.minutesInput}
            onChange={this.handleMinutesChange}
            value={values.mins ? values.mins : ''}
            type="number"
          />
        </div>
      </div>
    );
  }
}
