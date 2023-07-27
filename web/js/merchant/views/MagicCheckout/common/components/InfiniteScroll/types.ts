export interface InfiniteLoaderProps<ItemType> {
  url: string;
  pageSize?: number;
  className?: string;
  isCursorBased?: boolean;
  rowRenderer: (item: ItemType) => React.ReactNode;
  spinner?: React.ReactNode;
  queryKey: string;
  itemsKey: string;
  searchText?: string;
}

export type QueryParams = {
  search_text?: string;
  cursor?: string | null;
  count?: number;
};
