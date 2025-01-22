import React from 'react';
import { Box, TextInput, Link, Button } from '@razorpay/blade/components';

export const FilterOptimizerAccounts = ({
  accountId,
  setAccountId,
  count,
  setCount,
  handleSearch,
}: {
  accountId: string;
  setAccountId: (value: string) => void;
  count: string;
  setCount: (value: string) => void;
  handleSearch: () => void;
}): JSX.Element => {
  const handleCountChange = ({ value }: { value?: string }) => {
    if (/^[0-9]*\.?[0-9]*$/.test(value as string)) {
      setCount(value as string);
    }
  };

  const handleClearFilter = () => {
    setAccountId('');
    setCount('');
  };

  return (
    <Box display="flex" gap="spacing.6">
      <TextInput
        name="account_id"
        label="Account ID"
        onChange={({ value }) => setAccountId(value as string)}
        value={accountId}
      />
      <TextInput
        name="count"
        label="Count"
        type="number"
        onChange={handleCountChange}
        value={count}
      />
      <Link variant="button" onClick={handleClearFilter} marginTop="spacing.6">
        Clear
      </Link>
      <Button variant="tertiary" onClick={handleSearch} marginTop="spacing.6">
        Search
      </Button>
    </Box>
  );
};
