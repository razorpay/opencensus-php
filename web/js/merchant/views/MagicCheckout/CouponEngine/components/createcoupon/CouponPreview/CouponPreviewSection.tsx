import React from 'react';
import { Box, DotIcon, Heading, List, ListItem, ListItemText } from '@razorpay/blade/components';

type CouponPreviewSectionProps = {
  couponPreviewSectionTitle: string;
  couponPreviewSectionList: string[];
};

const CouponPreviewSection: React.FC<CouponPreviewSectionProps> = ({
  couponPreviewSectionTitle,
  couponPreviewSectionList,
}) => {
  if (couponPreviewSectionList.length === 0) return null;
  return (
    <Box>
      <Heading marginBottom="spacing.2" color="surface.text.gray.normal">
        {couponPreviewSectionTitle}
      </Heading>
      <List
        icon={() => (
          <Box marginBottom="spacing.1">
            <DotIcon size="xsmall" />
          </Box>
        )}
        marginLeft="spacing.3"
      >
        {couponPreviewSectionList.map((couponSectionList) => (
          <ListItem key={couponSectionList}>
            <ListItemText color="surface.text.gray.subtle">{couponSectionList}</ListItemText>
          </ListItem>
        ))}
      </List>
    </Box>
  );
};

export default CouponPreviewSection;
