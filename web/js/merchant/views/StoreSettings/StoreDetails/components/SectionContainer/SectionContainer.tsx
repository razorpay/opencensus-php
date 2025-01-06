import React, { PropsWithChildren } from 'react';
import { Box, Heading, Divider, Text } from '@razorpay/blade/components';

import { COPY_OPTION_ENABLED_FIELDS } from 'merchant/views/StoreSettings/StoreDetails/constants';
import FieldInfo from 'merchant/views/StoreSettings/StoreDetails/components/FieldInfo';

type FieldInfoType = {
  key: string;
  label: string;
};

type SectionContainerProps = PropsWithChildren<{
  fieldsCollection: FieldInfoType[];
  valueMap: { [key: string]: string };
  sectionHeading: string;
  groupTitle?: string;
}>;

const SectionContainer = ({
  fieldsCollection,
  valueMap,
  children,
  sectionHeading,
  groupTitle,
}: SectionContainerProps): React.ReactElement => {
  return (
    <Box>
      <Box
        padding="spacing.5"
        backgroundColor="surface.background.cloud.subtle"
        borderColor="surface.border.gray.muted"
        borderTopLeftRadius="medium"
        borderTopRightRadius="medium"
      >
        <Heading>{sectionHeading}</Heading>
      </Box>
      <Box
        paddingX="spacing.5"
        borderColor="surface.border.gray.muted"
        borderBottomLeftRadius="medium"
        borderBottomRightRadius="medium"
        borderTopWidth="none"
      >
        {groupTitle ? (
          <Box paddingTop="spacing.5">
            <Text color="surface.text.gray.subtle" weight="semibold">
              {groupTitle}
            </Text>
          </Box>
        ) : null}
        {fieldsCollection.map((field, index) => {
          const { key, label } = field;
          return (
            <>
              <FieldInfo
                key={index}
                label={label}
                value={valueMap[key] || '-'}
                enableCopyOption={COPY_OPTION_ENABLED_FIELDS[key] && valueMap?.[key]?.length > 0}
              />
              {index < fieldsCollection.length - 1 && <Divider />}
            </>
          );
        })}
        {children}
      </Box>
    </Box>
  );
};

export default SectionContainer;
