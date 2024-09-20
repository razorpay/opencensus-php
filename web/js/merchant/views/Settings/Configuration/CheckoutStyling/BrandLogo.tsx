import React, { useEffect } from 'react';
import { Box, Text, Button, FolderIcon } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { AnyAction, Dispatch, bindActionCreators } from 'redux';

import { User } from 'common/typings';
import FileUploadButton from 'common/ui/FileUpload/Button';
import FileUpload from 'merchant/components/File/Upload';
import { toBase64 } from 'merchant/views/PartnerDashboard/SubMerchant/components/utils';
import ImageCropperModal from 'merchant/views/Settings/Configuration/ImageCropperModal';
import {
  THUMBNAIL_SIZE_LIMIT,
  FILE_TYPES,
  UPLOAD_IMAGE_HERE,
  BRAND_LOGO_SIZE_LIMIT,
} from 'merchant/views/Settings/Configuration/constants';
import { showNotification } from 'merchant_common/reducers/notifications';

import ImagePreview from './ImagePreview';
import { useCheckoutConfig } from './context';

type BrandLogoProps = {
  user: User & {
    isCustomMerchantUPIQR?: boolean;
  };
  showNotification: typeof showNotification;
};

const BrandLogo = ({ user, showNotification }: BrandLogoProps): JSX.Element => {
  const { values, handleLogoChange, handleRectLogoChange } = useCheckoutConfig();

  const [rectLogo, setRectLogo] = React.useState<{
    name: string | null;
    file: string | ArrayBuffer | null | undefined;
  } | null>(null);

  const handleFileChange = (evt: React.ChangeEvent) => {
    const { files } = evt.target as HTMLInputElement;

    if (files?.length) {
      handleLogoChange(files[0]);
    }
  };

  const handleFileRemove = () => {
    handleLogoChange(null);
  };

  const handleRectFileChange = (file: File | null) => {
    handleRectLogoChange(file);
  };

  const handleRemoveRectLogo = () => {
    handleRectLogoChange(null);
  };

  const handleBiggerFileSize = () => {
    showNotification({
      type: 'error',
      message: `Image too large. Max limit ${THUMBNAIL_SIZE_LIMIT / (1024 * 1024)}MB`,
    });
  };

  useEffect(() => {
    if (user.isCustomMerchantUPIQR) {
      if (values.logoRectRaw) {
        toBase64(values.logoRectRaw).then((base64) => {
          setRectLogo({
            name: values.logoRectRaw?.name ?? '',
            file: base64,
          });
        });
      } else {
        setRectLogo(null);
      }
    }
  }, [values.logoRectRaw, user.isCustomMerchantUPIQR]);

  return (
    <Box>
      <Box display="flex" flexDirection="column" gap="spacing.3">
        <Text weight="semibold" color="surface.text.gray.subtle">
          Your Logo
        </Text>
        <Box display="flex" gap="spacing.5">
          <ImagePreview src={values.logo} file={values.logoRaw} />

          <Box>
            <FileUploadButton
              text={values.logo ? 'Change Logo' : 'Choose File'}
              labelClass="btn-primary"
              accept="image/jpeg,image/jpg,image/png"
              maxSize={BRAND_LOGO_SIZE_LIMIT}
              onChange={handleFileChange}
            />
            {values.logo && (
              <span className="remove-logo">
                <Button variant="tertiary" type="button" onClick={handleFileRemove}>
                  Remove
                </Button>
              </span>
            )}
            <Text
              color="surface.text.gray.muted"
              variant="caption"
              size="small"
              marginTop="spacing.2"
            >
              Max file size: 1MB
            </Text>
          </Box>
        </Box>
        <Text color="surface.text.gray.muted">
          Choose a square image of minimum dimensions 256x256 px.
        </Text>
      </Box>

      {user.isCustomMerchantUPIQR && (
        <Box marginY="spacing.8">
          <Box marginBottom="10px">
            <Text weight="semibold" color="surface.text.gray.subtle">
              Rectangular Logo
            </Text>
          </Box>
          <Box
            display="flex"
            flexDirection={{
              base: 'column',
              m: 'row',
            }}
            marginBottom="10px"
          >
            <Box flex="1">
              <Box>
                <FileUpload
                  accept={FILE_TYPES}
                  size="large"
                  uploadedFileName={UPLOAD_IMAGE_HERE}
                  maxSize={THUMBNAIL_SIZE_LIMIT}
                  onBiggerFileSize={handleBiggerFileSize}
                  onFileChange={handleRectFileChange}
                  onCloseClick={handleRemoveRectLogo}
                  defaultValue={values.logoRect}
                  files={[]}
                  imgFilePreviewUrl={values.logoRect}
                  showFileSize={true}
                  name="rect-logo-file-upload"
                >
                  <div className="Dropzone-80g-details">
                    <Button icon={FolderIcon}>Choose File</Button>
                  </div>
                </FileUpload>
              </Box>
            </Box>
            <Box flex="1" marginTop="5px">
              {values.logoRect && <Button onClick={handleRemoveRectLogo}>Remove</Button>}
            </Box>
          </Box>

          <Box marginBottom="10px">
            <Text marginBottom="5px" color="surface.text.gray.muted">
              Choose a rectangular image of minimum height 60px.
            </Text>
            <Text color="surface.text.gray.muted">Upload .png, .jpg or .jpeg file | 1 MB Max</Text>
          </Box>
          {rectLogo && (
            <ImageCropperModal rectangularImageFile={rectLogo} closeModal={handleRemoveRectLogo} />
          )}
        </Box>
      )}
    </Box>
  );
};

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) => {
  return bindActionCreators(
    {
      showNotification,
    },
    dispatch,
  );
};

export default connect(null, mapDispatchToProps)(BrandLogo);
