import React from 'react';
import { Box, Link, Text, TextInput } from '@razorpay/blade/components';

import { useCheckoutEditor } from './context';

const Suggestion: React.FC = () => {
  const [suggestionText, setSuggestionText] = React.useState('');
  const { handleSuggestionSubmit } = useCheckoutEditor();
  return (
    <Box marginTop="44px">
      <Text>Didn’t find the feature you are looking for?</Text>
      <Box display="flex" gap="12px" marginTop="12px">
        <Box width="100%">
          <TextInput
            placeholder="Add suggestion"
            label=""
            onChange={({ value }) => {
              setSuggestionText(value || '');
            }}
          />
        </Box>
        <Link
          variant="button"
          isDisabled={suggestionText.length === 0}
          onClick={() => {
            handleSuggestionSubmit({ suggestion: suggestionText, page: 'checkout-features' });
          }}
        >
          Submit
        </Link>
      </Box>
    </Box>
  );
};

export default Suggestion;
