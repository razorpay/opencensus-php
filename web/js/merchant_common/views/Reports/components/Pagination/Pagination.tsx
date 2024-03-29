import React from 'react';
import { usePagination, DOTS } from './Hooks/usePagination';
import { PageButton, PaginationDiv, PaginationWrapper } from './styled';
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  Text,
  IconButton,
} from 'merchant_common/views/Reports/components';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { ToggleVisibility } from 'merchant_common/views/Reports/components/styled';
import { PaginationProps } from './types';

export const Pagination = ({
  totalCount,
  siblingCount = 1,
  pageSize,
  currentPage,
  onPageChange,
}: PaginationProps) => {
  const { theme } = useTheme();

  const { paginationRange, totalPageCount } = usePagination({
    currentPage,
    totalCount,
    siblingCount,
    pageSize,
  });

  const isFirstPage = currentPage === 1;
  const isLastPage = currentPage === totalPageCount;

  const onPrevious = () => {
    if (!isFirstPage) onPageChange(currentPage - 1);
  };

  const onNext = () => {
    if (!isLastPage) onPageChange(currentPage + 1);
  };

  const showingFrom = (currentPage - 1) * pageSize;

  return totalCount ? (
    <PaginationWrapper theme={theme}>
      <PaginationDiv theme={theme}>
        {/* Toggling visibility so that this button maintains its space. */}
        <ToggleVisibility disabled={isFirstPage}>
          <IconButton
            icon={ChevronLeftIcon}
            accessibilityLabel="Previous Page"
            onClick={onPrevious}
          />
        </ToggleVisibility>

        {paginationRange.map((pageNumber, i) => {
          if (pageNumber === DOTS) {
            return (
              <PageButton disabled={true} key={i} theme={theme}>
                <Text variant="body" weight="regular" color="surface.text.gray.normal">
                  ...
                </Text>
              </PageButton>
            );
          }

          return (
            <PageButton
              onClick={() => (currentPage === pageNumber ? () => {} : onPageChange(pageNumber))}
              key={i}
              theme={theme}
              focused={currentPage === pageNumber}
              disabled={currentPage === pageNumber}
              aria-label={`Page no is ${pageNumber}`}
            >
              <Text variant="body" weight="regular" color="surface.text.gray.normal">
                {pageNumber}
              </Text>
            </PageButton>
          );
        })}
        {/* Toggling visibility so that this button maintains its space. */}
        <ToggleVisibility disabled={isLastPage}>
          <IconButton icon={ChevronRightIcon} accessibilityLabel="Next Page" onClick={onNext} />
        </ToggleVisibility>
      </PaginationDiv>
      <Text variant="caption" color="surface.text.gray.subtle">
        Showing responses {showingFrom + 1}-{isLastPage ? totalCount : showingFrom + pageSize} out
        of {totalCount}
      </Text>
    </PaginationWrapper>
  ) : (
    <></>
  );
};
