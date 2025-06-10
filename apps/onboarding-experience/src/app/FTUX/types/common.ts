// Two FA auth component props
export type TwoFaAuthProps = {
  enforceVerifyOtp?: boolean;
  modes?: Array<'live' | 'test'>;
  onUserTwoFaVerified?: () => void;
  onWrongOtpCallback?: () => void;
  onFlowTermination?: () => void;
};

export enum ApiKeysModalScreens {
  REGEN = 'REGEN',
  REVEAL = 'REVEAL',
}

export enum ModalStatus {
  INITIAL = 'initial',
  LOADING = 'loading',
  OPEN = 'open',
}
