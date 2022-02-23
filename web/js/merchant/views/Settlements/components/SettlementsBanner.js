import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { Link } from 'react-router-dom';
import {
  openModal as openModalFn,
  closeModal as closeModalFn,
} from 'merchant_common/reducers/modals';
import { CreateTicketEmitter } from '../../TicketSupport/utils';
import SettlementMessage from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/SettlementMessage';

const SettlementsBanner = (props) => {
  const {
    mode,
    user,
    settlement_amount,
    current_balance,
    holidayList,
    openModal,
    closeModal,
    settlementConfig,
    bankAccountChangeStatus,
  } = props;

  const { no_settlement } = settlement_amount.data;

  const isOnTemporaryHold = settlementConfig?.data?.config?.features?.hold?.status;

  const isOnHold = no_settlement?.on_hold;

  const balance = current_balance.data.balance || 0;

  const activationFormUrl = user.isActivationFormFullView ? '/kyc' : '/activation';

  const handleContactSupport = () => {
    closeModal();

    window.rzpAnalytics?.({
      eventCategory: 'Settlement Revamp',
      eventAction: 'Contact Support',
      eventLabel: `Settlements`,
    });

    if (window.rzpTicketSystem) {
      CreateTicketEmitter.emit('create-ticket', 'tickets');
    }
  };

  let icon, title, subTitle, actions, className;
  if (user.activation_status === 'under_review' || (user.isActivated && !user.isSubmitted)) {
    // We are showing this banner in live mode if user's KYC has not been submitted or user's KYC is under review
    if (mode === 'live') {
      icon = <i className="i i-info-outline alert-yellow" />;
      title = 'Your settlements are currently not being processed';
      // Different communication for KYC not submitted and KYC under review
      subTitle =
        user.activation_status === 'under_review' ? (
          <>
            Settlements will be enabled once your KYC has been reviewed successfully. It generally
            takes 1-2 working days <strong>from the first transaction</strong> for the review
            process to be complete.
          </>
        ) : (
          <> Settlements will be processed. Once your KYC is submitted and approved. </>
        );
      actions = !user.isSubmitted && (
        <Link to={activationFormUrl} className="action text-primary">
          Complete KYC
        </Link>
      );
      className = 'highlight-warning';
    }
  } else if (isOnHold) {
    // We are showing this banner in if the user is put on Funds on hold
    icon = <i className="i i-triangle-alert alert-red" />;
    title = 'Your settlements are under review';
    subTitle =
      'Your settlements are currently not being processed due to some risk issues with your payments or with your razorpay account.';
    actions = (
      <span className="action text-primary" onClick={handleContactSupport}>
        Contact support
      </span>
    );
    className = 'highlight-error';
  } else if (isOnTemporaryHold) {
    // We are showing this banner in if the user is put on NSS hold funds
    icon = <i className="i i-triangle-alert alert-red" />;
    title = 'Your settlements have been put on temporary hold';
    // Different communication if user has already updated bank details
    subTitle = bankAccountChangeStatus ? (
      <>
        Your request to update <strong>bank account details is under review.</strong> Settlement
        will be retried after updation of bank account details.
      </>
    ) : (
      'Your settlements are currently not being processed due to some issues with your bank account. We will not be able to process further settlements until the bank account details are updated from your end.'
    );
    // Different communication if user has already updated bank details
    actions = bankAccountChangeStatus ? (
      <Link to="/profile/update_bank_account" className="action text-primary">
        View Bank Account Details
      </Link>
    ) : (
      <Link to="/profile/update_bank_account" className="action text-primary">
        Update Bank Account Details <i className="i i-external-link" />
      </Link>
    );
    className = 'highlight-error';
  }

  return title ? (
    <div className="banner-wrapper">
      <div className={`settlements-banner ${className}`}>
        <div className="title-section">
          {icon}
          <span className="title">{title}</span>
          {actions}
        </div>
        <div className="sub-title-section">
          <span>{subTitle}</span>
        </div>
      </div>
    </div>
  ) : (
    user.isOndemandSettlementEnabled && (
      <div className="banner-wrapper">
        <SettlementMessage
          user={user}
          holidayList={holidayList}
          openModal={openModal}
          balance={balance}
        />
      </div>
    )
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    mode: state.session.mode,
    settlement_amount: state.home.settlement_amount,
    holidayList: state.settlement.holidayList,
    settlementConfig: state.settlement.config,
    config: state.config.config,
    payments: state.payments,
    ...state.home,
    ...state.settlements,
    ...state.instantSettlements,
    bankAccountChangeStatus: state.profile.bankAccountChangeStatus,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ openModal: openModalFn, closeModal: closeModalFn }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(SettlementsBanner);
