import React from 'react';
import { Box, Button, Link, PlusIcon, Text } from '@razorpay/blade/components';

export type CreateCustomPaymentBlockHeaderProps = {
  handleCreateNewCustomBlock: () => void;
  hasMultipleCustomBlocks: boolean;
};

export default function CreateCustomPaymentBlockHeader({
  handleCreateNewCustomBlock,
  hasMultipleCustomBlocks,
}: CreateCustomPaymentBlockHeaderProps) {
  const title = hasMultipleCustomBlocks
    ? 'Your saved payment blocks'
    : 'Create a custom payment block';
  const description = 'Group your preferred payment methods together';
  return (
    <Box>
      <Box display="flex">
        <Box display="flex" flexDirection="column" flexGrow="1">
          <Text variant="body" size="medium" weight="medium" color="surface.text.gray.normal">
            {title}
          </Text>
          <Text variant="body" size="small" weight="regular" color="surface.text.gray.muted">
            {description}
          </Text>
        </Box>
        {hasMultipleCustomBlocks ? (
          <Link
            display="flex"
            alignSelf="center"
            variant="button"
            size="small"
            color="primary"
            iconPosition="right"
            icon={PlusIcon}
            onClick={handleCreateNewCustomBlock}
          >
            Create
          </Link>
        ) : (
          <Button
            onClick={handleCreateNewCustomBlock}
            accessibilityLabel="Create new custom block"
            icon={PlusIcon}
            size="small"
          />
        )}
      </Box>
    </Box>
  );
}
