import { useState, useEffect, useCallback } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import ReasonTable from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FlaggedReasons/components/ReasonTable';
import DoughnutGroup from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FlaggedReasons/DoughnutGroup';

import { fetchWidgetData } from 'merchant/reducers/magicCheckout/rtoAnalytics/actions';
import {
  getWidgetData,
  onRequestCountChange,
} from 'merchant/views/MagicCheckout/RTOAnalytics/utils';

import { NO_GRAPH_DATA, BREAKDOWN } from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

const FlaggedReasons = ({ widgetData, isLoading, updatedAt, startTime, endTime, fetchWidgets }) => {
  const [expanded, setExpanded] = useState(false);
  const [requestCount, setRequestCount] = useState(0);
  const widgetName = 'flagged_reason';

  const onExpandToggle = () => {
    setExpanded((prev) => !prev);
  };

  const fetchData = useCallback(() => {
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
    onRequestCountChange(requestCount, fetchData, setRequestCount);
  }, [requestCount]);

  const tableColumns = ['Reaons', 'Percentage of Risky users'];
  return (
    <GenericPanel
      className="analytics-panel flagged-reasons"
      hasNoData={!widgetData || widgetData.length === 0}
      isLoading={isLoading}
    >
      <PanelTopbar>Breakdown of risky users</PanelTopbar>
      <PanelBody
        customTitle={NO_GRAPH_DATA.customTitle}
        customSubtitle={NO_GRAPH_DATA.customSubtitle}
      >
        {widgetData && widgetData.length > 0 && !isLoading ? (
          <DoughnutGroup reasonsList={widgetData} orderType="risky users" />
        ) : null}
        <div className="expand-toggle">
          <div className="clickable" onClick={onExpandToggle}>
            <span>{expanded ? 'Hide all reasons' : 'View all reasons'}</span>
            <i className={`i-arrow-${expanded ? 'up' : 'down'}`} />
          </div>
        </div>
        {expanded ? <ReasonTable data={widgetData} columns={tableColumns} /> : null}
      </PanelBody>
      <PanelFooter>
        <LastUpdated at={updatedAt} customIcon="i-clock" />
      </PanelFooter>
    </GenericPanel>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ fetchWidgets: fetchWidgetData }, dispatch);

const mapStateToProps = (state) => ({
  widgetData: state.magicRTOAnalytics.flagged_reason.data,
  isLoading: state.magicRTOAnalytics.flagged_reason.loading,
  startTime: state.magicRTOAnalytics.startTime,
  endTime: state.magicRTOAnalytics.endTime,
  updatedAt: state.magicRTOAnalytics.flagged_reason.updatedAt,
});

export default connect(mapStateToProps, mapDispatchToProps)(FlaggedReasons);
