import { LeafListItem } from 'common/typings';

export type MethodProps = {
  item: LeafListItem;
};

export type MethodSectionProps = {
  id: string;
  header?: LeafListItem['header'];
  description?: LeafListItem['listDescription'];
  children?: React.ReactNode;
  trailing?: React.ReactNode;
};

export type IntlBankTransferProps = MethodProps & {
  user: {
    promoter_pan_name: string | null;
    iec_code: string | null;
    merchant: {
      purpose_code: string | null;
      category: string | null;
    };
  };
  showNotification: (payload: { type: string; message: unknown }) => void;
};
