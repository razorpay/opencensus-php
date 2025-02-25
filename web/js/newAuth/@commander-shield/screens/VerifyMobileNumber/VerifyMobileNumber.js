import React from 'react';
import * as yup from 'yup';
import PropTypes from 'prop-types';
import { Formik } from 'formik';
import ChevronLeft from '@razorpay/blade-old/src/icons/ChevronLeft';

import Button from '../../shared/Button';
import PasswordInput from '../../shared/PasswordInput';
import Separator from '../../shared/Separator';
import { PASSWORD_VALIDATION_ERROR_MESSAGE } from '../../utils/validationService';

import {
  ScreenForm,
  ScreenHeading,
  EmailNumberChangeView,
  SpaceView,
  CenteredView,
  ScreenInfoText,
} from '../../shared/ScreenViews';

import useUserContext from '../../user/useUserContext';
import useVerificationHelpers from './useVerificationHelpers';

const VerifyMobileNumberScreen = ({ isGoogleOauthEnabled }) => {
  const { state } = useUserContext();

  // prettier-ignore
  const {
    onVerificationSubmit,
    handleOnClickForgotPassword,
    handleOnClickChange,
    handleUseAnotherLogInOption
  } = useVerificationHelpers();

  const formikInitialValues = {
    contact: state.user.mobileNumber,
  };

  return (
    <Formik
      initialValues={formikInitialValues}
      onSubmit={onVerificationSubmit}
      validationSchema={yup.object().shape({
        password: yup.string().required(PASSWORD_VALIDATION_ERROR_MESSAGE),
      })}
      enableReinitialize
    >
      {(formikProps) => (
        <ScreenForm>
          <ScreenHeading>Login to Dashboard</ScreenHeading>
          <EmailNumberChangeView onChangeClick={handleOnClickChange}>
            {formikProps.values.contact}
          </EmailNumberChangeView>
          <ScreenInfoText>
            That mobile number is not verified for login. For verification please enter your account
            password so that we know it’s really you trying to sign in.
          </ScreenInfoText>
          <SpaceView padding={[2, 0, 0, 0]}>
            <PasswordInput handleForgotPassword={handleOnClickForgotPassword} />
          </SpaceView>
          <SpaceView padding={[3, 0, 2.5, 0]}>
            <Button
              type="submit"
              size="medium"
              variant="primary"
              block
              disabled={!formikProps.isValid || formikProps.isSubmitting}
            >
              Get OTP
            </Button>
          </SpaceView>
          {isGoogleOauthEnabled ? (
            <>
              <Separator />
              <CenteredView margin={[1.25, 0, 0, 0]} padding={[0, 0, 0, 0.5]}>
                <Button
                  variant="tertiary"
                  size="medium"
                  icon={ChevronLeft}
                  onClick={handleUseAnotherLogInOption}
                >
                  Use Another Login Option
                </Button>
              </CenteredView>
            </>
          ) : null}
        </ScreenForm>
      )}
    </Formik>
  );
};

VerifyMobileNumberScreen.propTypes = {
  isGoogleOauthEnabled: PropTypes.bool,
};

export default VerifyMobileNumberScreen;
