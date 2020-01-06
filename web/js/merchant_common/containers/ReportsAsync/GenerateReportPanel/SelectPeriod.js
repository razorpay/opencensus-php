import Input from 'common/new-ui/Input';
import { isNone } from 'common/utils/rzp-utils';

import { getTimeUnix, getStartAndEndUnixTimeStampsForDaysFrom } from '../utils';

const DEFAULT_SELECTED_DATE = moment()
  .subtract(1, 'day')
  .startOf('day');
const DEFAULT_SELECTED_END_AT = moment().endOf('day');
const DEFAULT_SELECTED_START_AT = moment()
  .subtract(1, 'day')
  .startOf('day');
const DEFAULT_SELECTED_MONTH = moment()
  .subtract(1, 'month')
  .startOf('month');
const DEFAULT_PERIOD = 'yesterday';

const DATE_DISPLAY_FORMAT = 'll';

export default class SelectPeriod extends React.Component {
  state = {
    values: {
      selectedPeriod: DEFAULT_PERIOD,
      selectedMonth: DEFAULT_SELECTED_MONTH,
      selectedDate: DEFAULT_SELECTED_DATE,
      selectedStartAt: DEFAULT_SELECTED_START_AT,
      selectedEndAt: DEFAULT_SELECTED_END_AT,
      selectedCustomConfigMonth: DEFAULT_SELECTED_MONTH,
    },
  };

  onWithTimeChange = ({ target }) => {
    this.setState({ withTime: target.checked });
  };

  onChange = ({ target }) => {
    const { name, value, checked } = target;

    this.setState({
      values: {
        ...this.state.values,
        [name]: !isNone(checked) ? checked : value,
      },
    });
  };

  onDateTimeChange = (value, name) => {
    const timeFieldSuffix = 'Time';
    if (name.endsWith(timeFieldSuffix)) {
      name = name.substring(0, name.indexOf(timeFieldSuffix));

      const dateValue = this.state.values[name].clone().startOf('day');
      const timeInUnix = getTimeUnix(value);

      value = dateValue.add(timeInUnix, 'seconds');
    } else {
      const timeInUnix = getTimeUnix(this.state.values[name]);
      value = value.add(timeInUnix, 'seconds');
    }

    const target = { value, name };
    this.onChange({ target });
  };

  onDateChange = (value, name) => {
    const target = { value, name };
    this.onChange({ target });
  };

  getDateRange = () => {
    const { selectedPeriod, ...values } = this.state.values;
    let startTime, endTime;
    switch (selectedPeriod) {
      case 'yesterday':
        return getStartAndEndUnixTimeStampsForDaysFrom(0);

      case 'last_7_days':
        return getStartAndEndUnixTimeStampsForDaysFrom(7);

      case 'last_month':
        return getStartAndEndUnixTimeStampsForMonth();

      case 'daily':
        return getStartAndEndUnixTimeStampsForDaysFrom(0, values.selectedDate);

      case 'monthly':
        return getStartAndEndUnixTimeStampsForMonth(values.selectedMonth);

      case 'dateRange':
        let { selectedStartAt, selectedEndAt } = values;
        if (!values.withTime) {
          selectedStartAt = selectedStartAt.clone().startOf('day');
          selectedEndAt = selectedEndAt.clone().endOf('day');
        }
        const startTimeUnix = selectedStartAt.format('X');
        const endTimeUnix = selectedEndAt.format('X');
        return [startTimeUnix, endTimeUnix];
    }
  };

  getCustomConfigYear = () => {
    const { selectedCustomConfigMonth } = this.state.values;
    const year = selectedCustomConfigMonth.year();

    const month = selectedCustomConfigMonth.month() + 1; // January

    return { year, month };
  };

  renderSelectPeriodForCustomConfig() {
    const { selectedCustomConfigMonth } = this.state.values;
    return (
      <div className="InputGroup--inline">
        <div className="Input-content">
          <SelectMonth
            onDateChange={this.onDateChange}
            selectedMonth={selectedCustomConfigMonth}
            name="selectedCustomConfigMonth"
            allowToday={false}
          />
        </div>
      </div>
    );
  }

  render() {
    const { selectedPeriod, withTime, ...defaults } = this.state.values;

    const { avlblPeriodOptions = [], isCustomConfig } = this.props;
    return !isCustomConfig ? (
      <Input.Group class="InputGroup--inline">
        <div class="Input-content">
          <div class="Input">
            <Input.Select
              label="Select Period"
              options={avlblPeriodOptions}
              size="half_big"
              class="Input--vTop"
              name="selectedPeriod"
              onChange={this.onChange}
            />

            <PredefinedPeriodDurations selectedPeriod={selectedPeriod} />

            {selectedPeriod === 'dateRange' && (
              <div class="m-t">
                <Input.Check
                  name="withTime"
                  fieldLabel="Specify Time"
                  onChange={this.onChange}
                  defaultValue={withTime}
                />
              </div>
            )}
          </div>

          <SelectInterval
            selectedPeriod={selectedPeriod}
            onDateChange={this.onDateChange}
            onDateTimeChange={this.onDateTimeChange}
            withTime={withTime}
            defaults={defaults}
          />
        </div>
      </Input.Group>
    ) : (
      this.renderSelectPeriodForCustomConfig()
    );
  }
}

