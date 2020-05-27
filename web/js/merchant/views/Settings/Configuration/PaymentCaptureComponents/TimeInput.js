import { Component } from 'react';

export default class TimeInput extends Component {
  constructor(props) {
    super(props);
    this.hrInput = React.createRef();
    this.minutesInput = React.createRef();
  }

  handleDaysChange = e => {
    this.hrInput.current.focus();
    this.props.handleValueChange(e.target.value, 'days');
  };

  handleHoursChange = e => {
    e.target.value.length >= 2 ? this.minutesInput.current.focus() : null;
    this.props.handleValueChange(e.target.value, 'hrs');
  };

  handleMinutesChange = e => {
    this.props.handleValueChange(e.target.value, 'mins');
  };

  render() {
    const { values } = this.props;

    return (
      <div>
        <div class="input-mask" data-attr="day :">
          <input
            maxLength={1}
            placeholder="0"
            autoFocus
            onChange={this.handleDaysChange}
            value={values.days ? values.days : ''}
          />
        </div>
        <div class="input-mask" data-attr="hr :">
          <input
            maxLength={2}
            placeholder="00"
            ref={this.hrInput}
            onChange={this.handleHoursChange}
            value={values.hrs ? values.hrs : ''}
          />
        </div>
        <div class="input-mask" data-attr="minutes">
          <input
            maxLength={2}
            placeholder="00"
            ref={this.minutesInput}
            onChange={this.handleMinutesChange}
            value={values.mins ? values.mins : ''}
          />
        </div>
      </div>
    );
  }
}
