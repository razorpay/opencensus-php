import React, { useState, useEffect } from 'react';

// redux utils
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';

// components
import Button from 'common/new-ui/Button';
import { Alert, Button as BladeButton } from '@razorpay/blade/components';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import InternationalStatusLabel from 'merchant/components/InternationalStatusLabel';

// constants
import { EVENTS, NON_3DS_CARDS_ACTIVATION_STATUS } from 'merchant/reducers/non3dsCardsActivation';

// actions
import { showNotification } from 'merchant_common/reducers/notifications';
import * as non3dsCardActivationActions from 'merchant/reducers/non3dsCardsActivation/actions';

// relative components
import Non3dsStatusDescription from './Non3dsStatusDescription';
import Non3dsCardsLearnMoreModal from './Non3dsCardsLearnMore';
import EnableNon3dsStatusModal from './EnableNon3dsStatusModal';
import Non3dsStatusInfo from './Non3dsStatusInfo';

// analytics
import { logAnalytics } from './analyticsHelper';

// styles
import './styles.styl';

/**
 * Non3dsActivation is only renders when international cards are enabled.
 * This component also has access checks:
 *
 *  Owner: Can update/view Non-3DS cards transaction settings
 *
 * user.role
 *
 *  Others: Can view Non-3DS cards transaction settings
 * @returns {React.ReactNode} Return React element for rendering Non3dsActivation settings
 */
