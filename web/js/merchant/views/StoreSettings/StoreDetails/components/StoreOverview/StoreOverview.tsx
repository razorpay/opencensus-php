import React, { useState, useEffect } from 'react';
import { Box, Text, Divider } from '@razorpay/blade/components';

import { STORE_TYPE_MAP } from 'merchant/views/BillMeSettings/common/constants';
import {
  LOCATION_DETAILS_FIELDS,
  STORE_CONTACT_DETAILS_FIELDS,
  STORE_DETAILS_FIELDS,
} from 'merchant/views/StoreSettings/StoreDetails/constants';
import FieldInfo from 'merchant/views/StoreSettings/StoreDetails/components/FieldInfo';
import SectionContainer from 'merchant/views/StoreSettings/StoreDetails/components/SectionContainer';

import type { CustomField, Store } from 'merchant/views/StoreSettings/types';

type StoreOverviewProps = {
  fetchedStoreInfo: Store;
};

type StoreInfoType = {
  [key: string]: any;
  customFields: CustomField[];
};

const StoreOverview = ({ fetchedStoreInfo }: StoreOverviewProps): React.ReactElement => {
  const [storeInfo, setStoreInfo] = useState<StoreInfoType>({} as StoreInfoType);

  useEffect(() => {
    const { name, address, storeInfo, customFields, contact } = fetchedStoreInfo;
    const modifiedStoreInfo = {
      name,
      customFields,
      ...address,
      ...storeInfo,
      primaryContactNumber: contact?.primary?.number,
      secondaryContactNumber: contact?.secondary?.number,
      storeType: STORE_TYPE_MAP[storeInfo?.storeType]?.label,
    };
    setStoreInfo(modifiedStoreInfo);
  }, [fetchedStoreInfo]);

  return (
    <Box
      paddingX="spacing.7"
      paddingY="spacing.5"
      display="flex"
      gap="spacing.7"
      flexDirection={{ base: 'column', l: 'row' }}
    >
      <Box flex={1.7}>
        {/* Basic Details */}
        <SectionContainer
          sectionHeading="Details"
          fieldsCollection={STORE_DETAILS_FIELDS}
          valueMap={storeInfo}
          groupTitle="Store Details"
          children={
            <>
              <Divider />
              <Box paddingTop="spacing.5">
                <Text color="surface.text.gray.subtle" weight="semibold">
                  Custom Fields
                </Text>
              </Box>
              {storeInfo?.customFields?.length ? (
                storeInfo.customFields.map((field, index) => {
                  const { title, value } = field;
                  return (
                    <Box key={index}>
                      <FieldInfo label={title || '-'} value={value || '-'} />
                      {index < storeInfo?.customFields.length - 1 && <Divider />}
                    </Box>
                  );
                })
              ) : (
                <Box paddingY="spacing.4">
                  <Text>-</Text>
                </Box>
              )}
            </>
          }
        />
      </Box>

      <Box flex={1} display="flex" gap="spacing.7" flexDirection="column">
        {/* Location Details */}
        <SectionContainer
          sectionHeading="Location Details"
          fieldsCollection={LOCATION_DETAILS_FIELDS}
          valueMap={storeInfo}
        />

        {/* Store Contact Details */}
        <SectionContainer
          sectionHeading="Store Contact Details"
          fieldsCollection={STORE_CONTACT_DETAILS_FIELDS}
          valueMap={storeInfo}
        />
      </Box>
    </Box>
  );
};

export default StoreOverview;
