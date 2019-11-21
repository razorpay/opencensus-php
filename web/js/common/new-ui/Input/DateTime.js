import moment from 'moment';
import Input, { Description } from 'common/new-ui/Input';
import { dateCalculator } from 'common/new-ui/Input/Calendar';
import { timeCalculator } from 'common/new-ui/Input/Time';
import { classList } from 'common/utils/rzp-utils';

export default class DateTime extends React.Component {
  state = {
    value: this.props.defaultValue,
    hasNoDate: this.props.required ? false : !this.props.defaultValue,
  };

  onDateChange = date => {
    const curSelectedDateTime = this.state.value;

    dateCalculator(date, curSelectedDateTime, this.updateDate);
  };

  onTimeChange = date => {
    const curDate = this.state.value;

    timeCalculator(date, curDate, this.updateDate);
  };

  updateDate = ts => {
    const newDate = moment(ts);

    this.setState({
      value: newDate,
    });

    this.props.onChange && this.props.onChange(newDate);
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
    } = this.props;

    return (
      <React.Fragment>
        {!required && (
          <Input.Check
            label={label}
            className={className}
            fieldLabel={checkboxFieldLabel}
            defaultValue={this.props.defaultValue ? '0' : '1'}
            value={hasNoDate}
            onChange={e => {
              if (!e.target.checked) {
                setTimeout(() => {
                  document.querySelector('[data-name="date"]').focus();
                  document.querySelector('[data-name="date"]').click();
                }, 10);
              } else {
                this.props.onChange && this.props.onChange(null);
              }

              this.setState({
                hasNoDate: e.target.checked,
              });
            }}
          />
        )}
        <Input.Group
          class={classList(
            !required && 'InputGroup--near',
            isInline ? 'InputGroup--inline' : 'Input--half_big'
          )}
        >
          <div class="Input-content" style={{ marginTop: required ? -8 : 0 }}>
            <Input.ToCalendar
              data-name="date"
              placeholder="15-04-2018"
              defaultValue={moment(value)}
              disabled={hasNoDate}
              readOnly={true}
              onChange={this.onDateChange}
              size={isInline ? 'half_small' : 'half'}
              addonAfter={<i class="i i-date-range" />}
              placement="topLeft"
              allowToday={true}
              disablePastDates={true}
              required={required}
            />
            {!!value && (
              <Input.TimePicker
                placeholder="11:59PM"
                defaultValue={moment(value)}
                disabled={hasNoDate}
                readOnly={true}
                onChange={this.onTimeChange}
                size={isInline ? 'half_small' : 'half'}
                addonAfter={<i class="i i-time" />}
                required={required}
              />
            )}
            {description && <Description text={description} />}
          </div>
        </Input.Group>
      </React.Fragment>
    );
  }
}