const Non3dsCardsActivation = ({
  states,
  enableNon3dsCards,
  disableNon3dsCards,
  removeErrorMessage,
  fetchNon3dsCardsStatus,
  showNotificationAction,
  isIERevamp = false,
}) => {
  const [learnMoreModalOpen, setLearnMoreModalOpen] = useState(false);
  const [enable3dsModalOpen, setEnable3dsModalOpen] = useState(false);

  const handleLearnMoreModalOpen = () => {
    setLearnMoreModalOpen(true);
    logAnalytics(EVENTS.LEARN_MORE_MODAL, { actionName: 'opened' });
  };

  const handleLearnMoreModalClose = () => {
    setLearnMoreModalOpen(false);
  };

  const handleEnable3dsModalOpen = () => {
    setEnable3dsModalOpen(true);
  };

  const handleEnable3dsModalClose = () => {
    setEnable3dsModalOpen(false);
  };

  const handleLearnMoreOnEnable = () => {
    handleLearnMoreModalClose();
    handleEnable3dsModalOpen();
    logAnalytics(EVENTS.ENABLE_NON_3DS_MODAL, { actionName: 'opened' });
  };

  const handleOnEnable = (consent) => {
    if (states.isUserRoleOwner) {
      enableNon3dsCards();
      handleEnable3dsModalClose();
      logAnalytics(EVENTS.NON_3DS_ENABLE, { actionName: 'requested' });
      logAnalytics(EVENTS.ENABLE_NON_3DS_CONSENT, {
        actionName: 'clicked',
        result: consent,
      });
    }
  };

  const handleOnDisable = () => {
    if (states.isUserRoleOwner) {
      disableNon3dsCards();
      handleLearnMoreModalClose();
      logAnalytics(EVENTS.NON_3DS_DISABLE, { actionName: 'requested' });
    }
  };

  useEffect(() => {
    if (!states.isEnabling) {
      fetchNon3dsCardsStatus();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [states.isEnabling]);

  useEffect(() => {
    if (states.error && typeof states.error === 'string') {
      showNotificationAction({
        type: 'error',
        message: states.error,
      });
      removeErrorMessage();
      logAnalytics(EVENTS.NON_3DS_ENABLE, { actionName: 'error', result: states.error });
    }
  }, [states.error, showNotificationAction, removeErrorMessage]);

  useEffect(() => {
    if (states.success) {
      showNotificationAction({
        type: 'success',
        message: states.success,
      });
      removeErrorMessage();
    }
  }, [states.success, showNotificationAction, removeErrorMessage]);

  useEffect(() => {
    if (states.activationStatus) {
      logAnalytics(EVENTS.NON_3DS, {
        actionName: `status ${states.activationStatus}`,
        result: states.workflowStatus,
      });
    }
  }, [states.activationStatus, states.workflowStatus]);

  useEffect(() => {
    logAnalytics(EVENTS.NON_3DS, { actionName: 'loaded' });
  }, []);

  if (states.isLoading) {
    return null;
  }

  return (
    <ErrorBoundary resetOnProps rank={Ranks.P1} team={Teams.CROSS_BORDER}>
      <div class="non-3ds-card-activation">
        <div className="product-info">
          <div className="product-title header-title">
            <strong>Support for Non 3D Secure transactions</strong>
            {isIERevamp ? (
              <span>
                {(states.canRequestForEnable && states.isUserRoleOwner) || states.isEnabling ? (
                  <BladeButton
                    variant="secondary"
                    size="small"
                    onClick={handleEnable3dsModalOpen}
                    isLoading={states.isEnabling}
                  >
                    Request to activate
                  </BladeButton>
                ) : (
                  <InternationalStatusLabel status={states.activationStatus} isIERevamp />
                )}
              </span>
            ) : states.isEnabling ? (
              <Button.Primary disabled>Loading...</Button.Primary>
            ) : (
              <span>
                {states.canRequestForEnable && states.isUserRoleOwner ? (
                  <Button.Primary onClick={handleEnable3dsModalOpen}>Request</Button.Primary>
                ) : (
                  <InternationalStatusLabel status={states.activationStatus} />
                )}
              </span>
            )}
          </div>
          <div className="spacer-10" />
          <Non3dsStatusDescription
            status={states.workflowStatus}
            onLearnMore={handleLearnMoreModalOpen}
            showLearnMoreLink={states.isUserRoleOwner}
          />
          {states.activationStatus == NON_3DS_CARDS_ACTIVATION_STATUS.REQUESTED &&
            (isIERevamp ? (
              <div className="mt20">
                <Alert
                  description={`You have requested for non 3D Secure card support on ${states.updatedAt}. This can take
              upto 5-7 business days to get processed by our fraud protection team.`}
                  intent="information"
                  isFullWidth
                  isDismissible={false}
                />
              </div>
            ) : (
              <Non3dsStatusInfo>
                You have requested for non 3D Secure card support on {states.updatedAt}. This can
                take upto 5-7 business days to get processed by our fraud protection team.
                {states.rejectionReason && <p>{states.rejectionReason}</p>}
              </Non3dsStatusInfo>
            ))}
          {states.rejectionReason &&
            (isIERevamp ? (
              <div className="mt20">
                <Alert
                  description={states.rejectionReason}
                  intent="negative"
                  isDismissible={false}
                  isFullWidth
                />
              </div>
            ) : (
              <Non3dsStatusInfo>{states.rejectionReason}</Non3dsStatusInfo>
            ))}
        </div>
        <Non3dsCardsLearnMoreModal
          open={learnMoreModalOpen}
          status={states.activationStatus}
          onDisable={handleOnDisable}
          onEnable={handleLearnMoreOnEnable}
          onClose={handleLearnMoreModalClose}
        />
        <EnableNon3dsStatusModal
          open={enable3dsModalOpen}
          isLoading={states.isEnabling}
          onEnable={handleOnEnable}
          onClose={handleEnable3dsModalClose}
        />
      </div>
    </ErrorBoundary>
  );
};

/**
 *
 * @param {*} state: accepts redux state
 * @returns {{ states: * }} Return redux state
 */
const mapStatesToProps = (state) => ({
  states: {
    isUserRoleOwner: state.session?.user.role === 'owner',
    isLoading: state.non3dsCardsActivation.isLoading,
    isEnabling: state.non3dsCardsActivation.isEnabling,
    success: state.non3dsCardsActivation.success,
    error: state.non3dsCardsActivation.error,
    activationStatus: state.non3dsCardsActivation.activationStatus,
    workflowStatus: state.non3dsCardsActivation.workflowStatus,
    rejectionReason: state.non3dsCardsActivation.rejectionReason,
    updatedAt: state.non3dsCardsActivation.updatedAt,
    canRequestForEnable: state.non3dsCardsActivation.canRequestForEnable,
  },
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...non3dsCardActivationActions,
      showNotificationAction: showNotification,
    },
    dispatch,
  );
};

export default connect(mapStatesToProps, mapDispatchToProps)(Non3dsCardsActivation);
