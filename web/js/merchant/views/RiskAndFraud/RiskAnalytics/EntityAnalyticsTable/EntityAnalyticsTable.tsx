import React, { useState } from 'react';
import {
  ActionList,
  ActionListItem,
  Box,
  Button,
  ChevronDownIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  Dropdown,
  DropdownLink,
  DropdownOverlay,
  Text,
} from '@razorpay/blade/components';
import { convertToMajorUnit, formatNumber } from '@razorpay/i18nify-js/currency';
import { useQuery } from '@tanstack/react-query';

import RadioButtonGroup from 'common/components/RadioButtonGroup';
import { getCountryName } from 'merchant/views/Transactions/v1/B2bPayments/utils';

import SkeletonTable from './SkeletonTable';
import { ANALYTICS_TABLE_COLUMNS, ANALYTICS_TABLE_HEADER, TABLE_METRIC_OPTIONS } from './constants';
import { trackEvent } from '../../common/trackEvents';
import {
  AnalyticsTableWrapper,
  TableContainer,
  Table,
  TableHead,
  TableBody,
  TableRow,
  TableHeaderCell,
  TableCell,
} from '../../components/styled';
import { fetchTableData } from '../services';
import { AnalyticsEntity, DateRange } from '../types';

const ROWS_PER_PAGE = 6;

export interface EntityAnalyticsTableProps {
  entity: AnalyticsEntity;
  dateRange: DateRange;
}

