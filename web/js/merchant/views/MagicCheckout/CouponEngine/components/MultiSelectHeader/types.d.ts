export interface MultiSelectHeaderProps {
  onSelect: (event: React.ChangeEvent<HTMLInputElement>) => void;
  checked: boolean;
  disableMultiSelect: boolean;
  showAggregatedActions: boolean;
  openModal: (options: { size: string; className?: string; component: JSX.Element }) => void;
  checkedIds: any;
  showNotification: (notification: any) => void;
  setCheckedIds: (checkedIds: any) => void;
  updateCouponsCb: (coupon: any, status: string) => void;
}
