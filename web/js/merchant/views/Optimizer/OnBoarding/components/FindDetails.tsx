import React from 'react';
import { Box, List, ListItem, ListItemText } from '@razorpay/blade/components';
import { GIF_ASSETS, GATEWAY_DETAILS_MAP } from 'merchant/views/Optimizer/OnBoarding/constants';

export const FindDetails = (props) => {
  const { gateway } = props;
  return (
    <Box width="max-content">
      <img src={GIF_ASSETS[gateway]} alt={gateway} width="300px" />
      <Box maxWidth="314px">
        <List size="medium" variant="ordered" marginTop="spacing.5">
          {GATEWAY_DETAILS_MAP[gateway]?.map((item) => (
            <ListItem>
              <ListItemText color="surface.text.gray.normal">{item}</ListItemText>
            </ListItem>
          ))}
        </List>
      </Box>
    </Box>
  );
};
