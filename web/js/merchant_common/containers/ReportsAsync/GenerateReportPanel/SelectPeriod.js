import Input from 'common/new-ui/Input';

const DEFAULT_MONTH = moment();

export default class SelectPeriod extends React.Component {
  state = {
    withTime: false,
  };

  onWithTimeChange = ({ target }) => {
    this.setState({ withTime: target.checked });
  };

  render() {
    const {
      avlblPeriodOptions = [],
      selectedPeriod,
      onDateChange,
    } = this.props;
    return (
      <Input.Group class="InputGroup--inline">
        <div class="Input-content">
          <div class="Input">
            <Input.Select
              label="Select Period"
              options={avlblPeriodOptions}
              size="small"
              class="Input--vTop"
              name="selectedPeriod"
              defaultValue={selectedPeriod}
            />

            {selectedPeriod === 'dateRange' && (
              <div class="m-t">
                <Input.Check
                  name="withTime"
                  fieldLabel="Specify Time"
                  onChange={this.onWithTimeChange}
                />
              </div>
            )}
          </div>

          <SelectInterval
            selectedPeriod={selectedPeriod}
            onDateChange={onDateChange}
            withTime={this.state.withTime}
          />
        </div>
      </Input.Group>
    );
  }
}

function SelectInterval({ selectedPeriod, onDateChange, withTime }) {
  switch (selectedPeriod) {
    case 'monthly':
      return <SelectMonth onDateChange={onDateChange} />;
    case 'daily':
      return <SelectSingleDay onDateChange={onDateChange} />;
    case 'dateRange':
      return <SelectRange withTime={withTime} />;
  }
  return null;
}

function SelectMonth({ onDateChange }) {
  return (
    <Input.ToCalendar
      type="month"
      name="selectedMonth"
      placement="topLeft"
      addonAfter={<i class="i i-date-range" />}
      defaultValue={DEFAULT_MONTH}
      label="Select Month"
      class="Input--vTop"
      onChange={onDateChange}
    />
  );
}

function SelectSingleDay(props) {
  return (
    <SelectDate
      name="selectedDate"
      placeholder="Select Day"
      defaultValue={DEFAULT_MONTH}
      label="Select Date"
      {...props}
    />
  );
}

function SelectDate({ withTime, ...props }) {
  return (
    <div class="Input">
      <Input.ToCalendar
        name="selectedDate"
        placement="topLeft"
        addonAfter={<i class="i i-date-range" />}
        class="Input--vTop"
        {...props}
      />
      {withTime && (
        <div className="m-t">
          <Input.TimePicker
            name={props.name + 'AtTime'}
            placeholder="HH:MM A"
            addonAfter={<i class="i i-time" />}
            defaultValue={DEFAULT_MONTH}
          />
        </div>
      )}
    </div>
  );
}

export function SelectRange({ withTime }) {
  return (
    <>
      <SelectDate
        name="selectedStartAt"
        placeholder="Start At"
        defaultValue={DEFAULT_MONTH}
        label="Start At"
        withTime={withTime}
      />

      <SelectDate
        name="selectedEndAt"
        placeholder="End At"
        defaultValue={DEFAULT_MONTH}
        label="End At"
        withTime={withTime}
      />
    </>
  );
}
