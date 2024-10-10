import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';

import { Modal, ModalContent, ModalMask } from 'common/new-ui/Modal';
import { Button, Text } from '@razorpay/blade/components';

import BrandNameTextInput from './BrandNameTextInput';
import UploadLogo from './UploadLogo';
import CheckoutV2 from 'merchant/views/Settings/Configuration/CheckoutDemo/CheckoutV2';

import {
  ContentWrapper,
  FooterWrapper,
  HeadingWrapper,
  IframeWrapper,
  LeftContentWrapper,
  RightContentWrapper,
} from './styled';

import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import { EditLogoTitleProps } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/titleType';

import {
  AVAILABLE_TITLE_STYLE,
  EDIT_LOGO_TITLE_HEADER_VALUE,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import {
  INDIVIDUAL,
  NOT_REGISTERED,
} from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';

const EditLogoTitle: React.FC<EditLogoTitleProps> = ({
  setShowEditModal,
  accountConfig,
  user,
  merchantCheckoutStyledConfig,
}) => {
  const {
    values,
    handleEditLogoModalDiscard,
    handleBrandNameChange,
    handleTitleStyleChange,
    handleSaveTitleModal,
  } = useCheckoutEditor();
  const brandName = values[CHECKOUT_EDITOR_FIELDS.BRAND_NAME];
  const [fileName, setFileName] = useState<string>('');
  const logo = values[CHECKOUT_EDITOR_FIELDS.LOGO];
  const logoRaw = values[CHECKOUT_EDITOR_FIELDS.LOGO_RAW];

  const shouldShowBrandNameTextInput =
    values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE] === AVAILABLE_TITLE_STYLE.TEXT_ONLY ||
    values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE] === AVAILABLE_TITLE_STYLE.LOGO_TEXT;

  const shouldShowBrandNameOption: boolean =
    shouldShowBrandNameTextInput &&
    user.activation_status == 'activated' &&
    user.business_type != INDIVIDUAL &&
    user.business_type != NOT_REGISTERED &&
    user.isAdminOrOwner;

  const shouldShowUploadLogoInput =
    values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE] === AVAILABLE_TITLE_STYLE.WORDMARK ||
    values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE] === AVAILABLE_TITLE_STYLE.LOGO_TEXT ||
    values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE] === AVAILABLE_TITLE_STYLE.LOGO_ONLY;

  const onSaveBrandNameAndLogo = () => {
    setShowEditModal(false);
    handleSaveTitleModal();
  };

  const onDiscard = () => {
    if (values.logoRaw || (!values.logoRaw && values.logo === '')) {
      if (accountConfig?.logo_url) {
        handleEditLogoModalDiscard(accountConfig.logo_url, null);
      } else {
        handleEditLogoModalDiscard('', null);
      }
    }
    if (merchantCheckoutStyledConfig?.brand_name)
      handleBrandNameChange(merchantCheckoutStyledConfig?.brand_name);
    if (merchantCheckoutStyledConfig?.title_style)
      handleTitleStyleChange(merchantCheckoutStyledConfig?.title_style);
    setShowEditModal(false);
  };

  const hasValuesChanged = (): boolean => {
    if (values.logoRaw || (!values.logoRaw && values.logo === '')) {
      return true;
    } else if (brandName !== merchantCheckoutStyledConfig?.brand_name) {
      return true;
    } else if (
      values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE] !== merchantCheckoutStyledConfig?.title_style
    ) {
      return true;
    }
    return false;
  };

  useEffect(() => {
    const url = values[CHECKOUT_EDITOR_FIELDS.LOGO];
    const rawLogo = values[CHECKOUT_EDITOR_FIELDS.LOGO_RAW]; //upload=>save=>open modal again
    if (url && url.length) {
      const fileName = url.split('/').pop();
      setFileName(fileName ?? '');
    } else if (rawLogo) {
      setFileName(rawLogo.name);
    }
  }, []);

  const content: any = (
    <>
      <HeadingWrapper>
        <Text color="surface.text.gray.normal" weight="semibold" size="large" variant="body">
          {EDIT_LOGO_TITLE_HEADER_VALUE[values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE]].title}
        </Text>
        <Text color="surface.text.gray.muted" weight="regular" size="small" variant="body">
          {EDIT_LOGO_TITLE_HEADER_VALUE[values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE]].subTitle}
        </Text>
      </HeadingWrapper>
      <ContentWrapper>
        <LeftContentWrapper>
          {shouldShowBrandNameOption ? (
            <BrandNameTextInput brandName={brandName} setBrandName={handleBrandNameChange} />
          ) : null}
          {shouldShowUploadLogoInput ? (
            <UploadLogo
              logo={logo}
              logoRaw={logoRaw}
              fileName={fileName}
              setFileName={setFileName}
            />
          ) : null}
        </LeftContentWrapper>
        <RightContentWrapper>
          <IframeWrapper>
            <CheckoutV2 shouldScaleToFit={true} forceLoadDesktopView={true} />
          </IframeWrapper>
        </RightContentWrapper>
      </ContentWrapper>
      <FooterWrapper>
        <Button variant="tertiary" color="primary" size="medium" onClick={onDiscard}>
          Discard
        </Button>
        <Button
          variant="primary"
          color="primary"
          size="medium"
          onClick={onSaveBrandNameAndLogo}
          isDisabled={!hasValuesChanged()}
        >
          Save and continue
        </Button>
      </FooterWrapper>
    </>
  );
  return (
    <ModalMask>
      <Modal className="non3dsCardsModal" onClose={onDiscard}>
        <ModalContent header={undefined} banner={undefined}>
          {content}
        </ModalContent>
      </Modal>
    </ModalMask>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  accountConfig: {
    ...state.config?.config,
    features: state.config?.features,
  },
  merchantCheckoutStyledConfig: state.config?.checkoutStylingConfig?.data,
});

export default connect(mapStateToProps)(EditLogoTitle);
