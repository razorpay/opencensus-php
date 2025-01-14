import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import {
  ArrowRightIcon,
  CloseIcon,
  Text,
  Button,
  Box,
  IconButton,
  BottomSheet,
  BottomSheetHeader,
  BottomSheetBody,
  Modal,
  ModalHeader,
  ModalFooter,
  BottomSheetFooter,
  Switch,
} from '@razorpay/blade/components';
import PaymentPagesDrawer from 'merchant/views/PaymentPages/common/Drawer';
import track from 'merchant/views/PaymentPages/PaymentPages/List/track';

import LineItems from './LineItems';

import { showNotification } from 'merchant_common/reducers/notifications';
import { toBase64 } from 'merchant/views/Capital/utils';
import { uploadImageInDescription as uploadStorefrontImage } from 'merchant/views/PaymentPages/PaymentPages/model';
import {
  editStorefront,
  PaymentPagesStorefrontType,
} from 'merchant/reducers/paymentPages/storefront';
import lazy from 'merchant/routes/LazyLoader';
import { IBannerImage } from 'merchant/reducers/paymentPages/types';

const BannerList = lazy(
  () => import(/* webpackChunkName: 'StorefrontV1BannerList' */ './BannerList'),
);

const ImageCropper = lazy(
  () => import(/* webpackChunkName: 'StorefrontV1ImageCropper' */ './ImageCropper'),
);

const FILE_SIZE_LIMIT_MB = 2;

interface IUploadBannerDrawer {
  handleClose: () => void;
  banner_images: Array<IBannerImage>;
  editStorefront: (name: string, value: unknown) => void;
  isMobile: boolean;
  storefront: PaymentPagesStorefrontType;
}

const getNextPosition = (banners: IBannerImage[]) => {
  if (banners.length === 0) return 1;
  const maxPosition = Math.max(...banners.map((banner) => banner.position));
  return maxPosition + 1;
};

const validateFile = (file: File | null): string | null => {
  if (!file) return 'No file selected.';
  if (file.size / 1024 / 1024 > FILE_SIZE_LIMIT_MB) {
    return `File size exceeds ${FILE_SIZE_LIMIT_MB}MB limit.`;
  }
  if (!/^image\//.test(file.type)) {
    return 'Invalid file format. Only PNG, JPEG, and JPG are allowed.';
  }
  return null;
};

const convertToBase64 = async (url: string): Promise<string> => {
  try {
    const res = await fetch(url);
    if (!res.ok) {
      throw new Error(`HTTP error! status: ${res.status}`);
    }
    const blob = await res.blob();
    return await new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onloadend = () => {
        if (typeof reader.result === 'string') {
          resolve(reader.result);
        } else {
          reject(new Error('Failed to convert blob to base64: Invalid result type'));
        }
      };
      reader.onerror = () => reject(new Error('Failed to convert blob to base64'));
      reader.readAsDataURL(blob);
    });
  } catch (err) {
    console.error('Error fetching or converting image:', err);
    throw err;
  }
};

const RenderBannerSection = ({
  storefront,
  handleBannerSwitchChange,
  onImageUpload,
  bannerData,
  handleReorder,
  handleToggle,
  handleDeleteImage,
  handleReplaceImage,
  isMobile,
  handleEditImage,
}) => {
  return (
    <Box height="500px">
      {storefront.entity.settings?.base_config?.banner_feature_enabled && (
        <Box
          display="flex"
          justifyContent="space-between"
          alignItems="center"
          marginBottom="spacing.6"
          paddingTop="spacing.4"
        >
          <Text weight="regular" color="surface.text.gray.subtle" variant="body" size="medium">
            Turn on banner preview
          </Text>

          <Switch
            accessibilityLabel="storefront-banner-switch"
            isChecked={storefront.entity.settings?.base_config?.banner_feature_enabled}
            onChange={({ isChecked }) => {
              handleBannerSwitchChange(isChecked);
              track.bannerPreviewCheckboxClicked({
                storefrontId: storefront?.id,
                isNewStoreFront: Boolean(!storefront?.id),
                isChecked,
              });
            }}
          />
        </Box>
      )}
      <LineItems
        title="Upload banner images"
        subTitle={
          <Text size="small" color="surface.text.gray.subtle">
            Click to add up to 3 images
          </Text>
        }
        rightChildren={
          <Button
            variant="tertiary"
            color="primary"
            size="xsmall"
            icon={ArrowRightIcon}
            onClick={onImageUpload}
          />
        }
      />
      <Text size="small" color="surface.text.gray.subtle" marginTop="spacing.3">
        Recommended size: 1024x400 | Format: PNG, JPEG, JPG
      </Text>
      {bannerData.length > 0 && (
        <BannerList
          bannerData={bannerData}
          onReorder={handleReorder}
          onToggleEnabled={handleToggle}
          handleDeleteImage={handleDeleteImage}
          handleReplaceImage={handleReplaceImage}
          isMobile={isMobile}
          handleEditImage={handleEditImage}
        />
      )}
    </Box>
  );
};

