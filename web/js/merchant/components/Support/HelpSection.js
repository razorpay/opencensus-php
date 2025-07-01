import React, { Suspense, lazy, useEffect, useState } from 'react';
import errorService from '@razorpay/universe-cli/errorService';
import { connect } from 'react-redux';

import { Modal, ModalBody } from 'common/components/Modal';
import { withRouter } from 'common/deprecated/withRouter';
import ErrorBoundary, { Ranks, Teams, InlineFallbackComponent } from 'common/new-ui/ErrorBoundary';
import { useSplitzService } from 'common/splitz';
import { analyticsTrack } from 'common/utils/analytics';
import { removeCookie, getCookie } from 'common/utils/cookies';
import { TicketSystemEmitter } from 'merchant/care/init';
import { useFohTicket } from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/store';
import { fetchTicketsRaisedByAgents } from 'merchant/reducers/config';
import { checkEligibilityForFeeBasedGating } from 'merchant/utils/feeBasedGatingUtils';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';

import { fireCustomEvent, getPosActivationStatus } from './utils';
import {
  blockTicketCreationFoh,
  isRiskDisabled,
  isRiskFoh,
} from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/Settlement/utils';
import { isSettlementSOHBlockEnabled } from 'merchant/views/Settlements/components/utils';

const Support = lazy(() =>
  import(/* webpackChunkName: 'frontend-care' */ '@razorpay/frontend-care'),
);

const ErrorFallbackComponent = (props) => {
  const [isOpen, setIsOpen] = useState(true);
  return (
    <Modal
      isOpen={isOpen}
      onClose={() => {
        setIsOpen(false);
      }}
    >
      <ModalBody>
        <InlineFallbackComponent {...props} />
      </ModalBody>
    </Modal>
  );
};

const HelpSection = ({
  user,
  history,
  org,
  fetchTicketsRaisedByAgents: _fetchTickets,
  isHelpWidgetVisible,
}) => {
  const splitz = useSplitzService();
  const { fohTicketStatus } = useFohTicket();
  const hideTicketCreation =
    isSettlementSOHBlockEnabled(splitz) && blockTicketCreationFoh(user, fohTicketStatus);
  const isRiskFohMerchant = isRiskFoh() || isRiskDisabled();
  const handleError = ({ error = 'CARE ERROR', rank = Ranks.P2 } = {}) => {
    errorService.captureError(error, {
      tags: {
        team: Teams.CARE,
      },
      rank,
    });
  };

  const handleTicketCreated = (ev) => {
    TicketSystemEmitter.emit('ticket-created', ev?.details || {});
  };

  useEffect(() => {
    if (user.isMobileSignupCareActive) {
      const isFetchTicketsApiMigrationActive = user.isFetchTicketsApiMigration;
      _fetchTickets(isFetchTicketsApiMigrationActive);
    }

    CreateTicketEmitter.on('toggle-help-section', () => {
      fireCustomEvent({
        event: 'toggle-help-section',
      });
    });

    CreateTicketEmitter.on('create-ticket', (id, pcb, lcb) => {
      fireCustomEvent({
        event: 'create-ticket',
        data: {
          id,
          pcb,
          lcb,
        },
      });
    });

    TicketSystemEmitter.on('openModal', (module, initialData) => {
      fireCustomEvent({
        event: 'open-ticket-modal',
        data: {
          module,
          initialData,
        },
      });
    });
    TicketSystemEmitter.on('closeModal', () => {
      fireCustomEvent({
        event: 'close-ticket-modal',
      });
    });

    document.addEventListener('ticket-created', handleTicketCreated);

    return () => {
      document.removeEventListener('ticket-created', handleTicketCreated);
    };
  }, []);

  // Don't show support for non indian
  if (!user.isCountryIndia) {
    return null;
  }

  const isOnBoardingRevampScreen =
    (history.location.pathname.includes('onboarding') && !getCookie('ftuxSession')) ||
    history.location.pathname.includes('tncform');
  const DASHBOARD_HOST_REGEX = /(dashboard.*\.razorpay\.(com|in)|localhost)$/;

  // Don't show support for Axis org
  if (
    !user.isComdelApiEnabled &&
    (!DASHBOARD_HOST_REGEX.test(location.hostname) || org.custom_code === 'axis')
  ) {
    return null;
  }

  if (isOnBoardingRevampScreen) {
    return null;
  }

  const handleCompleteKYC = () => {
    history.push('/onboarding/steps');
  };

  const handleActivation = () => {
    history.push('activation');
  };

  const handleCloseWebView = () => {
    try {
      window.ReactNativeWebView.postMessage(JSON.stringify({ eventType: 'EXIT' }));
    } catch (error) {
      handleError({ error });
    }
  };

  const isPartnerDashboard = history.location.pathname.includes('/partners');
  const isPartnerSupport = getCookie('isPartnerSupport') && isPartnerDashboard;
  const shouldOpenRaiseAQueryOnMount =
    history?.location?.pathname?.includes('/app-support') || isPartnerSupport;

  const isDev = !['production', 'canary'].includes(process.env.PUBLIC_ENV);

  const splitzHost = isDev
    ? 'https://beta-api.stage.razorpay.in/v1'
    : 'https://api.razorpay.com/v1';

  const handleSupportClose = () => {
    removeCookie('isPartnerSupport');
  };

  return (
    <ErrorBoundary
      resetOnProps
      rank={Ranks.P0}
      team={Teams.CARE}
      FallbackComponent={ErrorFallbackComponent}
    >
      <Suspense fallback={null}>
        <Support
          user={{
            id: user.id,
            name: user.name,
            email: user.email,
            contact_mobile: user?.user?.contact_mobile,
            role: user?.role,
            tags: user?.tags,
            activation_status: user.activation_status,
            business_type: user?.business_type,
            business_website: user?.business_website,
            isTransacted: user?.isTransacted || false,
            features: user?.features || [],
            pos_activation_status: getPosActivationStatus(user, splitz),
            activationStatusChangeLogs: user.activationStatusChangeLogs,
            country_code: user?.merchant?.country_code,
          }}
          onError={handleError}
          track={analyticsTrack}
          shouldOpenRaiseAQueryOnMount={shouldOpenRaiseAQueryOnMount}
          handleCompleteKYC={handleCompleteKYC}
          handleActivation={handleActivation}
          handleCloseWebView={handleCloseWebView}
          host={location.origin}
          splitzHost={splitzHost}
          isDev={isDev}
          isPartnerDashboard={isPartnerDashboard}
          hideSupportIcon={!isHelpWidgetVisible}
          hideTicketCreationCTA={
            checkEligibilityForFeeBasedGating(user) || !user.activation_status || hideTicketCreation
          }
          handleClose={handleSupportClose}
          disableCallChatOption={isRiskFohMerchant}
        />
      </Suspense>
    </ErrorBoundary>
  );
};

export default withRouter(
  connect(
    (state) => {
      return {
        user: state.session.user,
        org: state.session.org,
        isHelpWidgetVisible: state.session.isHelpWidgetVisible,
      };
    },
    {
      fetchTicketsRaisedByAgents,
    },
  )(HelpSection),
);
