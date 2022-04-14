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
import PaymentOptimizerProvider from 'merchant/views/Transactions/Payments/components/PaymentOptimizerProvider';

const SettlementInfo = (props) => {
  const { error, loading, settlement, user, terminalProviders } = props;
  useEffect(() => {
    settlementInfo();
  }, []);

  useEffect(() => {
    if (error)
      props.showNotification({
        type: 'error',
        message: error,
      });
  }, [error]);

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
  if (loading) {
    return (
      <div class="div--loading">
        <Spinner />
      </div>
    );
  }

  if (error) return null;

  return (
    <React.Fragment>
      <EntityDetailRow
        label="Status"
        value={() => <SettlementStatusLabel status={settlement?.status} />}
      />

      <EntityDetailRow
        label="Created At"
        value={() => <Time value={settlement?.created_at} format="DD MMM YYYY, hh:mm:ss a" />}
      />

      {user?.isSingleReconEnabled && user?.isOptimizerEnabled && settlement?.optimizer_provider && (
        <EntityDetailRow
          label="Payment Provider"
          value={() => (
            <PaymentOptimizerProvider
              terminal_id={settlement?.optimizer_provider}
              settled_by={settlement?.settled_by}
              terminalProviders={terminalProviders}
              hideExternalLink={true}
            />
          )}
        />
      )}

      <EntityDetailRow
        label="Fees"
        value={() => <Amount value={settlement?.fees} currency="INR" />}
      />

      <EntityDetailRow
        label="Tax"
        value={() => <Amount value={settlement?.tax} currency="INR" />}
      />

      <EntityDetailRow label="UTR" value={settlement?.utr} />
    </React.Fragment>
  );
};

const mapStateToProps = (state) => {
  const { settlement, session, navigator } = state;
  return {
    settlement: settlement.settlement,
    loading: settlement.loading,
    error: settlement.error,
    user: session.user,
    terminalProviders: navigator.terminalProviders,
  };
};

export default connect(mapStateToProps, { ...SettlementActions, showNotification })(SettlementInfo);