const UploadBannerDrawer: React.FC<IUploadBannerDrawer> = ({
  handleClose,
  banner_images,
  editStorefront,
  isMobile,
  storefront,
}) => {
  const [imageSrc, setImageSrc] = useState<string | null>(null);
  const [imageFile, setImageFile] = useState<File | null>(null);
  const [croppedImageFile, setCroppedImageFile] = useState<File | null>(null);
  const [bannerData, setBannerData] = useState<Array<IBannerImage>>(banner_images || []);
  const [selectedBanner, setSelectedBanner] = useState<IBannerImage | null>(null);
  const [openDeleteModal, setOpenDeleteModal] = useState<boolean>(false);

  const onImageUpload = () => {
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/png, image/jpeg, image/jpg');
    input.click();

    input.onchange = () => {
      const file = input.files && input.files[0];
      const validationError = validateFile(file);
      if (validationError) {
        showNotification({ type: 'error', message: validationError });
        return;
      }
      setImageFile(file);
      toBase64(file).then((base64) => {
        setImageSrc(base64 as string);
      });
      track.uploadBannerClicked({
        storefrontId: storefront?.id,
        isNewStoreFront: Boolean(!storefront?.id),
      });
    };
  };

  const handleCropComplete = (croppedFile: File) => {
    setCroppedImageFile(croppedFile);
  };

  const handleBannerChange = (newBannerData: IBannerImage[]) => {
    setBannerData(newBannerData);
    editStorefront('banner_images', newBannerData);
    setSelectedBanner(null);
  };

  const handleSubmit = async () => {
    if (!croppedImageFile) return;
    const validationError = validateFile(croppedImageFile);
    if (validationError) {
      showNotification({ type: 'error', message: validationError });
      return;
    }

    showNotification({
      type: 'success',
      message: 'Uploading image...',
      closeTimeout: 2500,
    });

    if (imageFile) {
      uploadStorefrontImage(imageFile)
        .then((res) => {
          if (res && res.success) {
            const originalUrl = res.data[0];
            return uploadStorefrontImage(croppedImageFile).then((croppedRes) => {
              if (croppedRes && croppedRes.success) {
                const croppedUrl = croppedRes.data[0];
                const updatedBannerData = bannerData.map((banner) => {
                  if (selectedBanner && banner.cropped === selectedBanner.cropped) {
                    return {
                      ...banner,
                      original: originalUrl,
                      cropped: croppedUrl,
                    };
                  }
                  return banner;
                });
                if (!selectedBanner) {
                  const position = getNextPosition(bannerData);
                  updatedBannerData.push({
                    original: originalUrl,
                    cropped: croppedUrl,
                    position,
                    enabled: true,
                  });
                }
                handleBannerChange(updatedBannerData);
              } else {
                const errorMessage = 'Some network error occurred while uploading cropped image';
                showNotification({
                  type: 'error',
                  message: errorMessage,
                });
              }
            });
          } else {
            const errorMessage = 'Some network error occurred while uploading original image';
            showNotification({
              type: 'error',
              message: errorMessage,
            });
          }
        })
        .catch(({ errors }) => {
          showNotification({
            type: 'error',
            message: errors[0],
          });
        })
        .finally(() => {
          setImageSrc(null);
        });
    } else {
      uploadStorefrontImage(croppedImageFile)
        .then((croppedRes) => {
          if (croppedRes && croppedRes.success) {
            const cropped = croppedRes.data[0];
            const updatedBannerData = bannerData.map((banner) => {
              if (selectedBanner && banner.cropped === selectedBanner.cropped) {
                return {
                  ...banner,
                  cropped: cropped,
                };
              }
              return banner;
            });
            handleBannerChange(updatedBannerData);
          } else {
            throw new Error('Error uploading cropped image');
          }
        })
        .catch(({ errors }) => {
          showNotification({
            type: 'error',
            message: errors[0] || 'Network error while uploading image',
          });
        })
        .finally(() => {
          setImageSrc(null);
        });
    }
  };

  const handleReorder = (banner: IBannerImage[]) => {
    setBannerData(banner);
    editStorefront('banner_images', banner);
  };

  const handleToggle = (val: boolean, banner: IBannerImage) => {
    const updatedBannerData = bannerData.filter((bannerD) => bannerD.cropped !== banner.cropped);
    const newBannerData = [...updatedBannerData, { ...banner, enabled: val }];
    setBannerData(newBannerData);
    editStorefront('banner_images', newBannerData);
  };

  const handleReplaceImage = (banner: IBannerImage) => {
    track.handleBannerReplace({
      storefrontId: storefront?.id,
      isNewStoreFront: Boolean(!storefront?.id),
    });
    setSelectedBanner(banner);
    onImageUpload();
  };

  const handleDeleteImage = (banner: IBannerImage) => {
    track.handleBannerDelete({
      storefrontId: storefront?.id,
      isNewStoreFront: Boolean(!storefront?.id),
    });
    setSelectedBanner(banner);
    setOpenDeleteModal(true);
  };

  const handleConfirmDelete = () => {
    if (selectedBanner) {
      const updatedBannerData = bannerData.filter(
        (bannerD) => bannerD.cropped !== selectedBanner.cropped,
      );
      handleBannerChange(updatedBannerData);
    }
    setSelectedBanner(null);
    setOpenDeleteModal(false);
  };

  const handleConfirmCancel = () => {
    setSelectedBanner(null);
    setOpenDeleteModal(false);
  };

  const handleBannerSwitchChange = (isChecked: boolean) => {
    editStorefront('settings', {
      ...storefront.entity.settings,
      base_config: {
        ...storefront.entity.settings.base_config,
        banner_feature_enabled: isChecked,
      },
    });
    handleClose();
  };

  const handleEditImage = (banner: IBannerImage) => {
    track.handleBannerEdit({
      storefrontId: storefront?.id,
      isNewStoreFront: Boolean(!storefront?.id),
    });
    setSelectedBanner(banner);
    convertToBase64(banner.original)
      .then((base64) => {
        setImageSrc(base64);
        setImageFile(null);
      })
      .catch((err) => {
        console.error('Error in converting base 64:', err);
      });
  };

  return (
    <>
      {isMobile ? (
        <BottomSheet
          isOpen={true}
          onDismiss={handleClose}
          zIndex={10000}
          snapPoints={[0.9, 0.9, 0.9]}
        >
          <BottomSheetHeader title="Add store banner" />
          <BottomSheetBody>
            <RenderBannerSection
              storefront={storefront}
              handleBannerSwitchChange={handleBannerSwitchChange}
              onImageUpload={onImageUpload}
              bannerData={bannerData}
              handleReorder={handleReorder}
              handleToggle={handleToggle}
              handleDeleteImage={handleDeleteImage}
              handleReplaceImage={handleReplaceImage}
              isMobile={isMobile}
              handleEditImage={handleEditImage}
            />
          </BottomSheetBody>
        </BottomSheet>
      ) : (
        <PaymentPagesDrawer
          showCloseBtn={false}
          maskClosable={false}
          onClose={handleClose}
          top="0px"
          isStorefront={true}
        >
          <Box display={'flex'} justifyContent={'space-between'} alignItems={'flex-start'}>
            <Text
              color="surface.text.gray.normal"
              marginBottom="spacing.7"
              size="large"
              variant="body"
              weight="semibold"
            >
              Add store banner
            </Text>
            <IconButton
              onClick={handleClose}
              accessibilityLabel="close-icon"
              size="large"
              icon={CloseIcon}
            />
          </Box>

          <RenderBannerSection
            storefront={storefront}
            handleBannerSwitchChange={handleBannerSwitchChange}
            onImageUpload={onImageUpload}
            bannerData={bannerData}
            handleReorder={handleReorder}
            handleToggle={handleToggle}
            handleDeleteImage={handleDeleteImage}
            handleReplaceImage={handleReplaceImage}
            isMobile={isMobile}
            handleEditImage={handleEditImage}
          />
        </PaymentPagesDrawer>
      )}
      {imageSrc && (
        <ImageCropper
          imageSrc={imageSrc}
          onCropComplete={handleCropComplete}
          onCancel={() => {
            setImageSrc(null);
            setSelectedBanner(null);
          }}
          onApply={handleSubmit}
          isMobile={isMobile}
        />
      )}
      {openDeleteModal &&
        (isMobile ? (
          <BottomSheet
            isOpen={true}
            onDismiss={handleConfirmCancel}
            zIndex={10000}
            snapPoints={[0.9, 0.9, 0.9]}
          >
            <BottomSheetHeader
              title="Confirm Deletion of Image"
              subtitle="Please confirm if you would like to proceed with deleting the image."
            />
            <BottomSheetFooter>
              <Box
                display="flex"
                gap="spacing.3"
                justifyContent="flex-end"
                width="100%"
                flexDirection="column"
              >
                <Button variant="tertiary" onClick={handleConfirmCancel}>
                  Cancel
                </Button>
                <Button onClick={handleConfirmDelete}>Confirm</Button>
              </Box>
            </BottomSheetFooter>
          </BottomSheet>
        ) : (
          <Modal isOpen={true} onDismiss={handleConfirmCancel} size="small">
            <ModalHeader
              title="You want to remove banner image?"
              subtitle="Your uploaded banner image will be deleted."
            />
            <ModalFooter>
              <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
                <Button variant="tertiary" onClick={handleConfirmCancel}>
                  Cancel
                </Button>
                <Button onClick={handleConfirmDelete}>Confirm</Button>
              </Box>
            </ModalFooter>
          </Modal>
        ))}
    </>
  );
};

const mapStateToProps = (state: any) => ({
  banner_images: state.paymentPageStorefront.entity.banner_images,
  isMobile: state.app.isMobileResolution,
});

const mapDispatchToProps = (dispatch: any) => ({
  editStorefront: bindActionCreators(editStorefront, dispatch),
});

export default connect(mapStateToProps, mapDispatchToProps)(UploadBannerDrawer);
