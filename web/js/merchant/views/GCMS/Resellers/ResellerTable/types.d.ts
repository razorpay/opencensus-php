import { RESELLER_SERVICE } from './constants';

export type ResellerTableProps = {
  mode: string;
  merchantId: string;
  renderLoading: null | React.ReactNode;
  onSelection: (x: never) => void;
  refetchQuery: number;
  filterOptions: object;
  service: (typeof RESELLER_SERVICE)[keyof typeof RESELLER_SERVICE];
  programId: string;
  showFilters: boolean;
};
