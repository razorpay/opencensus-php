import React, { useState } from 'react';
import { Box, TextInput, Button, SearchIcon } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import { graphqlRequest } from '@federated/apps/shell/graphql';
import BrandModalComponent from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/components/BrandModalComponent';
import BrandsTableComponent from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/components/BrandsTableComponent';
import { BRAND_OPERATION_TYPE } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/constants';
import { BRANDS_TABLE_DATA_QUERY } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/queries';
import { useBrandsTablePayloadStore } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/stores/brandsTablePayloadStore';
import RetryOnError from 'merchant/views/BillMeSettings/common/components/RetryOnError';
import { verifyGqlErrorResponse } from 'merchant/views/BillMeSettings/common/utils';

import type {
  BrandsResponse,
  OperationType,
  BrandModalInfoType,
} from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/types';

const BrandsTableContainer = (): React.ReactElement => {
  const [brandModalInfo, setBrandModalInfo] = useState<BrandModalInfoType>({
    operationType: null,
    selectedBrandId: null,
  });

  const {
    brandsFilterPayload,
    setBrandsFilterOffset,
    setBrandsFilterSearch,
    setBrandsFilterLimit,
  } = useBrandsTablePayloadStore();

  const handleBrandNameClick = (brandId: string) => {
    setBrandModalInfo({
      operationType: BRAND_OPERATION_TYPE.READ,
      selectedBrandId: brandId,
    });
  };

  const {
    data: brandsResponse,
    isFetching,
    isError,
    error: brandsError,
    refetch,
  } = useQuery<BrandsResponse>({
    enabled: true,
    retry: false,
    refetchOnWindowFocus: false,
    queryKey: [
      'brands_table_data',
      { offset: brandsFilterPayload.offset, limit: brandsFilterPayload.limit },
    ],
    queryFn: () => {
      const variables = { ...brandsFilterPayload };
      variables.searchTerm = brandsFilterPayload?.searchTerm?.trim();

      // If search term is trimmed, update the search term in the store
      if (variables.searchTerm !== brandsFilterPayload?.searchTerm) {
        setBrandsFilterSearch(variables.searchTerm);
      }
      return graphqlRequest({
        document: BRANDS_TABLE_DATA_QUERY,
        variables,
      });
    },
  });

  const updateModalOperationType = (operationType: Exclude<OperationType, 'READ'> | null) => {
    setBrandModalInfo({ ...brandModalInfo, operationType });
  };

  const totalItemCount = brandsResponse?.storeBrands?.total ?? 0;
  const brandsData = brandsResponse?.storeBrands?.storeBrands ?? [];

  const applyFilters = () => {
    if (!brandsFilterPayload.offset) {
      refetch();
    } else {
      // Reset the offset to 0 inorder to fetch the first page info, which would trigger the refetch
      setBrandsFilterOffset(0);
    }
  };

  if (isError && !isFetching) {
    const isBrandsAccessDenied = verifyGqlErrorResponse(brandsError);
    return (
      <RetryOnError
        errorText="Error in fetching Brands list"
        retryFn={!isBrandsAccessDenied ? refetch : undefined}
      />
    );
  }

  return (
    <>
      <BrandModalComponent
        onCloseModal={() => updateModalOperationType(null)}
        refetchBrandsList={refetch}
        brandModalInfo={brandModalInfo}
      />
      <Box>
        <Box
          display="flex"
          justifyContent="flex-end"
          gap="spacing.3"
          marginY="spacing.5"
          marginRight="spacing.4"
        >
          <Box width={{ base: '250px', l: '380px' }}>
            <form
              onSubmit={(e) => {
                e.preventDefault();
                applyFilters();
              }}
            >
              <TextInput
                placeholder="Search by brand name"
                accessibilityLabel="Search by brand name"
                onChange={({ value }) => setBrandsFilterSearch(value)}
                value={brandsFilterPayload.searchTerm}
              />
            </form>
          </Box>
          <Button icon={SearchIcon} onClick={applyFilters} />
        </Box>
        <BrandsTableComponent
          tableProps={{
            brandsData,
            totalItemCount,
            isRefreshing: isFetching,
            currentPage: brandsFilterPayload.offset / brandsFilterPayload.limit,
            defaultPageSize: brandsFilterPayload.limit,
            changePage: setBrandsFilterOffset,
            changePageSize: setBrandsFilterLimit,
          }}
          onBrandNameClick={handleBrandNameClick}
          onAddNewBrandClick={() => updateModalOperationType(BRAND_OPERATION_TYPE.CREATE)}
        />
      </Box>
    </>
  );
};

export default BrandsTableContainer;
