import React, { useEffect } from 'react';
import {
  Box,
  Heading,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
  Text,
  InfoIcon,
  Spinner,
  Link,
  RefreshIcon,
} from '@razorpay/blade/components';
import { useQuery, useQueryClient } from '@tanstack/react-query';

import { REACT_QUERY_CACHE_KEYS } from 'common/constant';
import { graphqlRequest } from 'common/services/graphql/graphql-client';
import { Store } from 'merchant/views/StoreSettings/types';
import { TERMINAL_BY_STORE_ID } from 'merchant/views/StoreSettings/StoreDetails/queries';
import SectionContainer from 'merchant/views/StoreSettings/StoreDetails/components/SectionContainer';

import type { TerminalsResponse } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/types';

type DigitalBillingInfoProps = {
  fetchedStoreInfo: Store;
};

const DigitalBillingInfo = ({ fetchedStoreInfo }: DigitalBillingInfoProps): React.ReactElement => {
  const queryClient = useQueryClient();

  const {
    data: storeTerminalsInfo,
    isFetching: isTerminalsLoading,
    isError: isErrorInFetchingStoreTerminals,
    refetch,
  } = useQuery<TerminalsResponse>({
    enabled: !fetchedStoreInfo?.dates?.deletedAt,
    refetchOnWindowFocus: false,
    queryKey: [REACT_QUERY_CACHE_KEYS.STORE_TERMINALS, fetchedStoreInfo?.id],
    queryFn: async () =>
      graphqlRequest({
        document: TERMINAL_BY_STORE_ID,
        variables: { storeIds: [fetchedStoreInfo?.id], offset: 0, limit: 30 },
      }),
  });

  useEffect(() => {
    return () => {
      queryClient.removeQueries({
        queryKey: [REACT_QUERY_CACHE_KEYS.STORE_TERMINALS, fetchedStoreInfo?.id],
      });
    };
  }, []);

  const terminalsData = storeTerminalsInfo?.storeTerminals?.storeTerminals || [];

  const renderTerminalsList = () => {
    if (isTerminalsLoading) {
      return (
        <Box display="flex" alignItems="center" justifyContent="center" height="50vh">
          <Spinner accessibilityLabel="Store Terminals loading" />
        </Box>
      );
    }
    if (isErrorInFetchingStoreTerminals) {
      return (
        <Box
          display="flex"
          alignItems="center"
          justifyContent="center"
          height="100%"
          gap="spacing.3"
        >
          <Text size="medium" color="interactive.text.negative.normal">
            Error in fetching store terminals
          </Text>
          <Link
            size="medium"
            variant="button"
            icon={RefreshIcon}
            iconPosition="right"
            onClick={() => refetch()}
          >
            Retry
          </Link>
        </Box>
      );
    }
    if (!fetchedStoreInfo?.dates?.deletedAt) {
      return (
        <Box>
          <Box
            padding="spacing.5"
            backgroundColor="surface.background.cloud.subtle"
            borderColor="surface.border.gray.muted"
            borderTopLeftRadius="medium"
            borderTopRightRadius="medium"
          >
            <Heading>Billing Terminals</Heading>
          </Box>
          <Table
            showBorderedCells
            data={{
              nodes: terminalsData,
            }}
            rowDensity="comfortable"
          >
            {(tableData) => (
              <>
                <TableHeader>
                  <TableHeaderRow>
                    <TableHeaderCell>
                      <Box whiteSpace="normal">Terminal No.</Box>
                    </TableHeaderCell>
                    <TableHeaderCell>
                      <Box whiteSpace="normal">Name</Box>
                    </TableHeaderCell>
                    <TableHeaderCell>
                      <Box whiteSpace="normal">MAC</Box>
                    </TableHeaderCell>
                    <TableHeaderCell>
                      <Box whiteSpace="normal">IP Address</Box>
                    </TableHeaderCell>
                  </TableHeaderRow>
                </TableHeader>
                {terminalsData.length ? (
                  <TableBody>
                    {tableData.map((tableItem, index) => (
                      <TableRow key={tableItem?.id} item={tableItem}>
                        <TableCell>
                          <Box whiteSpace="normal">
                            <Text>{index + 1}</Text>
                          </Box>
                        </TableCell>
                        <TableCell>
                          <Box whiteSpace="normal">
                            <Text>{tableItem?.name || '-'}</Text>
                          </Box>
                        </TableCell>
                        <TableCell>
                          <Box whiteSpace="normal">
                            <Text>{tableItem?.terminalInfo?.macAddress || '-'}</Text>
                          </Box>
                        </TableCell>
                        <TableCell>
                          <Box whiteSpace="normal">
                            <Text>{tableItem?.terminalInfo?.ipAddress || '-'}</Text>
                          </Box>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                ) : (
                  // Grid column end value is given in accordance to number of table columns + 1
                  <Box gridColumn={`1/5`} padding="spacing.6">
                    <Box gap="spacing.2" display="flex" justifyContent="center" alignItems="center">
                      <InfoIcon size="xlarge" />{' '}
                      <Text weight="semibold" variant="body">
                        No Billing Terminals added
                      </Text>
                    </Box>
                  </Box>
                )}
              </>
            )}
          </Table>
        </Box>
      );
    }
    return null;
  };

  return (
    <Box
      paddingX="spacing.7"
      paddingY="spacing.5"
      display="flex"
      flexDirection="column"
      gap="spacing.7"
    >
      {/* Product Specific Info */}
      <Box width="100%">
        <SectionContainer
          sectionHeading="Product Specific Info"
          fieldsCollection={[{ key: 'selectedBrand', label: 'Selected Brand' }]}
          valueMap={{ selectedBrand: fetchedStoreInfo?.brand?.name }}
        />
      </Box>

      {/* Billing Terminals */}
      {renderTerminalsList()}
    </Box>
  );
};

export default DigitalBillingInfo;
