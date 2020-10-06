import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import moment from 'moment';
import { dateCalculator } from 'common/new-ui/Input/Calendar';
import { timeCalculator } from 'common/new-ui/Input/Time';
import { classList } from 'common/utils/rzp-utils';

class LinkExpiry extends React.Component {
  constructor(props) {
    super();

    this.state = {
      value: props.defaultValue,
      hasNoDate: !!props.defaultValue,
    };

    this.ref = React.createRef();
  }

  onDateChange = (date) => {
    const curSelectedDateTime = this.state.value;

    this.setState({
      value: date,
    });

    dateCalculator(date, curSelectedDateTime, this.updateDate);
  };

  onTimeChange = (date) => {
    const curDate = this.state.value;

    timeCalculator(date, curDate, this.updateDate);
  };

  updateDate = (ts) => {
    const value = moment(ts);

    this.props.onChange(value);
  };

  handleHasNoDate = (e) => {
    if (!e.target.checked && !this.state.value) {
      setTimeout(() => {
        this.ref.current.focus();
        this.ref.current.click();
      }, 10);

      this.props.onChange(this.state.value);
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

    const disabled = props.disabled || state.hasNoDate;

    return (
      <React.Fragment>
        {!isRequired && (
          <Input.Check
            autoRender
            label="Link Expiry"
            fieldLabel="Expire link after"
            class="Input--vTop"
            value={state.hasNoDate}
            onChange={this.handleHasNoDate}
            disabled={props.disabled}
          />
        )}

        <Input.Group
          required={isRequired}
          label={isRequired && 'Link Expiry'}
          class={classList(
            'InputGroup--near',
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
            />
            {!!state.value && (
              <Input.TimePicker
                readOnly
                placeholder="11:59PM"
                defaultValue={props.defaultValue}
                disabled={props.disabled}
                onChange={this.onTimeChange}
                addonAfter={<i class="i i-time" />}
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
