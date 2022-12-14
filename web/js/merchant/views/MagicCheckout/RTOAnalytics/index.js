import { useState, useEffect, useCallback } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import moment from 'moment';
import Spinner from 'common/ui/Spinner';
import Header from 'merchant/views/MagicCheckout/RTOAnalytics/containers/Header';

import { fetchAggregators } from 'merchant/reducers/magicCheckout/shipping_services/actions';
import {
  fetchWidgetData,
  setTimeRange,
} from 'merchant/reducers/magicCheckout/rtoAnalytics/actions';

import { getStartDateFromDiff } from 'common/utils/rzp-utils';
import { getWidgetData } from 'merchant/views/MagicCheckout/RTOAnalytics/utils';

import {
  TABS,
  REQUEST_LIMIT,
  BREAKDOWN,
} from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

const DEFAULT_DURATION = [-30, 'days'];

const RTOAnalytics = ({
  isLoading,
  setTimeRange,
  fetchWidgets,
  startTime,
  endTime,
  fetchProviders,
  user,
}) => {
  const [activeTab, setActiveTab] = useState(TABS.OVERVIEW);
  const [requestCount, setRequestCount] = useState(0);

  const { label, Component } = activeTab;

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

    return () => setTimeRange(null, null);
  }, [setTimeRange]);

  useEffect(() => {
    if (fetchProviders) {
      fetchProviders();
    }
  }, [fetchProviders]);

  const fetchData = useCallback(() => {
    getWidgetData(
      'order_split',
      BREAKDOWN.cumulative,
      startTime,
      endTime,
      fetchWidgets,
      setRequestCount,
    );
  }, [endTime, startTime, fetchWidgets]);

  useEffect(() => {
    if (startTime && endTime) {
      fetchData();
    }
  }, [startTime, endTime]);

  useEffect(() => {
    if (
      user &&
      user.isMagicRTOAnalyticsV2Enabled &&
      requestCount > 0 &&
      requestCount <= REQUEST_LIMIT
    ) {
      fetchData();
    } else if (requestCount > REQUEST_LIMIT) {
      setRequestCount(0);
    }
  }, [requestCount]);

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
            {Object.keys(TABS).map((tabName) => {
              return TABS[tabName].condition && !TABS[tabName].condition(user) ? null : (
                <div
                  key={TABS[tabName].label}
                  className={`rto-nav${getTab(TABS[tabName])}`}
                  onClick={() => onTabClick(TABS[tabName])}
                >
                  {TABS[tabName].label}
                </div>
              );
            })}
          </div>
          <div className="tab-content">
            <Header />
            <div className="charts-data">{<Component user={user} />}</div>
          </div>
        </>
      )}
    </div>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      setTimeRange,
      fetchWidgets: fetchWidgetData,
      fetchProviders: fetchAggregators,
    },
    dispatch,
  );

const mapStateToProps = (state) => ({
  user: state.session.user,
  isLoading: state.magicRTOAnalytics.loading,
  startTime: state.magicRTOAnalytics.startTime,
  endTime: state.magicRTOAnalytics.endTime,
});

export default connect(mapStateToProps, mapDispatchToProps)(RTOAnalytics);
