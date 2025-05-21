import React, { useState, useCallback, useEffect, useRef } from 'react';
import { connect } from 'react-redux';
import TextHighlighter from 'common/ui/TextHighlighter';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  Box,
  CheckCircleIcon,
  IconButton,
  Link,
  Text,
  TrashIcon,
} from '@razorpay/blade/components';

import { PAYMENT_RECEIPT_CUSTOMIZATION } from './deeplink-constants';
import { merchantFetch } from '@dashboards/payments/utils/merchantFetch';

import uploadedSvg from 'assets/checkout-editor/title-style/uploaded-image.svg';
import { ColorTextInput } from './CheckoutConfig/ColorTextInput';
import { 
  PAYMENT_RECEIPT_CUSTOM_TEMPLATE, 
  RECEIPT_CUSTOMIZATION_ERRORS, 
  RECEIPT_CUSTOMIZATION_SUCCESS, 
  BRAND_LOGO_SIZE_LIMIT, 
  SUCCESS, 
  ERROR,
  RECEIPT_PREVIEW_INFO,
  BRAND_COLOR_INFO,
  BRAND_LOGO_RATIO_INFO
} from './constants';

export const PaymentReceiptCustomizer = ({ currentUser, showNotification }) => {
  const [brandColor, setBrandColor] = useState(currentUser.merchant.brand_color || '#3395FF');
  const [logo, setLogo] = useState<string | null>(null);
  const [logoUrl, setLogoUrl] = useState('');
  const [fileName, setFileName] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isLoading, setIsLoading] = useState(true);

  const brandLogo = logo || logoUrl || currentUser.merchant.logo_url;

  useEffect(() => {
    let isMounted = true;
    setIsLoading(true);

    merchantFetch({
      url: 'mes/rzp.merchant_experience_service.customizable_receipt.v1.CustomizableReceiptAPI/GetReceiptConfig',
      method: 'post',
      data: {
        merchant_id: currentUser.id,
      },
    })
      .then(response => response?.data)
      .then(data => {
        if (!isMounted) {
          return;
        }
        const { success, brand_color, logo_url } = data || {};
        if (success) {
          if (brand_color) {
            setBrandColor(brand_color);
          }

          if (logo_url) {
            setLogoUrl(logo_url);
          }
        } else {
          showNotification({
            type: ERROR,
            message: RECEIPT_CUSTOMIZATION_ERRORS.LOAD_ERROR,
          });
        }
      })
      .catch(() => {
        if (!isMounted) {
          return;
        }
        showNotification({
          type: ERROR,
          message: RECEIPT_CUSTOMIZATION_ERRORS.LOAD_ERROR,
        });
      })
      .finally(() => {
        if (!isMounted) {
          return;
        }
        setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [currentUser.id, showNotification]);

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    
    const isImageType = /^image\//.test(file.type);
    if (!isImageType) {
      showNotification({
        type: ERROR,
        message: RECEIPT_CUSTOMIZATION_ERRORS.FILE_TYPE_ERROR,
      });
      if (e.target) e.target.value = '';
      return;
    }
    
    if (file.size > BRAND_LOGO_SIZE_LIMIT) {
      showNotification({
        type: ERROR,
        message: RECEIPT_CUSTOMIZATION_ERRORS.FILE_SIZE_ERROR,
      });
      if (e.target) e.target.value = '';
      return;
    }
    
    setFileName(file.name);
    
    const reader = new FileReader();
    reader.onerror = () => {
      showNotification({
        type: ERROR,
        message: RECEIPT_CUSTOMIZATION_ERRORS.FILE_READ_ERROR,
      });
      if (e.target) e.target.value = '';
    };
    
    reader.onloadend = () => {
      if (typeof reader.result === 'string') {
        setLogo(reader.result);
      }
    }; 
    reader.readAsDataURL(file);
  };

  const handleColorChange = (e) => {
    setBrandColor(e.target.value);
  };

  const onRemoveLogo = () => {
    setLogo(null);
    setFileName('');    
    const fileInput = document.getElementById('logo-upload') as HTMLInputElement;
    if (fileInput) {
      fileInput.value = '';
    }
  };

  const isLogoEmpty = !logo;

  const handleSubmit = useCallback(async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
  
    selfServeTrackInitiate({
      selfServeAction: 'Payment Receipt Customization Update',
      page: 'Config',
      screen: 'Settings',
    });
  
    try {
      const res = await merchantFetch({
        url: 'mes/rzp.merchant_experience_service.customizable_receipt.v1.CustomizableReceiptAPI/UpdateReceiptConfig',
        method: 'post',
        data: { 
          merchant_id: currentUser.id,
          brand_color: brandColor,
          logo: logo,
          file_name: fileName,
        },
      });
      
      if (res?.data?.success) {
        const responseLogoUrl = res.data.updated_fields?.logo_url;
        if (responseLogoUrl) {
          setLogoUrl(responseLogoUrl);
        }
        
        showNotification({
          type: SUCCESS,
          message: RECEIPT_CUSTOMIZATION_SUCCESS,
        });
        
        selfServeTrackSuccess({
          selfServeAction: 'Payment Receipt Customization Update',
          page: 'Config',
          screen: 'Settings',
        });
        
        analyticsTrack({
          objectName: 'payment receipt customization',
          actionName: 'updated',
          screen: 'settings',
          properties: {
            location: 'configuration',
            brandColor,
            hasLogo: !!logo,
            success: true,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      } else {
        showNotification({
          type: ERROR,
          message: RECEIPT_CUSTOMIZATION_ERRORS.SAVE_ERROR,
        });
        
        analyticsTrack({
          objectName: 'payment receipt customization',
          actionName: 'update_failed',
          screen: 'settings',
          properties: {
            location: 'configuration',
            brandColor,
            hasLogo: !!logo,
            success: false,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      }
    } catch (error) {
      console.error('Error updating receipt config:', error);
      showNotification({
        type: ERROR,
        message: RECEIPT_CUSTOMIZATION_ERRORS.SAVE_ERROR,
      });
    } finally {
      setIsSubmitting(false);
    }
  }, [brandColor, logo, fileName, currentUser.id, showNotification]);

  return (
    <div className="panel panel-default">
      <input 
        type="file"
        id="logo-upload"
        accept="image/*"
        onChange={handleFileChange}
        style={{ position: 'absolute', width: '1px', height: '1px', overflow: 'hidden', clip: 'rect(0,0,0,0)' }}
      />
      <div className="panel-heading">
        <span className="title">
          <TextHighlighter hashedWith={PAYMENT_RECEIPT_CUSTOMIZATION}>
            Payment Receipt Customization
          </TextHighlighter>
        </span>
      </div>
      
      <div className="panel-body">
        <div className="row">
          <div className="col-md-5">
            <div className="panel panel-default">
              <div className="panel-heading">
                <Text weight="semibold" size="large" color="surface.text.gray.normal">
                  Configure Settings
                </Text>
              </div>
              <div className="panel-body">
                <Box 
                  display="flex" 
                  flexDirection="column" 
                  gap="spacing.6" 
                  opacity={isLoading ? 0.5 : 1}
                  pointerEvents={isLoading ? "none" : "auto"}
                  position="relative"
                >
                  {isLoading && (
                    <Box
                      position="absolute"
                      top="50%"
                      left="50%"
                      transform="translate(-50%, -50%)"
                      zIndex={1}
                    >
                      <Text color="surface.text.gray.muted">
                        Loading settings...
                      </Text>
                    </Box>
                  )}
                  <Box display="flex">
                    <Box width="40%" paddingRight="spacing.3">
                      <Text
                        weight="semibold"
                        size="medium"
                        color="surface.text.gray.normal"
                      >
                        Brand Color
                      </Text>
                    </Box>

                    <ColorTextInput
                      name="brand_color"
                      value={brandColor}
                      helpText={
                        <>
                          {BRAND_COLOR_INFO}
                        </>
                      }
                      onChange={handleColorChange}
                      />
                  </Box>

                  <Box display="flex">
                    <Box width="33%" paddingRight="spacing.3">
                      <Text
                        weight="semibold"
                        size="medium"
                        color="surface.text.gray.normal"
                      >
                        Brand Logo
                      </Text>
                    </Box>
                    <Box width="67%">
                      {!isLogoEmpty ? (
                        <Box width="100%">
                          <Box 
                            display="flex" 
                            alignItems="center"
                            justifyContent="space-between"
                            gap="spacing.3" 
                            marginBottom="spacing.3"
                          >
                            <Text
                              weight="semibold"
                              size="medium"
                              color="surface.text.gray.muted"
                            >
                              Uploaded Image
                            </Text>
                            
                            <Box 
                              padding="spacing.2" 
                              border="1px solid" 
                              borderColor="surface.border.gray.subtle"
                              borderRadius="small"
                              width="48px" 
                              height="48px"
                              display="flex"
                              justifyContent="center"
                              alignItems="center"
                              overflow="hidden"
                            >
                              <img 
                                src={logo || ''}
                                alt="Logo Preview"
                                style={{
                                  maxWidth: '100%',
                                  maxHeight: '100%',
                                  objectFit: 'contain'
                                }}
                              />
                            </Box>
                          </Box>
                          
                          <Box
                            display="flex"
                            justifyContent="space-between"
                            alignItems="center"
                            padding="spacing.3"
                            borderRadius="small"
                            borderStyle="solid"
                            borderColor="surface.border.gray.subtle"
                            backgroundColor="surface.background.gray.moderate"
                            height="54px"
                          >
                            <Box display="flex" justifyContent="center" alignItems="center" gap="spacing.4">
                              <img src={uploadedSvg} alt="uploaded-svg" />
                              <Box display="flex" justifyContent="center" alignItems="center" gap="spacing.3">
                                <Box>
                                  <Text
                                    weight="medium"
                                    size="medium"
                                    color="surface.text.gray.subtle"
                                    wordBreak="break-word"
                                    truncateAfterLines={1}
                                  >
                                    {fileName}
                                  </Text>
                                </Box>
                                <CheckCircleIcon size="medium" color="interactive.icon.primary.normal" />
                              </Box>
                            </Box>
                            <IconButton
                              icon={() => <TrashIcon size="large" color="interactive.icon.gray.muted" />}
                              onClick={onRemoveLogo}
                              accessibilityLabel="delete-logo"
                            />
                          </Box>
                          <Text
                            variant="caption"
                            weight="medium"
                            size="small"
                            color="surface.text.gray.muted"
                            marginTop="spacing.3"
                          >
                            {BRAND_LOGO_RATIO_INFO}
                          </Text>
                        </Box>
                      ) : (
                        <Box width="100%">
                          <Box
                            display="flex"
                            alignItems="center"
                            padding={['spacing.3', 'spacing.5']}
                            height="64px"
                            borderRadius="small"
                            borderStyle="dashed"
                            borderColor="surface.border.gray.subtle"
                          >
                            <Box
                              display="flex"
                              justifyContent="center"
                              alignItems="center"
                              paddingLeft="spacing.4"
                              gap="spacing.3"
                            >
                              <label htmlFor="logo-upload">
                                <Link 
                                  variant="button"
                                  onClick={(e) => {
                                    e.preventDefault();
                                    document.getElementById('logo-upload')?.click();
                                  }}
                                >
                                  Upload
                                </Link>
                              </label>  
                            </Box>
                          </Box>
                          <Text
                            variant="caption"
                            weight="regular"
                            size="medium"
                            color="surface.text.gray.muted"
                            marginTop="spacing.3"
                          >
                            {BRAND_LOGO_RATIO_INFO}
                          </Text>
                        </Box>
                      )}
                    </Box>
                  </Box>

                  <Box 
                    display="flex" 
                    justifyContent="center" 
                    marginTop="spacing.6"
                    paddingTop="spacing.4"
                  >
                    <button
                      onClick={handleSubmit}
                      className="btn btn-primary"
                      disabled={isSubmitting}
                      type="button"
                    >
                      {isSubmitting ? 'Saving...' : 'Save Customization'}
                    </button>
                  </Box>
                </Box>
              </div>
            </div>
          </div>

          <div className="col-md-7">
            <div className="panel panel-default">
              <div className="panel-heading">
                <Text weight="semibold" size="large" color="surface.text.gray.normal">
                  Preview
                </Text>
              </div>
              <div className="panel-body">
                <iframe
                  srcDoc={
                    PAYMENT_RECEIPT_CUSTOM_TEMPLATE
                    .replace("{{brand_color}}", brandColor)
                    .replace("{{brand_logo}}", brandLogo)
                  }
                  width="100%"
                  height="500px"
                  style={{ border: '1px solid #eee' }}
                  title="Payment Receipt Preview"
                />
                <Box textAlign="center" marginTop="spacing.3">
                  <Text
                    variant="caption"
                    weight="regular"
                    size="small"
                    color="surface.text.gray.muted"
                  >
                    {RECEIPT_PREVIEW_INFO}
                  </Text>
                </Box>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

const mapStateToProps = (state) => ({
  currentUser: state.session.user,
});

export default connect(mapStateToProps, {
  showNotification,
})(PaymentReceiptCustomizer);