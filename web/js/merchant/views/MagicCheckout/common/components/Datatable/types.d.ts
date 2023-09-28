export type ColumnDef<TData> = {
  title: string;
  columnClass?: string;
  value: (item: TData) => string | number | React.ReactNode;
};
