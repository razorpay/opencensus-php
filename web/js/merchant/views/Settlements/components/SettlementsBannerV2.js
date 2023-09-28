import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { withRouter } from 'common/deprecated/withRouter';
import styled from 'styled-components';
import {
  openModal as openModalFn,
  closeModal as closeModalFn,
} from 'merchant_common/reducers/modals';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import SettlementMessage from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/SettlementMessage';
import { Alert } from '@razorpay/blade/components';
import { ALERT_INTENT, SETTLEMENT_RETRY_SLA_IN_HOURS, SETTLEMENT_STATUS } from './utils';
import moment from 'moment/moment';
import { getFormattedAmount } from 'common/utils/rzp-utils';
import { analyticsTrack, analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { EASY_ONBOARDING } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';
import { redirectToEasyAfter1sec } from 'merchant/components/Activation/ActivationUtils';

export const BannerWrapper = styled.div(
  ({ theme }) => `
    padding: ${theme.spacing[6]}px;
    padding-bottom: ${theme.spacing[1]}px;;
    overflow-x: auto;
`,
);

const SettlementsBannerV2 = ({
  mode,
  user,
  settlement_amount,
  current_balance,
  holidayList,
  openModal,
  closeModal,
  settlementConfig,
  bankAccountChangeStatus,
  history,
  settlementsList,
}) => {
  const isBannerLoading =
    settlement_amount?.loading ||
    settlementConfig?.loading ||
    current_balance?.loading ||
    holidayList?.loading ||
    settlementsList?.loading;

  const no_settlement = settlement_amount?.data?.no_settlement;

  const isOnTemporaryHold = settlementConfig?.data?.config?.features?.hold?.status;

  const isOnHold = no_settlement?.on_hold;

  const isBlocked = settlementConfig?.data?.config?.features?.block?.status;

  const balance = current_balance?.data?.balance || 0;

  const noExecutions = !settlement_amount?.data?.next_settlement_time;

  const activationFormUrl = user?.isActivationFormFullView ? '/kyc' : '/activation';

  const isSignupWithEasyOnboarding = user?.user?.signup_campaign === EASY_ONBOARDING;

  const nextSettlement = settlement_amount?.data?.settlement_amount;

  const previousSettlement = settlementsList?.items?.[0];

  const previousSettlementFailed =
    previousSettlement?.status?.toLowerCase() === SETTLEMENT_STATUS.FAILED;

  const retrySlaBreached =
    moment().diff(moment.unix(previousSettlement?.created_at), 'hours') >
    SETTLEMENT_RETRY_SLA_IN_HOURS;

  const handleContactSupport = (title) => {
    closeModal();

    window.rzpAnalytics?.({
      eventCategory: 'Settlement Revamp',
      eventAction: 'Contact Support',
      eventLabel: `Settlements`,
    });

    if (window.rzpTicketSystem) {
      CreateTicketEmitter.emit('create-ticket', 'tickets');
    }

    analyticsTrackWithUserInfo({
      objectName: 'Create Ticket',
      actionName: 'Clicked v2',
      screen: 'Settlements',
      properties: {
        page: 'Home Screen',
        settlements_experiment_name: user.isSettlementV3RevampEnabled ? 'v2' : 'v1',
        source_widget: 'Settlements main screen',
        title,
        sessionId: window?.session_id ? window.session_id : undefined,
        international_payments_enabled: user.international,
      },
    });
  };

  let title, subTitle, actions, intent;

  if (
    user?.activation_status === 'under_review' ||
    user?.activation_status === 'kyc_qualified_unactivated'
  ) {
    // We are showing this banner in live mode if user's KYC has not been submitted or user's KYC is under review
    title = 'Your KYC details are currently under review';
    subTitle =
      'We’ll verify your given KYC details in 3-4 working days, and reach out to you for any questions. Please note, you’ll be able to receive collected payments in your bank account after the KYC verification is complete';
    intent = ALERT_INTENT.NOTICE;
  } else if (user?.activation_status === 'needs_clarification') {
    // We are showing this banner in live mode if user's KYC is needs clarification
    title = 'We need a few more details to complete KYC verification';
    subTitle =
      'Follow the link to update the required details soon. We’ll verify your details in 3-4 working days, and reach out to you for any questions';
    actions = {
      primary: {
        text: 'Submit details now',
        onClick: () => {
          if (isSignupWithEasyOnboarding) {
            analyticsTrack({
              objectName: 'redirect to easy-dashboard CTA',
              actionName: 'Redirect',
              screen: 'settlements banner v2',
              properties: {
                'CTA Label': 'Submit details now',
              },
            });
            redirectToEasyAfter1sec();
          } else {
            history.push(activationFormUrl);
          }
        },
      },
    };
    intent = ALERT_INTENT.NOTICE;
  } else if (user?.isActivated && !user?.isSubmitted) {
    // We are showing this banner in live mode if user's KYC has not been submitted
    title = 'Complete your KYC to receive collected payments in your bank account';
    subTitle =
      'To remove the ₹15,000 payment limit and receive collected payments in your bank account, complete your KYC. We’ll verify your given KYC details in 3-4 working days after you submit.';
    actions = {
      primary: {
        text: 'Complete KYC',
        onClick: () => {
          analyticsTrackWithUserInfo({
            objectName: 'Complete KYC',
            actionName: 'Clicked',
            screen: 'Settlements',
            properties: {
              page: 'Home Screen',
              settlements_experiment_name: user.isSettlementV3RevampEnabled ? 'v2' : 'v1',
              source_widget: 'Settlements main screen',
              title,
              state: user.isTransacted ? 'Complete' : 'Empty',
              activation_status: user.activation_status,
              sessionId: window?.session_id ? window.session_id : undefined,
              isL2Completed: user.isActivated && true,
              international_payments_enabled: user.international,
            },
          });
          if (isSignupWithEasyOnboarding) {
            analyticsTrack({
              objectName: 'redirect to easy-dashboard CTA',
              actionName: 'Redirect',
              screen: 'settlements banner v2',
              properties: {
                'CTA Label': 'Complete KYC',
              },
            });
            redirectToEasyAfter1sec();
          } else {
            history.push(activationFormUrl);
          }
        },
      },
    };
    intent = ALERT_INTENT.NOTICE;
  } else if (isOnHold) {
    // We are showing this banner if the user is put on Funds on hold
    title = 'Contact support to resume settlements for your account';
    subTitle = 'Your settlements are on-hold as we’ve noticed unusual activity in your account';
    actions = {
      primary: {
        text: 'Contact support',
        onClick: () => {
          handleContactSupport(title);
        },
      },
    };
    intent = ALERT_INTENT.NEGATIVE;
  } else if (isOnTemporaryHold) {
    // We are showing this banner if the user is put on NSS hold funds
    // Different communication if user has already updated bank details
    title = bankAccountChangeStatus
      ? 'Your bank account update request is under review'
      : 'Update your bank account details to resume settlements';
    subTitle = bankAccountChangeStatus
      ? 'We’ll verify your details in some time and share an update. Please note, you will be able to receive collected payments in your bank account after the update is successful'
      : 'Your settlements are on-hold as we’ve encountered a few issues with your given bank account';
    actions = !bankAccountChangeStatus && {
      primary: {
        text: 'Update Bank Account Details',
        onClick: () => {
          user?.isAccountAndSettingsRevampEnabled
            ? history.push('/bank-accounts-settlements/bank-account-details')
            : history.push('/profile/update_bank_account');
        },
      },
    };
    intent = bankAccountChangeStatus ? ALERT_INTENT.NOTICE : ALERT_INTENT.NEGATIVE;
  } else if (isBlocked) {
    // We are showing this banner if the user is put on NSS block feature
    title = 'Contact support to resume settlements for your account';
    subTitle = 'Your settlements are on-hold as per your request';
    actions = {
      primary: {
        text: 'Contact support',
        onClick: () => {
          handleContactSupport(title);
        },
      },
    };
    intent = ALERT_INTENT.NEGATIVE;
  } else if (previousSettlementFailed) {
    // We are showing this banner if the user's previous settlement is in failed state
    title = retrySlaBreached
      ? `Contact support to receive failed settlement of ₹${getFormattedAmount(
          previousSettlement?.amount,
        )}`
      : 'Your failed settlement is being retried';
    subTitle = retrySlaBreached
      ? `Your previous settlement of ₹${getFormattedAmount(
          previousSettlement?.amount,
        )} could not be processed as we’ve encountered a few issues`
      : 'We’re retrying your failed settlement as we’ve encountered a few issues. We’ll share an update with you in some time';
    actions = retrySlaBreached && {
      primary: {
        text: 'Contact support',
        onClick: () => {
          handleContactSupport(title);
        },
      },
    };
    intent = retrySlaBreached ? ALERT_INTENT.NEGATIVE : ALERT_INTENT.NOTICE;
  } else if (noExecutions && balance > 100) {
    // We are showing this banner if the user has some balance but no executions
    title = 'Collect more payments to continue receiving settlements';
    subTitle =
      'Your settlements are not being processed as we’ve noticed lack of transactional activity';
    intent = ALERT_INTENT.NOTICE;
  } else if (balance < nextSettlement) {
    // We are showing this banner if the user's nextSettlement > current balance
    title = 'Upcoming settlement might get skipped';
    subTitle =
      'It might get skipped as your current balance going negative. Collect more payments to continue receiving settlements for your account';
    actions = {
      primary: {
        text: 'Contact support',
        onClick: () => {
          handleContactSupport(title);
        },
      },
    };
    intent = ALERT_INTENT.NOTICE;
  }

  return (
    !isBannerLoading &&
    (title && mode === 'live' ? (
      <BannerWrapper aria-label="settlement-banner">
        <Alert
          intent={intent}
          isDismissible={false}
          title={title}
          description={subTitle}
          isFullWidth={true}
          actions={actions}
        />
      </BannerWrapper>
    ) : user?.isOndemandSettlementEnabled ? (
      <BannerWrapper aria-label="settlement-banner">
        <div className="Announcement_Banner">
          <SettlementMessage
            user={user}
            holidayList={holidayList}
            openModal={openModal}
            balance={balance}
          />
        </div>
      </BannerWrapper>
    ) : null)
  );
};

const mapStateToProps = (state) => {
  return {
    user: state?.session?.user,
    mode: state?.session?.mode,
    settlement_amount: state?.home?.settlement_amount,
    holidayList: state?.settlement?.holidayList,
    settlementConfig: state?.settlement?.config,
    config: state?.config?.config,
    bankAccountChangeStatus: state?.profile?.bankAccountChangeStatus,
    current_balance: state?.home?.current_balance,
    settlementsList: state?.settlements,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ openModal: openModalFn, closeModal: closeModalFn }, dispatch);
};

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(SettlementsBannerV2));
