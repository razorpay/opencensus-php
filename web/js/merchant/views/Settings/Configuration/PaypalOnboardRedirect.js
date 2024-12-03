import { connect } from 'react-redux';
import { Routes, Route, Navigate } from 'react-router-dom';

import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';

const PaypalOnboardRedirect = ({ user }) => {
  const {
    abExperiments: { paypal_onboard_redirect },
  } = useSplitzService();

  const isPaypalOnboardRedirectEnabled =
    isExperimentEnabled(paypal_onboard_redirect) || user.isHidePayPalPopupEnabled;

  if (isPaypalOnboardRedirectEnabled) {
    (window.opener || window.parent)?.postMessage(
      'paypal_onboard_redirect',
      window.location.origin,
    );
  }

  return (
    <Routes>
      <Route path="*" element={<Navigate to="/config" replace />} />;
    </Routes>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, {
  PaypalOnboardRedirect,
})(PaypalOnboardRedirect);
