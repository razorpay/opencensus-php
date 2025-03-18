import React, { useState, useCallback, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { addSocialHandle, updatedSocialHandle } from 'merchant/reducers/paymentPages/storefront';
import { showNotification } from 'merchant_common/reducers/notifications';
import { SocialMediaHandle, SocialMediaHandles } from 'merchant/reducers/paymentPages/types';
import { uploadImageInDescription as uploadSocialHandleLogo } from 'merchant/views/PaymentPages/PaymentPages/model';
import {
  MAX_SOCIAL_HANDLE_ALLOWED,
  PLATFORM_NAMES,
  SOCIAL_HANDLES,
} from 'merchant/views/PaymentPages/PaymentPages/constants';
import { SocialHandle, SocialHandleDrawerProps } from '../types';
import SocialHandleDrawerView from './SocialHandleDrawerView';
import { toTitleCase } from '@libs/shared-utils';
import { validateHandle } from '../utils';
import track from 'merchant/views/PaymentPages/PaymentPages/List/track';

/**
 * Calculates the next position value for a new social media handle.
 *
 * @param socialHandles - Array of social media handle objects
 * @returns The next available position value (maximum existing position + 1)
 *
 * Logic:
 * - If the array is empty, returns 0 as the starting position
 * - For non-empty arrays, finds the maximum position across all handles
 * - Uses nullish coalescing (?? 0) to treat undefined or null positions as 0
 * - Returns the maximum position + 1 for the next handle
 */
const calculateNextPosition = (socialHandles: SocialMediaHandles): number => {
  if (socialHandles.length === 0) return 0;
  return Math.max(...socialHandles.map((handle) => handle?.position ?? 0)) + 1;
};

const SocialHandleDrawerContainer: React.FC<SocialHandleDrawerProps> = ({
  handleClose,
  addSocialHandle,
  storefront,
  updateSocialHandle,
}) => {
  const [showSelectModal, setShowSelectModal] = useState<boolean>(false);
  const [selectedHandle, setSelectedHandle] = useState<SocialHandle | null>(null);
  const [isEditing, setIsEditing] = useState<boolean>(false);
  const [isSaving, setIsSaving] = useState<boolean>(false);

  const socialHandles = storefront?.entity?.social_handles || [];
  const hasReachedHandleLimit = socialHandles.length >= MAX_SOCIAL_HANDLE_ALLOWED;

  const uploadCustomLogo = async (uploadedFile) => {
    if (!uploadedFile) return null;

    try {
      const logoRes = await uploadSocialHandleLogo(uploadedFile);
      if (logoRes && logoRes.success) {
        const logoUrl = logoRes.data[0];
        showNotification({
          type: 'success',
          message: 'Logo has been successfully uploaded.',
        });
        return logoUrl;
      }
    } catch (error) {
      showNotification({
        type: 'error',
        message: 'Network error while uploading image',
      });
      return null;
    }
  };

  const saveSocialHandle = useCallback(
    async (inputVal, uploadedFile) => {
      try {
        setIsSaving(true);
        if (!selectedHandle) return;
        const platform = selectedHandle.name;

        const isValid = validateHandle(platform, inputVal);
        if (!isValid) {
          showNotification({
            type: 'error',
            message: `The provided ${platform} handle is not valid. Please follow the correct format.`,
          });
          return;
        }

        const logo_url =
          platform === PLATFORM_NAMES.CUSTOM
            ? await uploadCustomLogo(uploadedFile)
            : selectedHandle.src;
        const payload: SocialMediaHandle = { platform, profile_url: inputVal, logo_url };
        const isUpdating =
          isEditing || socialHandles.some((profile) => profile.platform === platform);

        if (isUpdating) {
          updateSocialHandle(payload);
          showNotification({
            type: 'success',
            message: `${toTitleCase(platform)} handle has been updated to your storefront`,
          });
        } else {
          const position = calculateNextPosition(socialHandles);
          addSocialHandle({ ...payload, position });
          showNotification({
            type: 'success',
            message: `${toTitleCase(platform)} handle has been added to your storefront`,
          });
        }

        setSelectedHandle(null);
        setShowSelectModal(false);
        setIsEditing(false);
        track.confirmSocialLinkClicked({
          storefrontId: storefront?.id,
          isNewStorefront: Boolean(!storefront?.id),
          socialHandle: {
            name: selectedHandle?.name,
            link: inputVal,
          },
        });
      } catch (error) {
        showNotification({
          type: 'error',
          message: 'An error occurred while saving your social media handle. Please try again.',
        });
      } finally {
        setIsSaving(false);
      }
    },
    [selectedHandle, isEditing, socialHandles, updateSocialHandle, addSocialHandle],
  );

  const cancelSocialHandleOperation = useCallback(() => {
    if (isEditing) {
      setSelectedHandle(null);
      setShowSelectModal(false);
      setIsEditing(false);
    } else {
      selectedHandle ? setSelectedHandle(null) : setShowSelectModal(false);
    }
  }, [isEditing, selectedHandle]);

  const editSocialHandle = (platform: string) => {
    setIsEditing(true);
    const handle = SOCIAL_HANDLES.find((handle) => handle.name === platform);
    if (handle) {
      setShowSelectModal(true);
      setSelectedHandle(handle);
    }
    track.editSocialLinkClicked(platform, {
      storefrontId: storefront?.id,
      isNewStorefront: Boolean(!storefront?.id),
    });
  };

  const openSocialHandleSelector = useCallback(() => {
    if (hasReachedHandleLimit) {
      showNotification({
        type: 'error',
        message: "You've reached the handles limit. Delete existing handles to add new ones.",
      });
      return;
    }
    setShowSelectModal(true);
    track.selectSocialHandleArrowClicked({
      storefrontId: storefront?.id,
      isNewStoreFront: Boolean(!storefront?.id),
    });
  }, [hasReachedHandleLimit]);

  return (
    <SocialHandleDrawerView
      handleClose={handleClose}
      showSelectModal={showSelectModal}
      selectedHandle={selectedHandle}
      setSelectedHandle={setSelectedHandle}
      saveSocialHandle={saveSocialHandle}
      cancelSocialHandleOperation={cancelSocialHandleOperation}
      editSocialHandle={editSocialHandle}
      openSocialHandleSelector={openSocialHandleSelector}
      isSaving={isSaving}
    />
  );
};

const mapStateToProps = (state: any) => ({
  storefront: state.paymentPageStorefront,
  isMobile: state.app.isMobileResolution,
});

const mapDispatchToProps = (dispatch: any) => ({
  addSocialHandle: bindActionCreators(addSocialHandle, dispatch),
  updateSocialHandle: bindActionCreators(updatedSocialHandle, dispatch),
});

export default connect(mapStateToProps, mapDispatchToProps)(SocialHandleDrawerContainer);
