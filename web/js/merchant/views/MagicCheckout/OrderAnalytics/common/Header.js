import moment from 'moment';
import DateRangePicker from 'common/ui/DateRangePicker';
import Popover, { PopoverBody } from 'common/ui/Popover';

const DATE_RANGE_PRESETS = [
  ['Past 24 hours', -24, 'hours'],
  ['Past 48 hours', -48, 'hours'],
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
];

const DEFAULT_PRESET = 1;

const Header = ({ setTimeRange, updated_at }) => {
  const onDatesChange = (start, end) => {
    setTimeRange({ start, end });
  };

  const isOutsideRange = (day) =>
    day.isAfter(moment()) ||
    day.isBefore(moment().subtract(91, 'days')) ||
    day.isBefore(moment('2023-03-01'));

  return (
    <div className="sticky-header dashboard-header">
      <div className="date-range-container">
        <DateRangePicker
          presets={DATE_RANGE_PRESETS}
          onDatesChange={onDatesChange}
          defaultPreset={DEFAULT_PRESET}
          hideCustomPreset
          isOutsideRange={isOutsideRange}
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
