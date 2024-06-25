import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { useSplitzService } from 'common/splitz';
import { fetchModalConfigDetails } from 'merchant/reducers/ModalConfigApi';
import { fetchIsAdminAsMerchant } from 'merchant/reducers/profile';

import { isEligibleForFtux } from '../Activation/ActivationUtils';

const EasyOnboardingWrapper = (props) => {
  const { user, children, fetchIsAdminAsMerchant, isAdminAsMerchant } = props;
  const [isRoutingToEasy, setIsRoutingToEasy] = useState(true);

  const isEasyMerchant =
    user?.experiments?.easy_onboarding?.result === 'on' &&
    user?.user?.signup_campaign === 'easy_onboarding';
  const isEasyL2InComplete =
    isEasyMerchant &&
    (user?.activation_form_milestone === 'L1' || !user?.activation_form_milestone);

  const SOURCE_RAZORPAY_X = 'x';
  const urlSearchParams = new URLSearchParams(window.location.search);
  const queryParams = Object.fromEntries(urlSearchParams.entries());
  const isSourceRX = !!(queryParams?.merchant === SOURCE_RAZORPAY_X);

  const { abExperiments } = useSplitzService();

  const { loading, data: isAdmin } = isAdminAsMerchant;

  const isFtuxEnabled = isEligibleForFtux({ user, abExperiments, isAdmin });

  const getShouldRouteToEasy = async () => {
    if (isSourceRX || isAdmin) {
      return false;
    }

    if (isFtuxEnabled && isEasyMerchant) {
      const { isTransacted, activation_status } = user;
      if (isEasyL2InComplete) {
        return true;
      }
      if (activation_status === 'needs_clarification' && !props.isNcEligibile) {
        return false;
      }
      if (activation_status !== 'activated' && activation_status !== 'activated_mcc_pending') {
        return true;
      }
      if (isTransacted === false) {
        return true;
      }
      const res = await fetchModalConfigDetails('onboarding');
      const showFtuxFinalScreen = res?.data?.show_ftux_final_screen;
      return !!showFtuxFinalScreen;
    }

    return isEasyL2InComplete;
  };

  const routeToEasyOnboarding = async () => {
    const shouldRouteToEasy = await getShouldRouteToEasy();
    if (shouldRouteToEasy) {
      if (isFtuxEnabled) {
        window.open(`${window.EASY_ONBOARDING_URL}/onboarding/overview`, '_self', 'noopener');
      } else {
        window.open(`${window.EASY_ONBOARDING_URL}/onboarding`, '_self', 'noopener');
      }
    } else {
      setIsRoutingToEasy(false);
    }
  };

  useEffect(() => {
    if (loading) {
      fetchIsAdminAsMerchant();
    } else {
      routeToEasyOnboarding();
    }
  }, [loading]);

  if (isRoutingToEasy && !isSourceRX) {
    return null;
  }

  return <>{children}</>;
};

const mapDispatchToProps = (dispatch) => bindActionCreators({ fetchIsAdminAsMerchant }, dispatch);

export default connect(
  (state) => ({
    user: state.session.user,
    isNcEligibile: state.home.isNcEligibile,
    isAdminAsMerchant: state.profile.isAdminAsMerchant,
  }),
  mapDispatchToProps,
)(EasyOnboardingWrapper);