const EntityAnalyticsTable: React.FC<EntityAnalyticsTableProps> = (props) => {
  const { entity, dateRange } = props;
  const [groupBy, setGroupBy] = useState<'cards_iin' | 'cards_country'>('cards_iin');
  const [tableMetric, setTableMetric] = useState('amount');
  const [currentPage, setCurrentPage] = useState(1);
  const { startDate, endDate } = dateRange;

  const {
    isLoading,
    isError,
    data: queryData = [],
  } = useQuery({
    queryKey: [entity, { startDate, endDate, groupBy }],
    queryFn: () => fetchTableData({ entity, dateRange, groupBy }),
    cacheTime: 15 * 60 * 1000, // Cache data for 15 minutes
    staleTime: 15 * 60 * 1000, // Data remains fresh for 15 minutes
    retry: false,
    refetchOnWindowFocus: false,
    enabled: startDate !== null && endDate !== null,
  });

  const tableColumns = ANALYTICS_TABLE_COLUMNS[entity][tableMetric];
  const tableHeader = ANALYTICS_TABLE_HEADER[entity][tableMetric];

  const dataLength = queryData ? queryData.length : 0; // Length of data or 0 if queryData is undefined
  // Calculate total number of pages
  const totalPages = Math.ceil(dataLength / ROWS_PER_PAGE);

  // Calculate start and end indices for current page
  const startIndex = (currentPage - 1) * ROWS_PER_PAGE + 1;
  const endIndex = Math.min(startIndex + ROWS_PER_PAGE - 1, dataLength);

  // Slice data for current page
  const visibleRows = queryData.slice(startIndex - 1, endIndex);
  // Calculate the number of empty rows to add
  const emptyRowsCount = ROWS_PER_PAGE - visibleRows.length;

  const isPrevDisabled = isLoading || currentPage === 1;
  const isNextDisabled = isLoading || currentPage >= totalPages;

  const handleGroupby = ({ name }) => {
    setGroupBy(name);
    setCurrentPage(1);
    trackEvent({
      objectName: 'GroupBy',
      actionName: 'Change',
      properties: { section: entity, groupBy: name },
    });
  };

  const handleMetricChange = (value: string) => {
    setTableMetric(value);
    trackEvent({
      objectName: 'TableMetric',
      actionName: 'Change',
      properties: { section: entity, metric: value },
    });
  };

  // Function to handle previous page
  const handlePreviousPage = () => setCurrentPage((prevPage) => Math.max(prevPage - 1, 1));
  // Function to handle next page
  const handleNextPage = () => setCurrentPage((prevPage) => Math.min(prevPage + 1, totalPages));

  return (
    <AnalyticsTableWrapper data-testid="analytics-table">
      <TableContainer>
        <Box display="flex" justifyContent="space-between" marginBottom="spacing.5">
          <Box display="flex" alignItems="center" flexWrap="wrap" gap="spacing.4">
            <Dropdown>
              <DropdownLink icon={ChevronDownIcon} iconPosition="right">
                {groupBy === 'cards_iin' ? 'Card BINs' : 'Countries'}
              </DropdownLink>
              <DropdownOverlay>
                <ActionList>
                  <ActionListItem title="Card BINs" value="cards_iin" onClick={handleGroupby} />
                  <ActionListItem title="Countries" value="cards_country" onClick={handleGroupby} />
                </ActionList>
              </DropdownOverlay>
            </Dropdown>
            <Text weight="semibold" size="large">
              {tableHeader}
            </Text>
          </Box>
          <RadioButtonGroup
            testID="metric-selector"
            options={TABLE_METRIC_OPTIONS}
            selectedOption={tableMetric}
            onChange={handleMetricChange}
            isDisabled={isLoading}
          />
        </Box>
        <Table>
          <TableHead>
            <TableRow>
              <TableHeaderCell>
                {groupBy === 'cards_iin' ? 'Card BIN' : 'Countries'}
              </TableHeaderCell>
              {tableColumns.map((heading) => (
                <TableHeaderCell key={heading} align="right">
                  {heading}
                </TableHeaderCell>
              ))}
            </TableRow>
          </TableHead>
          <TableBody>
            {isLoading ? (
              <SkeletonTable rows={8} columns={4} />
            ) : (
              <>
                {visibleRows?.length > 0 &&
                  visibleRows.map((dataItem, index) => {
                    const { card_iin, card_country, entity_data, payment } = dataItem ?? {};
                    const countryName = getCountryName(card_country);
                    const entityGroup = groupBy === 'cards_iin' ? card_iin : countryName;

                    // Extracting values and ensuring they are numeric, defaulting to 0 if they are falsy
                    const paymentAmount = convertToMajorUnit(Number(payment?.amount) || 0, {
                      currency: 'INR',
                    });
                    const entityAmount = convertToMajorUnit(Number(entity_data?.amount) || 0, {
                      currency: 'INR',
                    });
                    const paymentCount = Number(payment?.count || 0);
                    const entityCount = Number(entity_data?.count || 0);

                    // Calculating ratios
                    const numberOfTxns =
                      tableMetric === 'amount' ? Math.floor(paymentAmount) : paymentCount;
                    const numberOfEntity =
                      tableMetric === 'amount' ? Math.floor(entityAmount) : entityCount;
                    const entityRatio = ((numberOfEntity / (numberOfTxns || 1)) * 100 || 0).toFixed(
                      2,
                    );

                    return (
                      <TableRow key={index}>
                        <TableCell>{entityGroup}</TableCell>
                        <TableCell align="right">
                          {tableMetric === 'amount'
                            ? formatNumber(numberOfTxns, { currency: 'INR' })
                            : numberOfTxns}
                        </TableCell>
                        <TableCell align="right">
                          {tableMetric === 'amount'
                            ? formatNumber(numberOfEntity, { currency: 'INR' })
                            : numberOfEntity}
                        </TableCell>
                        <TableCell align="right">{entityRatio}%</TableCell>
                      </TableRow>
                    );
                  })}
                {emptyRowsCount > 0 || isError ? (
                  <TableRow data-testid="empty-row" height={emptyRowsCount * 45}>
                    <TableCell colSpan={4} align="center">
                      {visibleRows.length === 0
                        ? isError
                          ? 'Fetching failed! Try later'
                          : 'No data available'
                        : ''}
                    </TableCell>
                  </TableRow>
                ) : null}
              </>
            )}
          </TableBody>
        </Table>
        {queryData?.length > 0 && (
          <Box display="flex" marginTop="spacing.4">
            <Box display="flex" alignItems="center" marginLeft="auto">
              <Text color="surface.text.gray.muted">
                {startIndex}-{endIndex} of {dataLength}
              </Text>
              <Box marginLeft="spacing.4">
                <Button
                  variant="tertiary"
                  size="small"
                  icon={ChevronLeftIcon}
                  isDisabled={isPrevDisabled}
                  onClick={handlePreviousPage}
                />
                <Button
                  variant="tertiary"
                  size="small"
                  icon={ChevronRightIcon}
                  isDisabled={isNextDisabled}
                  onClick={handleNextPage}
                />
              </Box>
            </Box>
          </Box>
        )}
      </TableContainer>
    </AnalyticsTableWrapper>
  );
};

export default EntityAnalyticsTable;
