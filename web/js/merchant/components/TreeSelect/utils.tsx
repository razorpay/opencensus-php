import React from 'react';
import { Box, Text, AlertTriangleIcon, ChevronDownIcon, ChevronRightIcon } from '@razorpay/blade/components';

export const treeSelectFilterTreeNode = (input: string, treeNode: any) => {
    const treeNodeValue = treeNode.label.toLowerCase();
    const inputValue = input.toLowerCase();
    return treeNodeValue.indexOf(inputValue) >= 0;
};

export const NoResultFound = () => (
    <Box
      display="flex"
      flexDirection="column"
      alignItems="center"
      justifyContent="center"
      width="100%"
      minHeight="100px"
    >
      <Box>
        <AlertTriangleIcon color="interactive.icon.negative.normal" size="xlarge" />
      </Box>
      <Box>
        <Text color="surface.text.gray.muted" weight="semibold">
          No Result Found!
        </Text>
      </Box>
    </Box>
);

export const renderSwitcherIcon = ({ isLeaf, expanded }) => {
    if (isLeaf) {
      return null;
    }
    return expanded ? (
      <ChevronDownIcon color="surface.icon.gray.muted" />
    ) : (
      <ChevronRightIcon color="surface.icon.gray.muted" />
    );
};