function SelectInterval({
  selectedPeriod,
  onDateChange,
  onDateTimeChange,
  withTime,
  defaults,
}) {
  switch (selectedPeriod) {
    case 'monthly':
      return (
        <SelectMonth
          onDateChange={onDateChange}
          selectedMonth={defaults.selectedMonth}
          name="selectedMonth"
        />
      );
    case 'daily':
      return (
        <SelectSingleDay
          onDateChange={onDateChange}
          selectedDate={defaults.selectedDate}
        />
      );
    case 'dateRange':
      return (
        <SelectRange
          onDateChange={onDateTimeChange}
          withTime={withTime}
          selectedStartAt={defaults.selectedStartAt}
          selectedEndAt={defaults.selectedEndAt}
        />
      );
  }
  return null;
}

function SelectMonth({ onDateChange, selectedMonth, name, allowToday = true }) {
  return (
    <Input.ToCalendar
      type="month"
      name={name}
      placement="topLeft"
      addonAfter={<i class="i i-date-range" />}
      defaultValue={selectedMonth}
      label="Select Month"
      class="Input--vTop"
      onChange={onDateChange}
      allowToday={allowToday}
    />
  );
}

function SelectSingleDay({ selectedDate, ...props }) {
  return (
    <SelectDate
      name="selectedDate"
      placeholder="Select Day"
      defaultValue={selectedDate}
      label="Select Date"
      {...props}
    />
  );
}

function SelectDate({ withTime, onDateChange, ...props }) {
  return (
    <div class="Input">
      <Input.ToCalendar
        name="selectedDate"
        placement="topLeft"
        addonAfter={<i class="i i-date-range" />}
        class="Input--vTop"
        onChange={onDateChange}
        {...props}
      />
      {withTime && (
        <div class="m-t">
          <Input.TimePicker
            name={props.name + 'Time'}
            placeholder="HH:MM A"
            addonAfter={<i class="i i-time" />}
            defaultValue={props.defaultValue}
            onChange={onDateChange}
          />
        </div>
      )}
    </div>
  );
}

export function SelectRange({ selectedStartAt, selectedEndAt, ...props }) {
  return (
    <>
      <SelectDate
        name="selectedStartAt"
        placeholder="Start At"
        defaultValue={selectedStartAt}
        label="Start At"
        {...props}
      />

      <SelectDate
        name="selectedEndAt"
        placeholder="End At"
        defaultValue={selectedEndAt}
        label="End At"
        {...props}
      />
    </>
  );
}

function PredefinedPeriodDurations({ selectedPeriod }) {
  const today = moment();
  let fromDate, toDate;
  switch (selectedPeriod) {
    case 'yesterday':
      fromDate = today
        .clone()
        .subtract(1, 'day')
        .format(DATE_DISPLAY_FORMAT);
      break;

    case 'last_7_days':
      const previousDay = today.clone().subtract(1, 'day');
      toDate = previousDay.format(DATE_DISPLAY_FORMAT);

      // calculating last 7th day from today which last 6th day from yesterday
      fromDate = previousDay.subtract(6, 'day').format(DATE_DISPLAY_FORMAT);
      break;

    case 'last_month':
      const lastDayOfLastMonth = today
        .clone()
        .startOf('month')
        .subtract(1, 'day');
      toDate = lastDayOfLastMonth.format(DATE_DISPLAY_FORMAT);
      fromDate = lastDayOfLastMonth
        .startOf('month')
        .format(DATE_DISPLAY_FORMAT);
      break;

    default:
      return null;
  }

  return (
    <div class="m-t">
      <small class="text-warning">
        {fromDate} {toDate && ` to ${toDate}`}
      </small>
    </div>
  );
}

// default month is last month
function getStartAndEndUnixTimeStampsForMonth(
  momentDate = moment().subtract(1, 'month')
) {
  const lastDayOfLastMonthEndOfDayUnix = momentDate.endOf('month').format('X');
  const firstDayOfLastMonthStartOfDayUnix = momentDate
    .startOf('month')
    .format('X');

  return [firstDayOfLastMonthStartOfDayUnix, lastDayOfLastMonthEndOfDayUnix];
}
