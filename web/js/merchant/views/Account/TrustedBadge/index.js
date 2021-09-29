import React from 'react';
import { bindActionCreators } from 'redux';
import PropTypes from 'prop-types';

import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import Spinner from 'common/ui/Spinner';
import { showNotification } from 'merchant_common/reducers/notifications';

import { pageData, STATUS } from 'merchant/views/Account/TrustedBadge/constants/data';
import RenderTrustedBadgePage from 'merchant/views/Account/TrustedBadge/components/RenderTrustedBadgePage';
import { updateRTBMerchantStatus } from 'merchant/reducers/trustedBadge';

const TrustedBadge = ({
  trustedBadge,
  tracking,
  updateRTBMerchantStatus: updateStatus,
  showNotification: triggerNotification,
}) => {
  const {
    loading,
    status,
    updatePending: updateInProgress,
    updateError,
    updateAction,
  } = trustedBadge;

  const trackEvent = React.useCallback(
    (...args) => {
      try {
        if (tracking && args.length) {
          const { badgeStatus = STATUS.NOT_ELIGIBLE_YES_WAITLISTED_DELISTED, original } =
            status || {};
          if (!original) {
            const badgeVersion = pageData[badgeStatus]?.version;
            const commonData = {
              pageVersion: badgeVersion,
              RTBStatus: badgeStatus,
              RTBEligible: original.status === 'eligible',
              RTBLiveMerchants: badgeVersion === 1,
              RTBOptedOut: badgeVersion === 2,
              RTbActivated: badgeVersion === 1,
              RTBDelisted: original.is_delisted_atleast_once === 1,
              RTBBlacklisted: original.status === 'blacklist',
              RTBWaitlisted: original.merchant_status === 'waitlist',
            };
            if (args.length === 1) {
              args[1] = {};
            }
            args[1] = { ...commonData, ...args[1] };
          }
          tracking.trackEvent(window.rzpQ && window.rzpQ.merchantActions().interaction(...args));
        }
      } catch (e) {
        // e
      }
    },
    [tracking, status],
  );

  React.useEffect(() => {
    if (updateError) {
      triggerNotification({
        type: 'error',
        message: 'Something went wrong',
        hidePrevious: true,
      });
    }
  }, [triggerNotification, updateError]);

  React.useEffect(() => {
    trackEvent('RTBProductDashboardPageVisited');
  }, [trackEvent]);

  React.useEffect(() => {
    // OPT OUT CTA rendered
    if (status.badgeStatus === STATUS.YES_ELIGIBLE_LIVE) {
      trackEvent('RTBOptOutOptionRendered');
    }
    // Activate Badge Button rendered
    if (status.badgeStatus === STATUS.YES_ELIGIBLE_OPTED_OUT) {
      trackEvent('RTBActivateNowRendered');
    }
  }, [status, trackEvent]);

  const getContent = React.useCallback(() => {
    const { badgeStatus = STATUS.NOT_ELIGIBLE_YES_WAITLISTED_DELISTED } = status || {};
    return (
      <RenderTrustedBadgePage
        updateStatus={updateStatus}
        trackEvent={trackEvent}
        status={badgeStatus}
        data={pageData}
        loading={updateInProgress}
        updateAction={updateAction}
      />
    );
  }, [status, trackEvent, updateStatus, updateInProgress, updateAction]);

  return (
    <div className="content-wrapper trusted-badge-container">
      {loading ? <Spinner /> : getContent()}
    </div>
  );
};

TrustedBadge.propTypes = {
  trustedBadge: PropTypes.shape({
    status: PropTypes.shape({
      status: PropTypes.string.isRequired,
      original: PropTypes.any,
    }),
    loading: PropTypes.bool,
    updatePending: PropTypes.bool,
    updateError: PropTypes.bool,
    updateAction: PropTypes.string,
  }),
  updateRTBMerchantStatus: PropTypes.func.isRequired,
  showNotification: PropTypes.func.isRequired,
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateRTBMerchantStatus,
      showNotification,
    },
    dispatch,
  );

export default connect((state) => {
  return {
    trustedBadge: state.trustedBadge,
  };
  // eslint-disable-next-line babel/new-cap
}, mapDispatchToProps)(RTracking(() => window.rzpQ.component('Trusted Badge'))(TrustedBadge));
