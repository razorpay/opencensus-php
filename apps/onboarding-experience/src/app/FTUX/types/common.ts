// Two FA auth component props
export type TwoFaAuthProps = {
  enforceVerifyOtp?: boolean;
  modes?: Array<'live' | 'test'>;
  onUserTwoFaVerified?: () => void;
  onWrongOtpCallback?: () => void;
  onFlowTermination?: () => void;
};
