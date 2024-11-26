import React from 'react';
import { ArrowRightIcon, Button, CloseIcon, EyeIcon } from '@razorpay/blade/components';
import { FooterWrapper } from './styled';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

interface IMobileActionButtons {
  onPublish: () => void;
  isPreview: boolean;
  setPreview: (val: boolean) => void;
  isCreate: boolean | undefined;
  storefrontId: string | undefined;
}

function MobileActionButtons({
  isCreate,
  storefrontId,
  onPublish,
  isPreview,
  setPreview,
}: IMobileActionButtons): React.ReactElement {
  const onPreviewStoreClick = () => {
    setPreview(true);
    analyticsTrack({
      objectName: 'Preview store',
      actionName: 'Clicked',
      screen: 'Create storefront page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        storefrontId: storefrontId ?? undefined,
        isNewStoreFront: Boolean(isCreate),
      },
    });
  };

  const onClosePreviewClick = () => {
    setPreview(false);
    analyticsTrack({
      objectName: 'Close preview',
      actionName: 'Clicked',
      screen: 'Create storefront page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        storefrontId: storefrontId ?? undefined,
        isNewStoreFront: Boolean(isCreate),
      },
    });
  };

  return (
    <FooterWrapper>
      {!isPreview ? (
        <>
          <Button
            variant="secondary"
            size="small"
            icon={EyeIcon}
            iconPosition="left"
            isFullWidth
            onClick={onPreviewStoreClick}
          >
            Preview store
          </Button>
          <Button
            variant="primary"
            size="small"
            icon={ArrowRightIcon}
            iconPosition="right"
            onClick={onPublish}
            isFullWidth
          >
            Publish page
          </Button>
        </>
      ) : (
        <Button
          variant="primary"
          icon={CloseIcon}
          iconPosition="left"
          isFullWidth
          onClick={onClosePreviewClick}
        >
          Close preview
        </Button>
      )}
    </FooterWrapper>
  );
}

export default MobileActionButtons;
