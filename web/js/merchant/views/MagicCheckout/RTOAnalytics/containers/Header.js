import { connect } from 'react-redux';
import moment from 'moment';
import { bindActionCreators } from 'redux';
import DateRangePicker from 'common/ui/DateRangePicker';
import CumulativeOrders from 'merchant/views/MagicCheckout/RTOAnalytics/common/CumulativeOrders';
import RiskLevelOrderSplitCumulative from 'merchant/views/MagicCheckout/RTOAnalytics/common/RiskLevelOrderSplitCumulative';
import { setTimeRange } from 'merchant/reducers/magicCheckout/rtoAnalytics/actions';

const DATE_RANGE_PRESETS = [
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
];

const defaultPreset = 1;

const Header = ({ setTimeRange, isManualReviewOpted }) => {
  const onDatesChange = (from, to) => {
    setTimeRange(from, to);
  };

  const isOutsideRange = (day) =>
    day.isAfter(moment()) || day.isBefore(moment().subtract(91, 'days'));

  return (
    <div className="fixed-header">
      {!isManualReviewOpted ? <CumulativeOrders /> : <RiskLevelOrderSplitCumulative />}
      <div className="date-range-container">
        <DateRangePicker
          presets={DATE_RANGE_PRESETS}
          onDatesChange={onDatesChange}
          defaultPreset={defaultPreset}
          hideCustomPreset
          isOutsideRange={isOutsideRange}
        />
      </div>
    </div>
  );
};

const mapDispatchToProps = (dispatch) => bindActionCreators({ setTimeRange }, dispatch);

export default connect(null, mapDispatchToProps)(Header);
