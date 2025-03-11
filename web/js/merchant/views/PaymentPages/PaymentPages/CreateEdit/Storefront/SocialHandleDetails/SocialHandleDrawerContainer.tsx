import React, { useState, useCallback, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import {
  addSocialHandle,
  deleteSocialHandle,
  reorderSocialHandle,
  updatedSocialHandle,
} from 'merchant/reducers/paymentPages/storefront';
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
  isMobile,
  addSocialHandle,
  storefront,
  reorderSocialHandle,
  updateSocialHandle,
  deleteSocialHandle,
}) => {
  const [showSelectModal, setShowSelectModal] = useState<boolean>(false);
  const [inputVal, setInputVal] = useState<string>('');
  const [selectedHandle, setSelectedHandle] = useState<SocialHandle | null>(null);
  const [isEditing, setIsEditing] = useState<boolean>(false);
  const [openDeleteModal, setOpenDeleteModal] = useState<boolean>(false);
  const [selectedPlatform, setSelectedPlatform] = useState<string>('');
  const [isSaving, setIsSaving] = useState<boolean>(false);
  const [uploadedFile, setUploadedFile] = useState<File | null>(null);
  const [uploadedLogo, setUploadedLogo] = useState<string>('');

  const socialHandles = storefront?.entity?.social_handles || [];
  const hasReachedHandleLimit = socialHandles.length >= MAX_SOCIAL_HANDLE_ALLOWED;

  useEffect(() => {
    if (!selectedHandle) return;

    const profile = socialHandles.find((profile) => profile.platform === selectedHandle.name);
    setInputVal(profile?.profile_url ?? '');
    if (profile?.logo_url && profile?.platform === PLATFORM_NAMES.CUSTOM) {
      setUploadedLogo(profile.logo_url);
    }
  }, [selectedHandle, socialHandles]);

  const updateInputValue = (e: any) => {
    setInputVal(e.value);
  };

  const selectSocialHandle = (handle: SocialHandle) => {
    setSelectedHandle(handle);
  };

  const uploadCustomLogo = async () => {
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

  const saveSocialHandle = useCallback(async () => {
    try {
      setIsSaving(true);
      if (!selectedHandle) return;

      const platform = selectedHandle.name;
      const logo_url = platform === PLATFORM_NAMES.CUSTOM ? await uploadCustomLogo() : selectedHandle.src;
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
    } catch (error) {
      showNotification({
        type: 'error',
        message: 'An error occurred while saving your social media handle. Please try again.',
      });
    } finally {
      setIsSaving(false);
    }
  }, [selectedHandle, inputVal, isEditing, socialHandles, updateSocialHandle, addSocialHandle]);

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
  };

  const showDeleteConfirmation = (platform: string) => {
    setSelectedPlatform(platform);
    setOpenDeleteModal(true);
  };

  const confirmDeleteSocialHandle = () => {
    setOpenDeleteModal(false);
    deleteSocialHandle({ platform: selectedPlatform });
  };

  const cancelDeleteSocialHandle = () => {
    setOpenDeleteModal(false);
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
  }, [hasReachedHandleLimit]);

  return (
    <SocialHandleDrawerView
      isMobile={isMobile}
      handleClose={handleClose}
      showSelectModal={showSelectModal}
      inputVal={inputVal}
      selectedHandle={selectedHandle}
      isEditing={isEditing}
      openDeleteModal={openDeleteModal}
      selectedPlatform={selectedPlatform}
      socialHandles={socialHandles}
      hasReachedHandleLimit={hasReachedHandleLimit}
      updateInputValue={updateInputValue}
      setSelectedHandle={setSelectedHandle}
      saveSocialHandle={saveSocialHandle}
      cancelSocialHandleOperation={cancelSocialHandleOperation}
      editSocialHandle={editSocialHandle}
      showDeleteConfirmation={showDeleteConfirmation}
      confirmDeleteSocialHandle={confirmDeleteSocialHandle}
      cancelDeleteSocialHandle={cancelDeleteSocialHandle}
      openSocialHandleSelector={openSocialHandleSelector}
      reorderSocialHandle={reorderSocialHandle}
      selectSocialHandle={selectSocialHandle}
      uploadedFile={uploadedFile}
      setUploadedFile={setUploadedFile}
      isSaving={isSaving}
      uploadedLogo={uploadedLogo}
      setUploadedLogo={setUploadedLogo}
    />
  );
};

const mapStateToProps = (state: any) => ({
  storefront: state.paymentPageStorefront,
  isMobile: state.app.isMobileResolution,
});

const mapDispatchToProps = (dispatch: any) => ({
  addSocialHandle: bindActionCreators(addSocialHandle, dispatch),
  reorderSocialHandle: bindActionCreators(reorderSocialHandle, dispatch),
  updateSocialHandle: bindActionCreators(updatedSocialHandle, dispatch),
  deleteSocialHandle: bindActionCreators(deleteSocialHandle, dispatch),
});

export default connect(mapStateToProps, mapDispatchToProps)(SocialHandleDrawerContainer);