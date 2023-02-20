import React from 'react';
import { ArrowRightIcon, Button, CloseIcon, EyeIcon } from '@razorpay/blade/components';
import { FooterWrapper } from './styled';

interface IMobileActionButtons {
  onPublish: () => void;
  isPreview: boolean;
  setPreview: (val: boolean) => void;
}

export default function MobileActionButtons({
  onPublish,
  isPreview,
  setPreview,
}: IMobileActionButtons): React.ReactElement {
  return (
    <FooterWrapper>
      {!isPreview ? (
        <>
          <Button
            variant="secondary"
            icon={EyeIcon}
            iconPosition="left"
            isFullWidth
            onClick={setPreview.bind(null, true)}
          >
            Preview store
          </Button>
          <Button
            variant="primary"
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
          onClick={setPreview.bind(null, false)}
        >
          Close preview
        </Button>
      )}
    </FooterWrapper>
  );
}
