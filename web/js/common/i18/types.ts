import { ConfigTagType } from 'merchant/constants/tags';

export type I18ContextStateType = {
  isConfigTagEnabled: (path: ConfigTagType) => boolean;
};
