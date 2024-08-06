import React from 'react';
import { Box, List, ListItem, ListItemCode, Text } from '@razorpay/blade/components';

export const MagicXBanner = ({ isStoreSettingPage }: { isStoreSettingPage: boolean }) => {
  const STORE_TITLE =
    'Only for Shopify PLUS merchants. Follow the following steps to add UI extensions on your store';
  const STORE_ITEMS = (
    <List variant="ordered" size="small">
      <ListItem>
        Go to <ListItemCode>Settings &gt; Checkout &gt; Customise (on live theme)</ListItemCode>
      </ListItem>
      <ListItem>
        Click
        <ListItemCode>"Add App Block"</ListItemCode>
        under each section to find MagicX UI extensions
      </ListItem>
      <ListItem>
        Select the ones you want and click
        <ListItemCode>"Save"</ListItemCode>
      </ListItem>
    </List>
  );

  const COD_TITLE =
    'For enabling COD configurations on your Shopify store, Follow the following steps on your Shopify admin';
  const COD_ITEMS = (
    <List variant="ordered" size="small">
      <ListItem>
        Go to <ListItemCode>Settings &gt; Payments &gt; Payment Customisations</ListItemCode>
      </ListItem>
      <ListItem>
        Select the customisation by Razorpay MagicX and{' '}
        <ListItemCode>click "Activate"</ListItemCode>
      </ListItem>
    </List>
  );

  return (
    <Box elevation="midRaised" padding="spacing.4">
      <Text>{isStoreSettingPage ? STORE_TITLE : COD_TITLE}</Text>
      {isStoreSettingPage ? STORE_ITEMS : COD_ITEMS}
    </Box>
  );
};
