import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import * as SettlementActions from 'merchant/reducers/settlements/details';
import Spinner from 'common/ui/Spinner';
import { showNotification } from 'merchant_common/reducers/notifications';
import { handleAnalytics, propertiesPayload } from '../../Settlements/analytics';

const SettlementInfo = (props) => {
  useEffect(() => {
    settlementInfo();
  }, []);

  useEffect(() => {
    if (props.error)
      props.showNotification({
        type: 'error',
        message: props.error,
      });
  }, [props.error]);

  async function settlementInfo() {
    const objectName = 'settlement details fetched';
    const actionName = 'status';
    const screen = 'settlement details';
    try {
      const data = await props.fetchItem(props.settlementId);
      const properties = { ...propertiesPayload('settlement', data), status: 'success' };
      handleAnalytics(objectName, actionName, properties, screen);
    } catch (e) {
      const properties = {
        status: 'failure',
        failureReason: e.errors?.[0],
      };
      handleAnalytics(objectName, actionName, properties, screen);
    }
  }

  // show spinner unless settlements data is available
  if (props.loading) {
    return (
      <div class="div--loading">
        <Spinner />
      </div>
    );
  }

  if (props.error) return null;

  return (
    <React.Fragment>
      <EntityDetailRow
        label="Status"
        value={() => <SettlementStatusLabel status={props.settlement.status} />}
      />

      <EntityDetailRow
        label="Created At"
        value={() => <Time value={props.settlement.created_at} format="DD MMM YYYY, hh:mm:ss a" />}
      />

      <EntityDetailRow
        label="Fees"
        value={() => <Amount value={props.settlement.fees} currency="INR" />}
      />

      <EntityDetailRow
        label="Tax"
        value={() => <Amount value={props.settlement.tax} currency="INR" />}
      />

      <EntityDetailRow label="UTR" value={props.settlement.utr} />
    </React.Fragment>
  );
};

const mapStateToProps = (state) => {
  return {
    settlement: state.settlement.settlement,
    loading: state.settlement.loading,
    error: state.settlement.error,
  };
};

export default connect(mapStateToProps, { ...SettlementActions, showNotification })(SettlementInfo);
