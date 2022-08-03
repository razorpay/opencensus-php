import { useState, useEffect, useCallback } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import moment from 'moment';
import Spinner from 'common/ui/Spinner';
import {
  fetchAllWidgetData,
  setTimeRange,
} from 'merchant/reducers/magicCheckout/rtoAnalytics/actions';
import { TABS } from 'merchant/views/MagicCheckout/RTOAnalytics/constants';
import Header from 'merchant/views/MagicCheckout/RTOAnalytics/containers/Header';
import { getStartDateFromDiff } from 'common/utils/rzp-utils';

const DEFAULT_DURATION = [-30, 'days'];

const RTOAnalytics = ({ fetchAll, isLoading, setTimeRange }) => {
  const [activeTab, setActiveTab] = useState(TABS.OVERVIEW);

  const { label, component } = activeTab;

  useEffect(() => {
    const endDate = moment();
    const defaultDiff =
      endDate.unix() -
      endDate
        .clone()
        .add(...DEFAULT_DURATION)
        .unix();
    const startDate = getStartDateFromDiff(defaultDiff, endDate);
    setTimeRange(startDate.toDate().getTime(), endDate.toDate().getTime());
  }, [setTimeRange]);

  useEffect(() => {
    if (!fetchAll) return;

    fetchAll();
  }, [fetchAll]);

  const onTabClick = (tab) => {
    if (label === tab.label) return;

    setActiveTab(tab);
  };

  const getTab = useCallback(
    (tabs) => {
      return label === tabs.label ? ' active' : '';
    },
    [label],
  );

  return (
    <div className="display-flex rto-magic-container">
      {isLoading ? (
        <Spinner />
      ) : (
        <>
          <div className="rto-magic-sidebar">
            {Object.keys(TABS).map((tabName) => (
              <div
                key={TABS[tabName].label}
                className={`rto-nav${getTab(TABS[tabName])}`}
                onClick={() => onTabClick(TABS[tabName])}
              >
                {TABS[tabName].label}
              </div>
            ))}
          </div>
          <div className="tab-content">
            <Header />
            <div className="charts-data">{component}</div>
          </div>
        </>
      )}
    </div>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ fetchAll: fetchAllWidgetData, setTimeRange, fetchAllWidgetData }, dispatch);

const mapStateToProps = (state) => ({
  isLoading: state.magicRTOAnalytics.loading,
  startTime: state.magicRTOAnalytics.startTime,
  endTime: state.magicRTOAnalytics.endTime,
});

export default connect(mapStateToProps, mapDispatchToProps)(RTOAnalytics);
