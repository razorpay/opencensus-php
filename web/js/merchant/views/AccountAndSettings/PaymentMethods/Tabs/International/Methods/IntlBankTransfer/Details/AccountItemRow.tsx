import React, { useEffect, useState } from 'react';
import { Box, Text, Divider, Tooltip, IconButton, CopyIcon } from '@razorpay/blade/components';

import copyToClipboard from 'common/utils/copyToClipboard';
import { track } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/analytics/track';

const AccountRow = ({
  label,
  value,
  showDivider,
}: {
  label: string;
  value?: string;
  showDivider?: boolean;
}): JSX.Element => {
  const [isCopied, setIsCopied] = useState(false);

  const handleCopy = (text: string) => {
    copyToClipboard(text);
    setIsCopied(true);

    setTimeout(() => {
      setIsCopied(false);
    }, 2000);

    track('clicked', { objectName: 'details copy single account', value: value ?? '' });
  };

  useEffect(() => {
    track('render', { objectName: 'details row', value: value ?? '' });
  }, [value]);

  return (
    <>
      <Box
        display="grid"
        gridTemplateColumns={{
          base: '1fr 1fr',
          m: '1fr 3fr',
        }}
        gap="spacing.3"
        paddingY="spacing.4"
      >
        <Text color="surface.text.gray.subtle">{label}</Text>
        {value && (
          <Box display="flex" gap="spacing.2">
            <Text>{value}</Text>
            <Tooltip content={isCopied ? 'Copied' : 'Copy detail'}>
              <IconButton
                icon={(): JSX.Element => <CopyIcon color="surface.icon.primary.normal" />}
                onClick={() => handleCopy(value)}
                accessibilityLabel="Copy detail"
              />
            </Tooltip>
          </Box>
        )}
      </Box>
      {showDivider && <Divider testID="row-divider" />}
    </>
  );
};

export default AccountRow;
