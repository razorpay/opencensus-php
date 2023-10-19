import { ActiveModalI } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';

export interface AccountDetailsUpdateProps {
  entity: ActiveModalI;
  onEnterEmailSubmit?: (args: { userInput: string; setIsLoading: (arg: boolean) => void }) => void;
  onContactUpdateSubmit?: (args: {
    contactMobile: string;
    setIsLoading: (arg: boolean) => void;
  }) => void;
  onModalDismiss?: () => void;
}
