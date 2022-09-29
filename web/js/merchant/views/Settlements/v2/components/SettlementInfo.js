import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import * as SettlementActions from 'merchant/reducers/settlements/details';
import Spinner from 'common/ui/Spinner';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  handleAnalytics,
  propertiesPayload,
} from 'merchant/views/Settlements/Settlements/analytics';
import PaymentOptimizerProvider from 'merchant/views/Transactions/Payments/components/PaymentOptimizerProvider';
import { fetchIsAdminAsMerchant } from 'merchant/reducers/profile';
import { fetchFeatureStatus } from 'merchant/reducers/config';
import { isOrgFeatureExist } from 'merchant/models/User';
import ShowWhen from 'merchant/components/ShowWhen';
import { fetchBankSettleStatus } from 'merchant/views/Settlements/v2/util';
import LoaderDots from 'common/ui/LoaderDots';

const SettlementInfo = (props) => {
  const {
    error,
    loading,
    settlement,
    user,
    terminalProviders,
    showNotification,
    fetchFeatureStatus,
    fetchIsAdminAsMerchant,
  } = props;

  const [state, setState] = useState({
    isCustomSettlLoading: false,
    adminAsMerchant: false,
    bankSettleStatus: '',
    showCustomSettlDetails: false,
  });

  const getCustomSettleDetails = () => {
    const { settlementId } = props;
    const isOrgSettleToBank = isOrgFeatureExist('org_settle_to_bank');
    const setlID = settlementId?.replace('setl_', '');
    const promiseList = [];
    setState((prevState) => ({
      ...prevState,
      isCustomSettlLoading: true,
    }));
    promiseList.push(fetchIsAdminAsMerchant());
    promiseList.push(fetchFeatureStatus(user?.id, 'cancel_settle_to_bank'));
    promiseList.push(fetchFeatureStatus(user?.id, 'old_custom_settl_flow'));
    promiseList.push(fetchBankSettleStatus(setlID));
    return Promise.all(promiseList)
      .then((response) => {
        const adminAsMerchant = response?.[0]?.data?.is_admin_as_merchant ?? false;
        const cancelSettleToBank = response?.[1]?.data?.status ?? false;
        const oldCustomSettleFlow = response?.[2]?.data?.status ?? false;
        const bankSettleStatus = response?.[3]?.data?.org_settlement?.status ?? '';
        const showCustomSettlDetails =
          !cancelSettleToBank && !oldCustomSettleFlow && isOrgSettleToBank;
        setState((prevState) => ({
          ...prevState,
          isCustomSettlLoading: false,
          adminAsMerchant,
          bankSettleStatus,
          showCustomSettlDetails,
        }));
      })
      .catch(() => {
        setState((prevState) => ({
          ...prevState,
          isCustomSettlLoading: false,
        }));
        showNotification({
          type: 'error',
          message: 'Something went wrong, please try again later',
        });
      });
  };

  useEffect(() => {
    settlementInfo();
    getCustomSettleDetails();
  }, []);

  useEffect(() => {
    if (error)
      showNotification({
        type: 'error',
        message: error,
      });
  }, [error]);

  async function settlementInfo() {
    const { fetchItem, settlementId } = props;
    const objectName = 'settlement details fetched';
    const actionName = 'status';
    const screen = 'settlement details';
    try {
      const data = await fetchItem(settlementId);
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
      <div className="div--loading">
        <Spinner />
      </div>
    );
  }

  if (error) return null;

  const { isCustomSettlLoading, adminAsMerchant, bankSettleStatus, showCustomSettlDetails } = state;

  return (
    <React.Fragment>
      <ShowWhen additionalCondition={() => !showCustomSettlDetails || adminAsMerchant}>
        <EntityDetailRow
          label="Status"
          value={() => <SettlementStatusLabel status={settlement?.status} />}
        />
      </ShowWhen>
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
      <ShowWhen additionalCondition={() => !showCustomSettlDetails || adminAsMerchant}>
        <EntityDetailRow label="UTR" value={settlement?.utr} />
      </ShowWhen>
      <ShowWhen additionalCondition={() => isCustomSettlLoading}>
        <LoaderDots />
      </ShowWhen>
      <ShowWhen additionalCondition={() => showCustomSettlDetails}>
        <EntityDetailRow label="Final Settlement Reference no." value={settlement?.id} />
        <EntityDetailRow
          label="Bank Settlement Status"
          value={() => <SettlementStatusLabel status={bankSettleStatus} />}
        />
      </ShowWhen>
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

export default connect(mapStateToProps, {
  ...SettlementActions,
  showNotification,
  fetchIsAdminAsMerchant,
  fetchFeatureStatus,
})(SettlementInfo);
