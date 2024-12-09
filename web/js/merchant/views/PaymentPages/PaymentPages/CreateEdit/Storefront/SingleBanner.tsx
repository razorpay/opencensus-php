import React, { useState } from 'react';
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
  onReorder: () => void;
  onToggleEnabled: (val: boolean) => void;
  handleReplaceImage: () => void;
  handleDeleteImage: () => void;
  isMobile: boolean;
}

const StyledBannerImage = styled.img`
  width: 100px;
  height: 42px;
  border-radius: 4px;
`;

const SingleBanner: React.FC<ISingleBannerProps> = ({
  src,
  enabled,
  onReorder,
  onToggleEnabled,
  handleReplaceImage,
  handleDeleteImage,
  isMobile,
  id,
}) => {
  const [isSettingsDrawerOpen, setIsSettingsDrawerOpen] = useState(false);

  const renderActionList = () => (
    <ActionList>
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

  return (
    <Box
      display="flex"
      justifyContent="space-between"
      alignItems="center"
      width="100%"
      marginBottom="spacing.4"
    >
      <Box display="flex" gap="spacing.4" alignItems="center">
        <IconButton
          onClick={onReorder}
          accessibilityLabel={`${id}-storefront-reorder-icon`}
          size="large"
          icon={AlignJustifyIcon}
        />
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
            <DropdownOverlay>{renderActionList()}</DropdownOverlay>
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
              <BottomSheetBody>{renderActionList()}</BottomSheetBody>
            </BottomSheet>
          </>
        )}
        <Switch
          accessibilityLabel={`${id}-storefront-toggle`}
          isChecked={enabled}
          onChange={({ isChecked }) => onToggleEnabled(isChecked)}
        />
      </Box>
    </Box>
  );
};

export default SingleBanner;
