export interface FtuxConsentFooterProps {
  onOptOut: () => void;
  onProceed: () => void;
}

export interface FtuxOptOutFooterProps {
  onGoBack: () => void;
}

export interface FtuxConsentAlertProps extends FtuxConsentFooterProps {
  isConsentOpen: boolean;
}

export interface FtuxOptOutAlertProps {
  isOptAlertOpen: boolean;
  onGoback: () => void;
}

export interface FtuxHandlers {
  handleOptOutClick: () => void;
  handleProceedClick: () => void;
  handleGoBackClick: () => void;
}
