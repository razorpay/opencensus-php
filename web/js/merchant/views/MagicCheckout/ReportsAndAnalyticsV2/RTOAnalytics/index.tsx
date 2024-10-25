import React, { useState, useEffect, useCallback } from 'react';
import { useLocation } from 'react-router-dom';

import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { fetchAggregators } from 'merchant/reducers/magicCheckout/shipping_services/actions';
import {
  fetchWidgetData,
  setTimeRange,
} from 'merchant/reducers/magicCheckout/rtoAnalytics/actions';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import NavContainer from 'merchant/views/MagicCheckout/common/components/NavContainer';
import moment from 'moment';
import Spinner from 'common/ui/Spinner';

import { getStartDateFromDiff } from 'common/utils/rzp-utils';
import { getWidgetData } from 'merchant/views/MagicCheckout/RTOAnalytics/utils';
import { analyticsTrack } from 'common/utils/analytics';

import {
  TABS,
  REQUEST_LIMIT,
  BREAKDOWN,
} from 'merchant/views/MagicCheckout/RTOAnalytics/constants';
import { RTO_ANALYTICS_MAP } from 'merchant/views/MagicCheckout/ReportsAndAnalyticsV2/constants';

import { RTO_ROUTES } from 'merchant/views/MagicCheckout/ReportsAndAnalyticsV2/RTOAnalytics/routes';

import { AppState } from 'merchant/views/MagicCheckout/ReportsAndAnalyticsV2/types';

const DEFAULT_DURATION = [-30, 'days'];

const path = '/magic/reports-analytics/rto/';

type DispatchProps = {
  setTimeRange: (startTime: number | null, endTime: number | null) => void;
  fetchWidgets: () => void;
  fetchProviders: () => void;
};

type StateProps = {
  user: AppState['session']['user'];
  isLoading: AppState['magicRTOAnalytics']['loading'];
  startTime: AppState['magicRTOAnalytics']['startTime'];
  endTime: AppState['magicRTOAnalytics']['endTime'];
  isManualReviewOpted: AppState['magicCheckout']['cod_order_control'];
};

type RTOAnalyticsProps = StateProps & DispatchProps;

const RTOAnalytics: React.FC<RTOAnalyticsProps> = ({
  isLoading,
  setTimeRange,
  fetchWidgets,
  startTime,
  endTime,
  fetchProviders,
  user,
  isManualReviewOpted,
}) => {
  const [activeTab, setActiveTab] = useState(TABS.OVERVIEW);
  const [requestCount, setRequestCount] = useState(0);

  const location = useLocation();
  const { label } = activeTab;

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
    const widgetName = isManualReviewOpted ? 'manual_risk_order_split' : 'order_split';
    getWidgetData(
      widgetName,
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
    if (requestCount > 0 && requestCount <= REQUEST_LIMIT) {
      fetchData();
    } else if (requestCount > REQUEST_LIMIT) {
      setRequestCount(0);
    }
  }, [requestCount]);

  const callTabSwitchAnalyticTrack = useCallback(
    (tab) => {
      analyticsTrack({
        objectName: `1ccMdClickedOn${tab.eventName}`,
        actionName: 'clicked',
        screen: `${tab.tabName} tab l1`,
        properties: {
          merchant_id: user?.merchant?.id,
        },
      });

      analyticsTrack({
        objectName: `1ccMdViewed${tab.eventName}`,
        actionName: 'render',
        screen: `${tab.tabName} tab l1`,
        properties: {
          merchant_id: user?.merchant?.id,
        },
      });
    },
    [user],
  );

  const onTabClick = (tab) => {
    if (label === tab.label) return;

    setActiveTab(tab);
    callTabSwitchAnalyticTrack(tab);
  };

  //Programatic Navigation for tab switch analytics
  useEffect(() => {
    const path = location?.pathname?.split('/');
    const tab = path[path?.length - 1]?.toUpperCase();
    if (Object.keys(RTO_ANALYTICS_MAP).includes(tab)) {
      onTabClick(TABS[RTO_ANALYTICS_MAP[tab]]);
    }
  }, [location]);

  useEffect(() => {
    analyticsTrack({
      objectName: '1ccMdClickedOnRtoAnalyticsTab',
      actionName: 'clicked',
      screen: 'RTO analytics tab l1',
      properties: {
        merchant_id: user?.merchant?.id,
      },
    });

    analyticsTrack({
      objectName: '1ccMdViewedRTOAnalytics',
      actionName: 'render',
      screen: 'RTO Insights tab l1',
      properties: {
        merchant_id: user?.merchant?.id,
      },
    });

    analyticsTrack({
      objectName: '1ccMdViewedOverview',
      actionName: 'render',
      screen: 'Overview tab l1',
      properties: {
        merchant_id: user?.merchant?.id,
      },
    });
  }, []);

  return (
    <div className="display-flex rto-magic-container">
      {isLoading ? (
        <Spinner center />
      ) : (
        <SuspenseWithLoader type="center">
          <NavContainer navItems={RTO_ROUTES} basePath={path} handleNavClick={onTabClick} />
        </SuspenseWithLoader>
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
  isManualReviewOpted: state.magicCheckout.cod_order_control,
});

export default connect(mapStateToProps, mapDispatchToProps)(RTOAnalytics);
