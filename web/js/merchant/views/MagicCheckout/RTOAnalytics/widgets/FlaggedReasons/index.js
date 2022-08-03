import { useState } from 'react';
import { connect } from 'react-redux';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import ReasonTable from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FlaggedReasons/components/ReasonTable';
import DoughnutGroup from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FlaggedReasons/DoughnutGroup';
import { NO_GRAPH_DATA } from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

const FlaggedReasons = ({ widgetData, isLoading, updatedAt }) => {
  const [expanded, setExpanded] = useState(false);

  const onExpandToggle = () => {
    setExpanded((prev) => !prev);
  };

  return (
    <GenericPanel
      className="analytics-panel flagged-reasons"
      hasNoData={!widgetData || widgetData.length === 0}
      isLoading={isLoading}
    >
      <PanelTopbar>Reasons for flagged orders</PanelTopbar>
      <PanelBody
        customTitle={NO_GRAPH_DATA.customTitle}
        customSubtitle={NO_GRAPH_DATA.customSubtitle}
      >
        {widgetData && widgetData.length > 0 && !isLoading ? (
          <DoughnutGroup reasonsList={widgetData} />
        ) : null}
        <div className="expand-toggle">
          <div className="clickable" onClick={onExpandToggle}>
            <span>{expanded ? 'Hide all reasons' : 'View all reasons'}</span>
            <i className={`i-arrow-${expanded ? 'up' : 'down'}`} />
          </div>
        </div>
        {expanded ? <ReasonTable data={widgetData} /> : null}
      </PanelBody>
      <PanelFooter>
        <LastUpdated at={updatedAt} customIcon="i-clock" />
      </PanelFooter>
    </GenericPanel>
  );
};

const mapStateToProps = (state) => ({
  widgetData: state.magicRTOAnalytics.flagged_reason.data,
  isLoading: state.magicRTOAnalytics.flagged_reason.loading,
  updatedAt: state.magicRTOAnalytics.flagged_reason.updatedAt,
});

export default connect(mapStateToProps, null)(FlaggedReasons);
