import React from 'react';
import PropTypes from 'prop-types';
import { lightTheme } from '@razorpay/blade-old/src/tokens/theme';
import Wrapper from '../Wrapper';
import InitWrapper from '../Wrapper/InitWrapper';
import { SIGNUP } from '../../screens/screenHelpers';
import SignUp from './SignUp';

const SignUpWrapper = (props) => {
  return (
    <Wrapper theme={lightTheme}>
      <InitWrapper
        lumberjackAppName="dashboard-signup"
        route={SIGNUP}
        showMobileSignup={props.showMobileSignup}
        defaultCoupon={props.defaultCoupon}
        initializeOrg={props.initializeOrg}
        orgName={props.orgName}
      >
        <SignUp {...props} />
      </InitWrapper>
    </Wrapper>
  );
};

SignUpWrapper.propTypes = {
  header: PropTypes.node,
  onRouteChange: PropTypes.func,
  authClientId: PropTypes.string,
  oneTapInfo: PropTypes.object,
  showPasswordRules: PropTypes.bool,
  showMobileSignup: PropTypes.bool,
  defaultCoupon: PropTypes.string,
  initializeOrg: PropTypes.bool,
  autoReadOtpSignup: PropTypes.bool,
  orgName: PropTypes.string,
};

SignUpWrapper.defaultProps = {
  showMobileSignup: false,
  autoReadOtpSignup: false,
};

export default SignUpWrapper;
