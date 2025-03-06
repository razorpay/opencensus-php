import React from 'react';
import { Badge, Box, Text } from '@razorpay/blade/components';
import { useCreateConfigModal } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/store/createConfigModalStore';
import { headerTitles } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/utils/constants';

export const ColumnContainer = ({ children, title }: { title: string; children: JSX.Element }) => {
  const selectedColumns = useCreateConfigModal((state) => state.selectedColumns);
  const totalCount = Object.values(selectedColumns).reduce((acc, curr) => acc + curr.length, 0);

  return (
    <Box padding="spacing.6">
      <Box display="flex" flexDirection="row" gap="spacing.2">
        <Text variant="body" size="small" weight="semibold" color="surface.text.gray.muted">
          {title}
        </Text>
        {title === headerTitles.selectedColumns && (
          <Badge size="small" color="neutral">
            {totalCount.toString()}
          </Badge>
        )}
      </Box>
      <Box padding="spacing.3">{children}</Box>
    </Box>
  );
};
