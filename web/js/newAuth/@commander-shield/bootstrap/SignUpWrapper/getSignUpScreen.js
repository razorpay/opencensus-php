import React from 'react';
import PropTypes from 'prop-types';
import { screenMap } from '../../screens/screenHelpers';
import BusinessType from '../../screens/BusinessType/BusinessType';
import MonthlyRevenue from '../../screens/MonthlyRevenue/MonthlyRevenue';
import ContactDetails from '../../screens/ContactDetails/ContactDetails';
import VerifyEmail from '../../screens/VerifyEmail/VerifyEmail';
import AnimationScreen from '../Wrapper/AnimationScreen';
import ProgressBarProvider from '../../shared/ProgressBar/ProgressBarProvider';
import SetupTwoFactorAuth from '../../screens/SetupTwoFactorAuth/SetupTwoFactorAuth';
import TwoFactorAuth from '../../screens/TwoFactorAuth/TwoFactorAuth';

export const getSignUpScreen = ({ screenQuery, defaultScreen, header }) => {
  let ScreenComponent;
  switch (screenQuery) {
    case screenMap.signUp:
      ScreenComponent = defaultScreen;
      break;
    case screenMap.businessType:
      ScreenComponent = <BusinessType />;
      break;
    case screenMap.monthlyRevenue:
      ScreenComponent = <MonthlyRevenue />;
      break;
    case screenMap.contactDetails:
      ScreenComponent = <ContactDetails />;
      break;
    case screenMap.verifyEmail:
      ScreenComponent = <VerifyEmail />;
      break;
    case screenMap.setupTwoFactorAuth:
      ScreenComponent = <SetupTwoFactorAuth />;
      break;
    case screenMap.twoFactorAuth:
      ScreenComponent = <TwoFactorAuth />;
      break;
    default:
      ScreenComponent = defaultScreen;
  }
  return (
    <ProgressBarProvider>
      {header}
      <AnimationScreen>{ScreenComponent}</AnimationScreen>
    </ProgressBarProvider>
  );
};

getSignUpScreen.propTypes = {
  header: PropTypes.node,
  screenQuery: PropTypes.string,
  defaultScreen: PropTypes.node,
};
