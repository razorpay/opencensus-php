import React, { useEffect } from 'react';
import PropTypes from 'prop-types';
import * as yup from 'yup';
import { Formik } from 'formik';
import ChevronLeft from '@razorpay/blade-old/src/icons/ChevronLeft';

import Button from '../../shared/Button';
import PasswordInput from '../../shared/PasswordInput';
import Separator from '../../shared/Separator';
import { PASSWORD_VALIDATION_ERROR_MESSAGE } from '../../utils/validationService';
import {
  ScreenForm,
  ScreenHeading,
  SpaceView,
  CenteredView,
  ScreenInfoText,
} from '../../shared/ScreenViews';
import TermsAndConditionModal from '../../shared/TermsAndConditionModal';
import useUserContext from '../../user/useUserContext';
import useTwoFAPasswordActions from './useTwoFAForm';
import twoFactorPasswordAuthEvents from './twoFactorAuthPasswordEvents';

const TwoFactorAuthPassword = ({ isGoogleOauthEnabled }) => {
  const { state } = useUserContext();
  // prettier-ignore
  const {
    on2FASubmit,
    moveToForgotPasswordScreen,
    goToLoginOptionsScreen,
    censoredEmail,
    handleTncModalClose,
    handleAcceptTnC,
  } = useTwoFAPasswordActions();

  useEffect(() => {
    twoFactorPasswordAuthEvents.trackPageLoad();
  }, []);

  return (
    <>
      <Formik
        initialValues={{
          password: '',
        }}
        onSubmit={on2FASubmit}
        validationSchema={yup.object().shape({
          password: yup.string().required(PASSWORD_VALIDATION_ERROR_MESSAGE),
        })}
        enableReinitialize
      >
        {(formikProps) => (
          <ScreenForm>
            <ScreenHeading>2 Step Verification</ScreenHeading>
            <ScreenInfoText margin={[0, 0, 0, 0]}>
              {censoredEmail
                ? `Please enter the password for your Email ID ${censoredEmail}`
                : 'Please enter your Razorpay account password'}
            </ScreenInfoText>
            <SpaceView padding={[2, 0, 0, 0]}>
              <PasswordInput handleForgotPassword={moveToForgotPasswordScreen} />
            </SpaceView>
            <SpaceView padding={[3, 0, 2.5, 0]}>
              <Button
                type="submit"
                size="medium"
                variant="primary"
                block
                disabled={!formikProps.isValid || formikProps.isSubmitting}
              >
                Log In
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
                    onClick={goToLoginOptionsScreen}
                  >
                    Use Another Login Option
                  </Button>
                </CenteredView>
              </>
            ) : null}
          </ScreenForm>
        )}
      </Formik>
      {state?.user?.show_tnc_popup ? (
        <TermsAndConditionModal closeModal={handleTncModalClose} handleAccept={handleAcceptTnC} />
      ) : null}
    </>
  );
};

TwoFactorAuthPassword.propTypes = {
  isGoogleOauthEnabled: PropTypes.bool,
};

export default TwoFactorAuthPassword;
