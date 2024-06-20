import React from 'react';
import { Box, List, ListItem, ListItemText } from '@razorpay/blade/components';

interface ListSuggestionBoxProps {
  listItems: string[];
}

const ListSuggestionBox: React.FC<ListSuggestionBoxProps> = ({ listItems }) => {
  return (
    <Box paddingX="spacing.2">
      <List>
        {listItems.map((item, idx) => (
          <ListItem key={idx}>
            <ListItemText color="surface.text.gray.subtle" wordBreak="break-word">
              {item}
            </ListItemText>
          </ListItem>
        ))}
      </List>
    </Box>
  );
};

export default ListSuggestionBox;
