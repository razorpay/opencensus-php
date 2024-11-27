import React from 'react';
import { List, ListItem, ListItemText } from '@razorpay/blade/components';

export const GetSimplPoints = () => {
  return (
    <List size="small" variant="unordered">
      <ListItem>
        <ListItemText color="surface.text.gray.subtle">
          Reach out to your relationship manager at Simpl to collect your gateway terminal ID to get
          started
        </ListItemText>
      </ListItem>
    </List>
  );
};
