import { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import { Btn, BtnGroup } from 'common/ui/BtnGroup/index';
import Graph from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/CostSaved/Graph';
import { fetchWidgetData } from 'merchant/reducers/magicCheckout/rtoAnalytics/actions';
import { costSavedFormatter } from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/CostSaved/utils';
import { BREAKDOWN_MAP, NO_GRAPH_DATA } from 'merchant/views/MagicCheckout/RTOAnalytics/constants';
import { onBreakdownChange } from 'merchant/views/MagicCheckout/RTOAnalytics/utils';

const CostSaved = ({ widgetData, startTime, endTime, fetch, fetchingTimedWidgetsData }) => {
  const [chartData, setChartData] = useState(null);
  const [breakdown, setBreakdown] = useState('weekly');

  const { data, loading, updatedAt } = widgetData;
  const widgetName = 'cost_saving';

  const onBtnChange = useCallback(
    (value) => {
      onBreakdownChange(value, breakdown, startTime, endTime, fetch, setBreakdown, widgetName);
    },
    [breakdown, startTime, endTime, fetch],
  );

  useEffect(() => {
    if (fetchingTimedWidgetsData) {
      setBreakdown('weekly');
      setChartData(null);

      return;
    }

    setChartData(costSavedFormatter(data, breakdown, startTime, endTime));
  }, [fetchingTimedWidgetsData, data, breakdown, startTime, endTime, widgetData]);

  return (
    <div className="costSaved-container col-md-9">
      <GenericPanel
        className="analytics-panel cost-saved"
        isLoading={loading}
        hasNoData={!data || data.length === 0}
      >
        <PanelTopbar>
          Cost saved due to COD Intelligence
          <div className="panel-actions pull-right">
            <BtnGroup
              className="panel-action-item time-breakdown"
              value={breakdown}
              onChange={onBtnChange}
            >
              {Object.keys(BREAKDOWN_MAP).map((breakdown) => (
                <Btn key={breakdown} value={breakdown} className="btn-default">
                  <span>{BREAKDOWN_MAP[breakdown].text}</span>
                </Btn>
              ))}
            </BtnGroup>
          </div>
        </PanelTopbar>
        <PanelBody
          id="cost-saved-body"
          customTitle={NO_GRAPH_DATA.customTitle}
          customSubtitle={NO_GRAPH_DATA.customSubtitle}
        >
          {data && data.length > 0 && !loading ? (
            <Graph key="cost-saving" breakdown={breakdown} data={chartData} />
          ) : null}
        </PanelBody>
        <PanelFooter id="cost-saved-footer">
          <LastUpdated at={updatedAt} customIcon="i-clock" />
          <div className="reimbursement-callout">
            <small>
              <i className="i i-info-circle" />
              <span>Assuming Rs. 75 as average reverse shipping cost per order</span>
            </small>
          </div>
        </PanelFooter>
      </GenericPanel>
    </div>
  );
};

const mapDispatchToProps = (dispatch) => bindActionCreators({ fetch: fetchWidgetData }, dispatch);

const mapStateToProps = (state) => ({
  widgetData: state.magicRTOAnalytics.cost_saving,
  startTime: state.magicRTOAnalytics.startTime,
  endTime: state.magicRTOAnalytics.endTime,
  fetchingTimedWidgetsData: state.magicRTOAnalytics.timedWidgetsFetching,
});

export default connect(mapStateToProps, mapDispatchToProps)(CostSaved);
