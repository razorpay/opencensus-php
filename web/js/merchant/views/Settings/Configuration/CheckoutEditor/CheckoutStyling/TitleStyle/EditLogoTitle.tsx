import React from 'react';
import { Button, Text, ArrowLeftIcon, Box, IconButton } from '@razorpay/blade/components';
import { EditLogoTitleProps } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/titleType';
import { connect } from 'react-redux';

import { Modal, ModalContent, ModalMask } from 'common/new-ui/Modal';
import CheckoutV2 from 'merchant/views/Settings/Configuration/CheckoutDemo/CheckoutV2';
import {
  AVAILABLE_TITLE_STYLE,
  EDIT_LOGO_TITLE_HEADER_VALUE,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import {
  EMPTY_LOGO,
  EMPTY_WORDMARK,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';

import BrandNameTextInput from './BrandNameTextInput';
import UploadLogo from './UploadLogo';
import {
  ContentWrapper,
  EditModalHeadingWrapper,
  FooterWrapper,
  IframeWrapper,
  LeftContentWrapper,
  RightContentWrapper,
} from './styled';

const EditLogoTitle: React.FC<EditLogoTitleProps> = ({
  setShowTitleTypeModal,
  setShowEditModal,
  accountConfig,
  user,
  merchantCheckoutStyledConfig,
  selectedTitleStyle,
}) => {
  const {
    values,
    handleBrandNameChange,
    handleTitleStyleChange,
    handleSaveTitleModal,
    handleWordmarkChange,
    handleLogoChange,
    isSavingTitleModalChange,
  } = useCheckoutEditor();
  const brandName = values[CHECKOUT_EDITOR_FIELDS.BRAND_NAME];
  const logo = values[CHECKOUT_EDITOR_FIELDS.LOGO];
  const logoRaw = values[CHECKOUT_EDITOR_FIELDS.LOGO_RAW];
  const wordmark = values[CHECKOUT_EDITOR_FIELDS.WORDMARK];
  const wordmarkRaw = values[CHECKOUT_EDITOR_FIELDS.WORDMARK_RAW];

  const shouldShowBrandNameTextInput =
    selectedTitleStyle === AVAILABLE_TITLE_STYLE.TEXT_ONLY ||
    selectedTitleStyle === AVAILABLE_TITLE_STYLE.LOGO_TEXT;

  const shouldShowUploadLogoInput = selectedTitleStyle === AVAILABLE_TITLE_STYLE.LOGO_TEXT;

  const shouldShowUploadWordmarkInput = selectedTitleStyle === AVAILABLE_TITLE_STYLE.WORDMARK;

  const onSaveBrandNameAndLogo = () => {
    handleSaveTitleModal(setShowEditModal);
  };

  const onDiscard = () => {
    if (values.logoRaw || (values.logo === EMPTY_LOGO && accountConfig.logo_url)) {
      handleLogoChange(null, accountConfig.logo_url ?? EMPTY_LOGO);
    }
    if (wordmarkRaw || (wordmark === EMPTY_WORDMARK && merchantCheckoutStyledConfig.wordmark_url)) {
      handleWordmarkChange(null, merchantCheckoutStyledConfig.wordmark_url ?? EMPTY_WORDMARK);
    }
    if (merchantCheckoutStyledConfig?.brand_name)
      handleBrandNameChange(merchantCheckoutStyledConfig?.brand_name);
    if (merchantCheckoutStyledConfig?.title_style)
      handleTitleStyleChange(merchantCheckoutStyledConfig?.title_style);
    setShowEditModal(false);
  };

  const hasValuesChanged = (): boolean => {
    if (values.logoRaw || (values.logo === EMPTY_LOGO && accountConfig.logo_url)) {
      return true;
    } else if (brandName !== merchantCheckoutStyledConfig?.brand_name) {
      return true;
    } else if (
      values.wordmarkRaw ||
      (values.wordmark === EMPTY_WORDMARK && merchantCheckoutStyledConfig.wordmark_url)
    ) {
      return true;
    } else if (
      values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE] !== merchantCheckoutStyledConfig.title_style
    ) {
      return true;
    }
    return false;
  };

  const onBackButtonClick = () => {
    onDiscard();
    setShowTitleTypeModal(true);
  };
  const content: any = (
    <>
      <EditModalHeadingWrapper>
        <IconButton
          icon={ArrowLeftIcon}
          accessibilityLabel="go-back-edit-modal"
          emphasis="intense"
          size="large"
          onClick={onBackButtonClick}
        />
        <Box display="flex" flexDirection="column">
          <Text color="surface.text.gray.normal" weight="semibold" size="large" variant="body">
            {EDIT_LOGO_TITLE_HEADER_VALUE[selectedTitleStyle]?.title}
          </Text>
          <Text color="surface.text.gray.muted" weight="regular" size="small" variant="body">
            {EDIT_LOGO_TITLE_HEADER_VALUE[selectedTitleStyle]?.subTitle}
          </Text>
        </Box>
      </EditModalHeadingWrapper>
      <ContentWrapper>
        <LeftContentWrapper>
          {shouldShowBrandNameTextInput ? (
            <BrandNameTextInput
              brandName={brandName}
              setBrandName={handleBrandNameChange}
              isDisabled={!user.isAdminOrOwner}
            />
          ) : null}
          {shouldShowUploadLogoInput ? (
            <UploadLogo type="logo" image={logo} imageRaw={logoRaw} />
          ) : null}
          {shouldShowUploadWordmarkInput ? (
            <UploadLogo type="wordmark" image={wordmark} imageRaw={wordmarkRaw} />
          ) : null}
        </LeftContentWrapper>
        <RightContentWrapper>
          <IframeWrapper>
            <CheckoutV2 zoomTitleStyle={true} forceLoadDesktopView={true} />
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
          isLoading={isSavingTitleModalChange}
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
