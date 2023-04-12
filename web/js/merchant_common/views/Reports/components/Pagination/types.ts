export interface PaginationProps {
  totalCount: number;
  pageSize: number;
  currentPage: number;
  onPageChange: (x: number) => void;
  siblingCount?: number;
}
