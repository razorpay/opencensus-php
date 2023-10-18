import React, { useEffect, useState } from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';

import Amount from 'common/ui/Amount';
import Spinner from 'common/ui/Spinner';
import DataTable from 'common/ui/Table/DataTable';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import {
  fetchFunctionalWithdrawalConfigByMerchantID,
  fetchWithdrawalDetails,
} from 'merchant/reducers/capital/withdrawals';
import WithdrawalStatus from 'merchant/views/Capital/CashAdvance/WithdrawalStatus';
import {
  STATUSES,
  STATUS_DESCRIPTIONS,
  STATUS_LABELS,
  StatusPillClasses,
} from 'merchant/views/Capital/CashAdvance/constants';
import { isLenderLiquiloans } from 'merchant/views/Capital/CashAdvance/utils';
import { closeModal, openModal } from 'merchant_common/reducers/modals';

import RepayNow from './RepayNow';
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
  const [withdrawalId, setWithdrawalId] = useState(id);
  const [planIdPresent, setPlanIdPresent] = useState(false);

  useEffect(() => {
    if (id !== withdrawalId) {
      setWithdrawalId(id);

      fetchWithdrawalDetails({
        reference_type: 'ID',
        reference_id: id,
      }).then((response) => {
        const plan_id = response?.data?.withdrawal?.plan_id;
        setPlanIdPresent(!!plan_id);
      });
    }
  }, [id]);

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

  const repaymentId = {
    title: 'Repayment ID',
    value: (repayment) => <span>{repayment.id}</span>,
  };
  const repaymentAmount = {
    title: 'Amount',
    value: (repayment) => <Amount value={Number(repayment.amount)} />,
  };
  const repaidAt = {
    title: 'Date',
    value: (repayment) => (
      <span>
        {repayment?.created_at ? moment(repayment?.created_at).format('DD MMM YYYY') : '-'}
      </span>
    ),
  };
  const currentStatus = {
    title: 'Status',
    value: ({ repayment_status }) => (
      <span class={`status-label label ${StatusPillClasses[repayment_status]}`}>
        {STATUS_LABELS[repayment_status]}
      </span>
    ),
  };
  const isLiquiloans = isLenderLiquiloans(withdrawalConfigurationDetails);

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
                <strong>{withdrawalId}</strong>
              </span>
            </div>
          </div>
          <div className="SliderPanel__Body">
            <div className="">
              <div className="list-group details-row-container">
                <WithdrawalStatus status={data.status} withdrawalDetails={data} />
                {isLiquiloans && (
                  <RepayNow withdrawalId={withdrawalId} withdrawalDetails={withdrawalDetails} />
                )}

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
                      id={withdrawalId}
                      withdrawalDetails={withdrawalDetails}
                      withdrawalConfigurationDetails={withdrawalConfigurationDetailsData}
                    />
                  )}

                  {isLiquiloans && (
                    <>
                      <div className="hr-line" />

                      <div className="block-note orange m-t">
                        <strong>Repayment Details</strong>
                      </div>
                      <div>
                        <DataTable
                          columns={[repaymentId, repaymentAmount, repaidAt, currentStatus]}
                          title="Repayments"
                          items={data?.repayments || []}
                          loading={false}
                          showHeaders={true}
                        />
                      </div>
                    </>
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
