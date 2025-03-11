import React from 'react';
import { SortableElement, SortableElementProps, SortableHandle } from 'react-sortable-hoc';

import {
  Box,
  IconButton,
  AlignJustifyIcon,
  EditInlineIcon,
  TrashIcon,
} from '@razorpay/blade/components';
import styled from 'styled-components';
import { ISingleSocialHandleProps } from '../types';

const DraggableIcon = styled.div`
  cursor: grab;
`;

const DragHandle = SortableHandle(() => (
  <DraggableIcon>
    <AlignJustifyIcon size="large" />
  </DraggableIcon>
));

type SingleSocialHandleProps = ISingleSocialHandleProps & SortableElementProps;

const SingleSocialHandle: React.ComponentType<SingleSocialHandleProps> = SortableElement(
  ({ id, platform, logo_url,showDeleteConfirmation,editSocialHandle }) => {
    return (
      <Box
        display="flex"
        justifyContent="space-between"
        alignItems="center"
        width="100%"
        marginBottom="spacing.4"
      >
        <Box display="flex" gap="spacing.4" alignItems="center">
          <DragHandle />
          <img src={logo_url} alt={platform} width={24} height={24} />
        </Box>
        <Box display="flex" gap="spacing.7" alignItems="center">
          <IconButton
            onClick={() => editSocialHandle(platform)}
            accessibilityLabel={`edit-icon-${platform}`}
            size="large"
            emphasis="intense"
            icon={EditInlineIcon}
          />
          <IconButton
            onClick={() => showDeleteConfirmation(platform)}
            accessibilityLabel={`delete-icon-${platform}`}
            size="large"
            emphasis="intense"
            icon={TrashIcon}
          />
        </Box>
      </Box>
    );
  },
);

export default SingleSocialHandle;
