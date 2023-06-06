import { useMemo, useState } from 'react';
import moment from 'moment';
import DateRangePicker from 'common/ui/DateRangePicker';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { getPresetsValue } from 'merchant/views/MagicCheckout/CODOrdersTab/utils';

// all ranges offseted by 1 since end date for those preset is day before
const DATE_RANGE_PRESETS = [
  ['Today', 0, 'day'],
  ['Past 2 days', -1, 'days'],
  ['Past 7 days', -6, 'days'],
  ['Past 30 days', -29, 'days'],
  ['Past 90 days', -89, 'days'],
];

const DEFAULT_PRESET = 1;
const TODAY = moment().local();
const DAY_BEFORE = moment().local().subtract('1', 'day');

const Header = ({ setTimeRange, updated_at }) => {
  const [selectedPreset, setSelectedPreset] = useState(
    getPresetsValue([DATE_RANGE_PRESETS[DEFAULT_PRESET]])[0],
  );
  const onDatesChange = (start, end) => {
    setTimeRange({ start, end });
  };

  const isOutsideRange = (day) =>
    day.isAfter(TODAY) ||
    day.isBefore(moment().subtract(91, 'days')) ||
    day.isBefore(moment('2023-03-01'));

  const handlePresetChange = (preset) => {
    setSelectedPreset(preset);
  };

  const endDate = useMemo(() => {
    if (selectedPreset?.name === 'Today') {
      return TODAY;
    } else {
      return DAY_BEFORE;
    }
  }, [selectedPreset]);

  return (
    <div className="sticky-header dashboard-header">
      <div className="date-range-container">
        <DateRangePicker
          presets={DATE_RANGE_PRESETS}
          defaultPreset={DEFAULT_PRESET}
          onDatesChange={onDatesChange}
          selectedPresetFromParent={selectedPreset}
          onSelectPreset={handlePresetChange}
          endDate={endDate}
          isOutsideRange={isOutsideRange}
          hasCustomEndDate
          hideCustomPreset
          allowSingleDaySelect
        />
        <div className="last-updated">
          <small>
            <i className="i i-info-circle">
              <Popover align="bottom" theme="dark">
                <PopoverBody>
                  <div>Data available post March 1, 2023</div>
                </PopoverBody>
              </Popover>
            </i>
            &nbsp;
            <span>Data last updated {moment.unix(updated_at).fromNow()} </span> <br />
            <span>This data is only for razorpay magic processed orders</span>
          </small>
        </div>
      </div>
    </div>
  );
};

export default Header;
