import { ConfigTagType } from 'merchant/constants/tags';

export type I18ContextStateType = {
  isConfigTagEnabled: (path: ConfigTagType) => boolean;
};

export type WithI18nifyStateProps = {
  setI18nState: (data: unknown) => void;
};
