import React, { useState } from 'react';
import { connect } from 'react-redux';

// UI
import MagicKonnectOnboarding from './Onboarding';
import 'merchant/views/MagicCheckout/css/magic_checkout.styl';
import { createPopup } from '@typeform/embed';

// helpers
import { useSplitzService } from 'common/splitz';
import { useStore } from '@federated/apps/shell/commonStore';

// api
import { getJwtTokenForMagicKonnect } from './api';

const MagicKonnect = ({ user, org }) => {
  const showNotification = useStore((state) => state.showNotification);
  const { abExperiments } = useSplitzService();
  const shouldShowMagicKonnectLoginCta =
    abExperiments?.magic_konnect_login_enabled?.variables?.result === 'on';
  const [isLoading, setIsLoading] = useState(false);

  // make api call and re-route after api call
  const onClickExistingUser = () => {
    setIsLoading(true);
    getJwtTokenForMagicKonnect()
      .then((res) => {
        document.cookie = `aisensySSOToken=${res.data.jwt}; path=/; domain=.razorpay.com`;
        window.open('https://www.app.konnect.razorpay.com/login', '_blank', 'noopener,noreferrer');
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: 'Unable to proceed with Konnect sign-in',
        });
      })
      .finally(() => {
        setIsLoading(false);
      });
  };

  // open form for new users on onclick
  const onClickNextCtaActionNewUser = () => {
    const formId = 'h4StEl1C';
    const newUserForm = createPopup(formId, {
      hideHeaders: true,
      hideFooter: true,
    });

    newUserForm.toggle();
  };

  // Ai-sensy existing user
  if (user && shouldShowMagicKonnectLoginCta) {
    return (
      <MagicKonnectOnboarding
        onClickNextCtaAction={onClickExistingUser}
        primaryCta="Login to Konnect"
        isLoading={isLoading}
        isExistingUser={true}
        businessName={org.businessName}
      />
    );
  }

  // Ai-sensy new user
  return (
    <MagicKonnectOnboarding
      onClickNextCtaAction={onClickNextCtaActionNewUser}
      isLoading={isLoading}
      isExistingUser={false}
      businessName={org.businessName}
    />
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  org: state.session.org,
});

export default connect(mapStateToProps, null)(MagicKonnect);
