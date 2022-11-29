import React from 'react';
const TwoFactorVerificationContext = React.createContext();

export const useTwoFactorVerificationContext = () => React.useContext(TwoFactorVerificationContext);

export default TwoFactorVerificationContext;
