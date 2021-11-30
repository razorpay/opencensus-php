import React from 'react';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import moment from 'moment';
import { dateCalculator } from 'common/new-ui/Input/Calendar';
import { timeCalculator } from 'common/new-ui/Input/Time';
import { classList } from 'common/utils/rzp-utils';
import track from '../../track';

class LinkExpiry extends React.Component {
  constructor(props) {
    super();

    this.state = {
      value: props.defaultValue,
      hasNoDate: null,
    };

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

    this.setState({
      value,
    });

    this.props.onChange(value);
  };

  handleHasNoDate = (e) => {
    if (!e.target.checked) {
      setTimeout(() => {
        this.ref.current.focus();
        this.ref.current.click();
      }, 10);

      if (this.state.value) {
        this.props.onChange(this.state.value);
      }
    } else {
      this.props.onChange(null);
    }

    this.setState({
      hasNoDate: e.target.checked,
    });
  };

  render() {
    const { props, state } = this;
    const isRequired = props.required;

    const disabled = props.disabled || state.hasNoDate === true;

    const noExpiryProps = {};
    if (props.defaultValue) {
      noExpiryProps.defaultValue = '1';
    }

    return (
      <React.Fragment>
        {!isRequired && (
          <Input.Check
            autoRender
            label="Link Expiry"
            fieldLabel="No Expiry"
            class="Input--vTop mobile-field"
            labelClass="Input-label pb-8"
            onChange={this.handleHasNoDate}
            disabled={props.disabled}
            {...noExpiryProps}
          />
        )}

        <Input.Group
          required={isRequired}
          label={isRequired && 'Link Expiry'}
          class={classList(
            !isRequired && 'InputGroup--near',
            'InputGroup--inline',
            isRequired && 'InputGroup--vTop',
          )}
          disabled={disabled}
        >
          <div class="Input-content">
            <Input.ToCalendar
              readOnly
              allowToday
              disablePastDates
              placeholder="DD-MM-YYYY"
              placement="topLeft"
              size="half"
              defaultValue={props.defaultValue}
              onChange={this.onDateChange}
              addonAfter={<i class="i i-date-range" />}
              ref={this.ref}
              onBlur={() => {
                track.lj.fields.expiryDate();
                track.segment.fields.expiryDate();
              }}
            />
            {!!state.value && (
              <Input.TimePicker
                readOnly
                placeholder="11:59PM"
                defaultValue={props.defaultValue}
                onChange={this.onTimeChange}
                addonAfter={<i class="i i-time" />}
                onBlur={() => {
                  track.lj.fields.expiryTime();
                  track.segment.fields.expiryTime();
                }}
              />
            )}
          </div>
        </Input.Group>
      </React.Fragment>
    );
  }
}

const mapStateTopProps = (state) => ({
  required: state.session.user.isExpireByRequired,
});

export default connect(mapStateTopProps)(LinkExpiry);
