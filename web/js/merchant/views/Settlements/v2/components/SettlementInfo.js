import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';

import { useSplitzService } from 'common/splitz';
import Amount from 'common/ui/Amount';
import LoaderDots from 'common/ui/LoaderDots';
import Spinner from 'common/ui/Spinner';
import Time from 'common/ui/Time';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import ShowWhen from 'merchant/components/ShowWhen';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import { fetchIsAdminAsMerchant } from 'merchant/reducers/profile';
import * as SettlementActions from 'merchant/reducers/settlements/details';
import {
  handleAnalytics,
  propertiesPayload,
} from 'merchant/views/Settlements/Settlements/analytics';
import { fetchBankSettleStatus, customSettlementEnabled } from 'merchant/views/Settlements/v2/util';
import PaymentOptimizerProvider from 'merchant/views/Transactions/v1/Payments/components/PaymentOptimizerProvider';
import { getSelfServeSuccessData } from 'merchant/views/Transactions/v1/utils';
import { showNotification } from 'merchant_common/reducers/notifications';

const ORG_BANK_LABEL_NAME = {
  rzp: 'UTR',
  curlec: 'Reference Number',
};

const SettlementInfo = (props) => {
  const {
    error,
    loading,
    settlement,
    user,
    terminalProviders,
    showNotification,
    fetchIsAdminAsMerchant,
    org,
    currency,
  } = props;
  const splitz = useSplitzService();
  const [state, setState] = useState({
    customSettlementLoading: false,
    adminAsMerchant: false,
    bankSettleStatus: '',
    showCustomSettlDetails: false,
  });
  const orgCode = org?.custom_code || 'rzp';

  const getCustomSettleDetails = () => {
    const { settlementId } = props;
    const setlID = settlementId?.replace('setl_', '');
    const promiseList = [];
    setState({
      customSettlementLoading: true,
    });
    promiseList.push(fetchIsAdminAsMerchant());
    promiseList.push(fetchBankSettleStatus(setlID));
    return Promise.all(promiseList)
      .then((response) => {
        const [adminAsMerchantResp, bankSettleStatusResp] = response;
        const adminAsMerchant = adminAsMerchantResp?.data?.is_admin_as_merchant ?? false;
        const bankSettleStatus = bankSettleStatusResp?.data?.org_settlement?.status ?? '';
        setState({
          customSettlementLoading: false,
          adminAsMerchant,
          bankSettleStatus,
          showCustomSettlDetails: true,
        });
      })
      .catch(() => {
        setState({
          customSettlementLoading: false,
        });
        showNotification({
          type: 'error',
          message: 'Something went wrong, please try again later',
        });
      });
  };

  const checkFeatureFlags = () => {
    const isCustomSettlement = customSettlementEnabled(user);
    if (isCustomSettlement) {
      getCustomSettleDetails();
    }
  };

  useEffect(() => {
    settlementInfo();
    checkFeatureFlags();
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

      const selfServeSuccessData = getSelfServeSuccessData(
        'Settlement Details Fetched',
        'Settlement Details',
        splitz,
      );
      selfServeTrackSuccess(selfServeSuccessData);
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

  // prettier-ignore
  const {
    customSettlementLoading,
    adminAsMerchant,
    bankSettleStatus,
    showCustomSettlDetails,
  } = state;

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
        value={() => <Amount value={settlement?.fees} currency={currency} />}
      />

      <EntityDetailRow
        label="Tax"
        value={() => <Amount value={settlement?.tax} currency={currency} />}
      />
      <ShowWhen additionalCondition={() => !showCustomSettlDetails || adminAsMerchant}>
        <EntityDetailRow label={ORG_BANK_LABEL_NAME[orgCode]} value={settlement?.utr} />
      </ShowWhen>
      <ShowWhen additionalCondition={() => customSettlementLoading}>
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
    org: session.org,
    terminalProviders: navigator.terminalProviders,
  };
};

export default connect(mapStateToProps, {
  ...SettlementActions,
  showNotification,
  fetchIsAdminAsMerchant,
})(SettlementInfo);
