import React, { useState } from 'react';
import { SortableElement, SortableElementProps, SortableHandle } from 'react-sortable-hoc';

import {
  Box,
  IconButton,
  Switch,
  AlignJustifyIcon,
  EditInlineIcon,
  Dropdown,
  DropdownOverlay,
  DropdownLink,
  ActionList,
  ActionListItem,
  BottomSheetHeader,
  BottomSheetBody,
  BottomSheet,
} from '@razorpay/blade/components';
import styled from 'styled-components';

interface ISingleBannerProps {
  id: string;
  src: string;
  enabled: boolean;
  onToggleEnabled: (val: boolean) => void;
  handleReplaceImage: () => void;
  handleDeleteImage: () => void;
  handleEditImage: () => void;
  isMobile: boolean;
  isSwitchEnabled: boolean;
}

const StyledBannerImage = styled.img`
  width: 100px;
  height: 42px;
  border-radius: 4px;
`;

const DraggableIcon = styled.div`
  cursor: grab;
`;

const DragHandle = SortableHandle(() => (
  <DraggableIcon>
    <AlignJustifyIcon size="large" />
  </DraggableIcon>
));

type SingleBannerProps = ISingleBannerProps & SortableElementProps;
const RenderActionList = ({ id, handleReplaceImage, handleDeleteImage, handleEditImage }) => {
  return (
    <ActionList>
      <ActionListItem
        title="Edit Image"
        value={`${id}-edit-banner`}
        testID={`${id}-edit-banner`}
        onClick={handleEditImage}
      />
      <ActionListItem
        title="Replace Image"
        value={`${id}-replace-banner`}
        onClick={handleReplaceImage}
        testID={`${id}-replace-banner`}
      />
      <ActionListItem
        intent="negative"
        title="Delete Image"
        value={`${id}-delete-banner`}
        testID={`${id}-delete-banner`}
        onClick={handleDeleteImage}
      />
    </ActionList>
  );
};

const SingleBanner: React.ComponentType<SingleBannerProps> = SortableElement(
  ({
    src,
    enabled,
    onToggleEnabled,
    handleReplaceImage,
    handleDeleteImage,
    isMobile,
    id,
    handleEditImage,
    isSwitchEnabled,
  }) => {
    const [isSettingsDrawerOpen, setIsSettingsDrawerOpen] = useState(false);

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
          <StyledBannerImage src={src} alt={`${id}-storefront-banner`} />
        </Box>
        <Box display="flex" gap="spacing.4" alignItems="center">
          {!isMobile ? (
            <Dropdown>
              <DropdownLink
                color="neutral"
                icon={EditInlineIcon}
                testID="edit-banner"
                accessibilityLabel={`${id}-storefront-edit-icon`}
              />
              <DropdownOverlay>
                <RenderActionList
                  id={id}
                  handleDeleteImage={handleDeleteImage}
                  handleEditImage={handleEditImage}
                  handleReplaceImage={handleReplaceImage}
                />
              </DropdownOverlay>
            </Dropdown>
          ) : (
            <>
              <IconButton
                onClick={() => setIsSettingsDrawerOpen(true)}
                accessibilityLabel="Open Settings"
                size="large"
                emphasis="intense"
                icon={EditInlineIcon}
              />
              <BottomSheet
                isOpen={isSettingsDrawerOpen}
                onDismiss={() => setIsSettingsDrawerOpen(false)}
              >
                <BottomSheetHeader title="Banner Settings" />
                <BottomSheetBody>
                  <RenderActionList
                    id={id}
                    handleDeleteImage={handleDeleteImage}
                    handleEditImage={handleEditImage}
                    handleReplaceImage={handleReplaceImage}
                  />
                </BottomSheetBody>
              </BottomSheet>
            </>
          )}
          <Switch
            isDisabled={!isSwitchEnabled}
            accessibilityLabel={`${id}-storefront-toggle`}
            isChecked={enabled}
            onChange={({ isChecked }) => onToggleEnabled(isChecked)}
          />
        </Box>
      </Box>
    );
  },
);

export default SingleBanner;
