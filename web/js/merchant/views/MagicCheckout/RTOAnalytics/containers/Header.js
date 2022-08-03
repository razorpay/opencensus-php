import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import DateRangePicker from 'common/ui/DateRangePicker';
import CumulativeOrders from 'merchant/views/MagicCheckout/RTOAnalytics/common/CumulativeOrders';
import {
  setTimeRange,
  fetchTimedWidgetsData,
} from 'merchant/reducers/magicCheckout/rtoAnalytics/actions';

const DATE_RANGE_PRESETS = [
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
];

const defaultPreset = 1;

const Header = ({ setTimeRange, fetchTimedWidgetsData }) => {
  const onDatesChange = (from, to) => {
    setTimeRange(from, to);
    fetchTimedWidgetsData(from, to);
  };

  return (
    <div className="fixed-header">
      <CumulativeOrders />
      <div className="date-range-container">
        <DateRangePicker
          presets={DATE_RANGE_PRESETS}
          onDatesChange={onDatesChange}
          defaultPreset={defaultPreset}
          hideCustomPreset
        />
      </div>
    </div>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ setTimeRange, fetchTimedWidgetsData }, dispatch);

export default connect(null, mapDispatchToProps)(Header);
