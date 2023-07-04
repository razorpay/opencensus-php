export interface InfiniteLoaderProps<ItemType> {
  url: string;
  pageSize?: number;
  className?: string;
  isCursorBased?: boolean;
  rowRenderer: (item: ItemType, allItems: ItemType[]) => React.ReactNode;
  spinner?: React.ReactNode;
  queryKey: string;
  itemsKey: string;
  searchText?: string;
  selectAll?: Record<string, unknown>;
}

export type QueryParams = {
  search_text?: string;
  cursor?: string | null;
  count?: number;
};
