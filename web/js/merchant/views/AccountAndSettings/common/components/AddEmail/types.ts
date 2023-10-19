import { ActiveModalI } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import { Store } from 'common/typings';

export interface AddEmailProps {
  entity: ActiveModalI;
  screen: string;
  closeModal: () => void;
  user: Store['session']['user'];
  openModal: (args: {
    isNew: boolean;
    component: JSX.Element;
    size: string;
    queryParams?: Record<string, ActiveModalI['queryParam']>;
  }) => void;
}
