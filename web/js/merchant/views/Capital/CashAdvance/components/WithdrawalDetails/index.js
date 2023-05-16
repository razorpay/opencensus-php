import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import Amount from 'common/ui/Amount';
import Spinner from 'common/ui/Spinner';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import {
  fetchWithdrawalDetails,
  fetchFunctionalWithdrawalConfigByMerchantID,
} from 'merchant/reducers/capital/withdrawals';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import WithdrawalStatus from 'merchant/views/Capital/CashAdvance/WithdrawalStatus';
import { STATUSES, STATUS_DESCRIPTIONS } from 'merchant/views/Capital/CashAdvance/constants';
import { RepaymentDetails } from './RepaymentDetails';
import { gaEventDispatcher } from './utils';

const WithdrawalDetails = ({
  id,
  user,
  fetchWithdrawalDetails,
  withdrawalConfigurationDetails,
  fetchFunctionalWithdrawalConfigByMerchantID,
  withdrawalDetails,
}) => {
  const [planIdPresent, setPlanIdPresent] = useState(false);

  useEffect(() => {
    fetchFunctionalWithdrawalConfigByMerchantID({
      owner_id: user.current,
      owner_type: 'RZP_MERCHANT',
    });

    fetchWithdrawalDetails({
      reference_type: 'ID',
      reference_id: id,
    }).then((response) => {
      const plan_id = response?.data?.withdrawal?.plan_id;
      setPlanIdPresent(!!plan_id);
    });

    return () => {
      gaEventDispatcher({
        eventAction: 'Details | Close',
      });
    };
  }, []);

  const { data, loading: withdrawalDetailsLoading } = withdrawalDetails;

  const withdrawalConfigurationDetailsData = withdrawalConfigurationDetails.data;
  const loading =
    !withdrawalConfigurationDetailsData ||
    withdrawalDetailsLoading ||
    withdrawalConfigurationDetailsData.loading;
  const shouldShowRepaymentDetails =
    data.status !== STATUSES.FAILED && data.status !== STATUSES.REJECTED && planIdPresent;

  return (
    <div className="content-wrapper content-sm txn-details CA--entity-details">
      {loading ? (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div className="panel panel-default SliderPanel">
          <div className="panel-heading">
            <div className="settlement-actions-wrapper">
              <span className="full-width no-margin">
                <strong>{id}</strong>
              </span>
            </div>
          </div>
          <div className="SliderPanel__Body">
            <div className="">
              <div className="list-group details-row-container">
                <WithdrawalStatus status={data.status} withdrawalDetails={data} />
                <div className="m-all p-all">
                  <div className="block-note purple m-b">
                    <strong>Withdrawal Details</strong>
                  </div>
                  <EntityDetailRow label="Withdrawn Amount">
                    <Amount value={data.amount} />
                  </EntityDetailRow>
                  <EntityDetailRow label="Status Description">
                    <p className="no-margin text--secondary KeyboardShortcutRow_action">
                      {STATUS_DESCRIPTIONS[data.status]}
                    </p>
                  </EntityDetailRow>
                  {data.disbursal_utr && (
                    <EntityDetailRow label="Disbursal UTR">
                      <p className="text--secondary">{data.disbursal_utr}</p>
                    </EntityDetailRow>
                  )}
                  {shouldShowRepaymentDetails && (
                    <RepaymentDetails
                      id={id}
                      withdrawalDetails={withdrawalDetails}
                      withdrawalConfigurationDetails={withdrawalConfigurationDetailsData}
                    />
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  withdrawalConfigurationDetails: state.withdrawals.withdrawalConfiguration,
  withdrawalDetails: state.withdrawals.withdrawalDetails,
  seedData: state.withdrawals.seedData,
});

const mapDispatchToProps = {
  fetchWithdrawalDetails,
  fetchFunctionalWithdrawalConfigByMerchantID,
  closeModal,
  openModal,
};

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(WithdrawalDetails));
