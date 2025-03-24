import React from 'react';
import {
  Button,
  ArrowLeftIcon,
  Box,
  IconButton,
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
} from '@razorpay/blade/components';
import { EditLogoTitleProps } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/titleType';
import { connect } from 'react-redux';

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
import track from './track';

const LeftContentWrapper = ({ children }) => {
  return (
    <Box
      display="flex"
      justifyContent="flex-start"
      gap="spacing.7"
      width="412px"
      alignItems="center"
      flexDirection="column"
    >
      {children}
    </Box>
  );
};

const RightContentWrapper = ({ children }) => {
  return (
    <Box
      display="flex"
      alignItems="center"
      borderRadius="large"
      backgroundColor="surface.background.gray.moderate"
      width="412px"
      height="283px"
      overflow="hidden"
      position="relative"
    >
      {children}
    </Box>
  );
};

const IframeWrapper = ({ children }) => {
  return (
    <Box
      position="absolute"
      top="330px"
      height="470px"
      width="470px"
      left="330px"
      transform=" translateY(-35%) scale(2.2, 2.2)"
    >
      {children}
    </Box>
  );
};

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
  const titleStyle = values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE];

  const shouldShowBrandNameTextInput =
    selectedTitleStyle === AVAILABLE_TITLE_STYLE.TEXT_ONLY ||
    selectedTitleStyle === AVAILABLE_TITLE_STYLE.LOGO_TEXT;

  const shouldShowUploadLogoInput = selectedTitleStyle === AVAILABLE_TITLE_STYLE.LOGO_TEXT;

  const shouldShowUploadWordmarkInput = selectedTitleStyle === AVAILABLE_TITLE_STYLE.WORDMARK;

  const onSaveBrandNameAndLogo = () => {
    handleSaveTitleModal(setShowEditModal);
    track.saveTitleStyle({
      titleStyle: values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE],
      brandName:
        titleStyle === AVAILABLE_TITLE_STYLE.LOGO_TEXT ||
        titleStyle === AVAILABLE_TITLE_STYLE.TEXT_ONLY
          ? brandName
          : null,
      logo: titleStyle === AVAILABLE_TITLE_STYLE.LOGO_TEXT && logoRaw ? 'uploaded' : null,
      wordmark: titleStyle === AVAILABLE_TITLE_STYLE.WORDMARK && wordmarkRaw ? 'uploaded' : null,
    });
  };

  const onDiscard = () => {
    if (values.logoRaw || (values.logo === EMPTY_LOGO && accountConfig.logo_url)) {
      handleLogoChange(null, accountConfig.logo_url ?? EMPTY_LOGO);
    }
    if (
      wordmarkRaw ||
      (wordmark === EMPTY_WORDMARK && merchantCheckoutStyledConfig?.wordmark_url)
    ) {
      handleWordmarkChange(null, merchantCheckoutStyledConfig?.wordmark_url ?? EMPTY_WORDMARK);
    }
    if (merchantCheckoutStyledConfig?.brand_name)
      handleBrandNameChange(merchantCheckoutStyledConfig?.brand_name);
    if (merchantCheckoutStyledConfig?.title_style)
      handleTitleStyleChange(merchantCheckoutStyledConfig?.title_style);
    setShowEditModal(false);
    track.discardTitleStyle(values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE]);
  };

  const hasValuesChanged = (): boolean => {
    if (values.logoRaw || (values.logo === EMPTY_LOGO && accountConfig.logo_url)) {
      return true;
    } else if (brandName !== merchantCheckoutStyledConfig?.brand_name) {
      return true;
    } else if (
      values.wordmarkRaw ||
      (values.wordmark === EMPTY_WORDMARK && merchantCheckoutStyledConfig?.wordmark_url)
    ) {
      return true;
    } else if (
      values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE] !== merchantCheckoutStyledConfig?.title_style
    ) {
      return true;
    }
    return false;
  };

  const onBackButtonClick = () => {
    onDiscard();
    setShowTitleTypeModal(true);
  };

  return (
    <Modal isOpen={true} onDismiss={onDiscard} size="large">
      <ModalHeader
        leading={
          <IconButton
            icon={ArrowLeftIcon}
            accessibilityLabel="go-back-edit-modal"
            emphasis="intense"
            size="large"
            onClick={onBackButtonClick}
          />
        }
        title={EDIT_LOGO_TITLE_HEADER_VALUE[selectedTitleStyle]?.title}
        subtitle={EDIT_LOGO_TITLE_HEADER_VALUE[selectedTitleStyle]?.subTitle}
      />
      <ModalBody>
        <Box
          display="flex"
          justifyContent="center"
          alignItems="center"
          gap="spacing.7"
          width="100%"
          padding="spacing.4"
        >
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
        </Box>
      </ModalBody>

      <ModalFooter>
        <Box display="flex" width="100%" gap="spacing.5" justifyContent="flex-end">
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
        </Box>
      </ModalFooter>
    </Modal>
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
