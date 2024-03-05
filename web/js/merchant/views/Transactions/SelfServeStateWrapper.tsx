import { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import {
  fetchSchedule,
  fetchHolidayList,
  fetchSettlementConfig,
} from 'merchant/reducers/settlements/details';

const SelfServeStateWrapper = ({
  children,
  fetchSettlementConfig,
  fetchSchedule,
  fetchHolidayList,
}) => {
  useEffect(() => {
    fetchSettlementConfig();
    fetchSchedule();
    fetchHolidayList();
  }, []);
  return children;
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchHolidayList,
      fetchSchedule,
      fetchSettlementConfig,
    },
    dispatch,
  );
};

export default connect(null, mapDispatchToProps)(SelfServeStateWrapper);
