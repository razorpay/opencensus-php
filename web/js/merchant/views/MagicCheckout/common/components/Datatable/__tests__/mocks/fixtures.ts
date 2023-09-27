import { ColumnDef } from 'merchant/views/MagicCheckout/common/components/Datatable/types';

type ItemType = {
  name: string;
  age: number;
  gender: string;
};

export const tableData: ItemType[] = [
  {
    name: 'Akash',
    age: 25,
    gender: 'male',
  },
];

export const name: ColumnDef<ItemType> = {
  title: 'Name',
  value(item: ItemType) {
    return item?.name || '-';
  },
};
export const age: ColumnDef<ItemType> = {
  title: 'Age',
  value(item: ItemType) {
    return item?.age || '-';
  },
};
export const gender: ColumnDef<ItemType> = {
  title: 'Gender',
  value(item: ItemType) {
    return item?.gender || '-';
  },
};
