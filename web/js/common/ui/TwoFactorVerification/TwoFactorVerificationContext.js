import React from 'react';

/**
 * Context properties for the TwoFactorVerificationContext.
 *
 * @typedef {Object} ContextProps
 * @property {function({
 *   enforceVerifyOtp?: boolean
 *   isNewAccountAndSettingsPage?: boolean,
 *   modes?: string[],
 *   onBankAccountUpdateReq?: boolean,
 *   onFlowTermination?: function():void,
 *   onUserTwoFaVerified: function():void,
 *   onWrongOtpCallback?: function():void,
 * }):void} criticalFlow
 */

/**
 * @type {React.Context<ContextProps>}
 */
const TwoFactorVerificationContext = React.createContext();

export const useTwoFactorVerificationContext = () => React.useContext(TwoFactorVerificationContext);

export default TwoFactorVerificationContext;
