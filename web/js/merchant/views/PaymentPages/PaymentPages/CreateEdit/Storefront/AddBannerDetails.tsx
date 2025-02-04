import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import track from 'merchant/views/PaymentPages/PaymentPages/List/track';
import { Alert, Box, Button, ChevronRightIcon, Switch, Text } from '@razorpay/blade/components';
import LineItems from './LineItems';
import {
  editStorefront,
  PaymentPagesStorefrontType,
} from 'merchant/reducers/paymentPages/storefront';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

const UploadBannerDrawer = lazy(
  () => import(/* webpackChunkName: 'StorefrontV1UploadBanner' */ './UploadBannerDrawer'),
);

interface IAddBannerDetailsProps {
  handleClick: (val: boolean) => void;
  openAddBannerDrawer?: boolean;
  storefront: PaymentPagesStorefrontType;
  editStorefront: (name: string, value: unknown) => void;
  showBannerAlert: boolean;
  setShowBannerAlert: React.Dispatch<React.SetStateAction<boolean>>;
  isMobile: boolean;
}

interface IRightChildrenProps {
  handleClick: (val: boolean) => void;
}

const RightChildren: React.FC<IRightChildrenProps> = ({ handleClick }) => (
  <Button
    variant="tertiary"
    color="primary"
    size="xsmall"
    icon={ChevronRightIcon}
    onClick={() => handleClick(true)}
  />
);

interface BannerSwitchProps {
  isChecked: boolean | undefined;
  onSwitchChange: (isChecked: boolean) => void;
}

const BannerSwitch: React.FC<BannerSwitchProps> = ({ isChecked, onSwitchChange }) => {
  return (
    <Box
      display="flex"
      justifyContent="space-between"
      alignItems="center"
      borderTopWidth="thin"
      borderTopColor="surface.border.gray.muted"
      borderTopStyle="solid"
      paddingTop="spacing.4"
    >
      <Text weight="regular" color="surface.text.gray.subtle" variant="body" size="medium">
        Turn on banner preview
      </Text>

      <Switch
        isChecked={isChecked}
        accessibilityLabel="storefront-banner-switch"
        onChange={({ isChecked }) => onSwitchChange(isChecked)}
      />
    </Box>
  );
};

const AddBannerDetails: React.FC<IAddBannerDetailsProps> = ({
  handleClick,
  openAddBannerDrawer,
  storefront,
  editStorefront,
  showBannerAlert,
  setShowBannerAlert,
  isMobile,
}) => {
  const isDetailsFilled = storefront.entity.banner_images?.length > 0;
  const handleBannerSetting = (val: boolean) => {
    editStorefront('settings', {
      ...storefront.entity.settings,
      base_config: {
        ...(storefront.entity.settings.base_config || {}),
        banner_feature_enabled: val,
      },
    });
  };

  const handleBannerSwitchChange = (isChecked: boolean) => {
    track.bannerPreviewCheckboxClicked({
      storefrontId: storefront?.id,
      isNewStoreFront: Boolean(!storefront?.id),
      isChecked,
    });
    handleBannerSetting(isChecked);
  };

  const handleAlertPrimaryClick = () => {
    track.uploadBannerClickedOnMissingBannerMsg({
      storefrontId: storefront?.id,
      isNewStoreFront: Boolean(!storefront?.id),
    });
    setShowBannerAlert(false);
    handleBannerSetting(true);
    handleClick(true);
  };

  const handleAlertSecondaryClick = () => {
    setShowBannerAlert(false);
    handleBannerSetting(false);
  };

  return (
    <>
      {openAddBannerDrawer ? (
        <SuspenseWithLoader>
          <UploadBannerDrawer handleClose={() => handleClick(false)} storefront={storefront} />
        </SuspenseWithLoader>
      ) : (
        <LineItems
          title="Add store banner"
          subTitle={
            <Text color="surface.text.gray.muted" variant="body" size="small" weight="regular">
              This is an optional field
            </Text>
          }
          rightChildren={<RightChildren handleClick={handleClick} />}
          extraItems={
            <BannerSwitch
              isChecked={storefront?.entity?.settings?.base_config?.banner_feature_enabled ?? false}
              onSwitchChange={handleBannerSwitchChange}
            />
          }
          isMobile={isMobile}
          isDetailsFilled={isDetailsFilled}
        />
      )}
      {showBannerAlert && (
        <Alert
          title="Missing Banner Image"
          description="Your store is being published without a banner image. Please upload a banner image or disable the banner to proceed."
          marginTop="spacing.4"
          position="absolute"
          top="62px"
          right={isMobile ? 'spacing.5' : '60px'}
          left={isMobile ? 'spacing.4' : 'none'}
          actions={{
            primary: { onClick: handleAlertPrimaryClick, text: 'Upload Banner' },
            secondary: { onClick: handleAlertSecondaryClick, text: 'Disable Banner' },
          }}
          color="notice"
          emphasis="intense"
          onDismiss={() => setShowBannerAlert(false)}
        />
      )}
    </>
  );
};

const mapStateToProps = (state: any) => ({
  storefront: state.paymentPageStorefront,
  isMobile: state.app.isMobileResolution,
});

const mapDispatchToProps = (dispatch: any) => ({
  editStorefront: bindActionCreators(editStorefront, dispatch),
});

export default connect(mapStateToProps, mapDispatchToProps)(AddBannerDetails);
