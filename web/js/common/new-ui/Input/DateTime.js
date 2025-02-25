import React from "react";
import moment from 'moment';
import Input, { Description, Error, inputClass } from 'common/new-ui/Input';
import { dateCalculator } from 'common/new-ui/Input/Calendar';
import { timeCalculator } from 'common/new-ui/Input/Time';
import { classList } from 'common/utils/rzp-utils';

export default class DateTime extends React.Component {
  state = {
    value: this.props.defaultValue,
    hasNoDate: this.props.required ? false : !this.props.defaultValue,
    mature: this.props.mature || false,
  };

  constructor(props) {
    super(props);
    this.ref = React.createRef();
  }

  onDateChange = (date) => {
    const curSelectedDateTime = this.state.value;

    dateCalculator(date, curSelectedDateTime, this.updateDate);
  };

  onTimeChange = (date) => {
    const curDate = this.state.value;

    timeCalculator(date, curDate, this.updateDate);
  };

  updateDate = (ts) => {
    const value = moment(ts);
    const error = this.props.validator && this.props.validator(value);
    this.props.onChange && this.props.onChange(value);
    this.setState({ error, mature: true, value });
  };

  render() {
    const { value, hasNoDate } = this.state;
    const {
      label,
      checkboxFieldLabel,
      className,
      required = false,
      isInline,
      description,
      disabled,
      autoRender,
      dateTimeInputClass,
      defaultValue,
    } = this.props;
    return (
      <React.Fragment>
        {!required && (
          <Input.Check
            autoRender={autoRender}
            label={label}
            className={className}
            fieldLabel={checkboxFieldLabel}
            defaultValue={this.props.defaultValue ? '0' : '1'}
            value={hasNoDate}
            onChange={(e) => {
              if (!e.target.checked) {
                setTimeout(() => {
                  this.ref.current.focus();
                  this.ref.current.click();
                }, 10);

                if (value) {
                  this.props.onChange && this.props.onChange(value);
                }
              } else {
                this.props.onChange && this.props.onChange(null);
              }

              this.setState({
                hasNoDate: e.target.checked,
              });
            }}
            disabled={disabled}
          />
        )}
        <Input.Group
          label={(required && label) || null}
          className={classList(
            inputClass(this),
            !required && 'InputGroup--near',
            isInline ? 'InputGroup--inline' : 'Input--half_big',
            dateTimeInputClass,
          )}
          disabled={disabled}
        >
          <div className="Input-content" style={{ marginTop: required ? -8 : 0 }}>
            <Input.ToCalendar
              autoRender
              data-name="date"
              placeholder="DD-MM-YYYY"
              defaultValue={defaultValue && moment(defaultValue)}
              disabled={hasNoDate}
              readOnly={true}
              onChange={this.onDateChange}
              size={isInline ? 'half_small' : 'half'}
              addonAfter={<i className="i i-date-range" />}
              placement="topLeft"
              allowToday={true}
              disablePastDates={true}
              required
              ref={this.ref}
            />
            {!!value && (
              <Input.TimePicker
                placeholder="11:59PM"
                defaultValue={moment(value)}
                disabled={hasNoDate}
                readOnly={true}
                onChange={this.onTimeChange}
                size={isInline ? 'half_small' : 'half'}
                addonAfter={<i className="i i-time" />}
                required
              />
            )}
            {description && <Description text={description} />}
            <Error text={this.state.error} />
          </div>
        </Input.Group>
      </React.Fragment>
    );
  }
}
