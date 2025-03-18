import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { ChevronRightIcon, Button, Box, Text, Switch, Alert } from '@razorpay/blade/components';
import {
  editStorefront,
  PaymentPagesStorefrontType,
} from 'merchant/reducers/paymentPages/storefront';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import LineItems from '../LineItems';
import { AlertState } from '../types';
import track from 'merchant/views/PaymentPages/PaymentPages/List/track';

const SocialHandleDrawerContainer = lazy(
  () => import(/* webpackChunkName: 'SocialHandleDrawerContainer' */ './SocialHandleDrawerContainer'),
);

interface IRightChildrenProps {
  handleClick: (val: boolean) => void;
}

interface IAddSocialMedialDetailsProps {
  handleAddSocialMediaClick: (val: boolean) => void;
  openSocialMediaDrawer?: boolean;
  storefront: PaymentPagesStorefrontType;
  editStorefront: (name: string, value: unknown) => void;
  showSocialMedialAlert: boolean;
  setShowSocialMediaAlert: React.Dispatch<React.SetStateAction<AlertState>>;
  isMobile: boolean;
  storefrontId: string;
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

interface SocialSwitchProps {
  isChecked: boolean | undefined;
  onSwitchChange: (isChecked: boolean) => void;
}

const SocialSwitch: React.FC<SocialSwitchProps> = ({ isChecked, onSwitchChange }) => {
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
        Turn on social handles preview
      </Text>

      <Switch
        isChecked={isChecked}
        accessibilityLabel="storefront social switch"
        onChange={({ isChecked }) => onSwitchChange(isChecked)}
      />
    </Box>
  );
};

const AddSocialMediaDetails: React.FC<IAddSocialMedialDetailsProps> = ({
  handleAddSocialMediaClick,
  openSocialMediaDrawer,
  storefront,
  editStorefront,
  isMobile,
  storefrontId,
  showSocialMedialAlert,
  setShowSocialMediaAlert,
}) => {
  const handleSocialHandlesSetting = (val: boolean) => {
    editStorefront('settings', {
      ...storefront.entity.settings,
      base_config: {
        ...(storefront.entity.settings.base_config || {}),
        social_handles_enabled: val,
      },
    });
  };

  const handleSocialSwitchToggle = (isChecked: boolean) => {
    handleSocialHandlesSetting(isChecked);
    track.socialHandlePreviewClicked({
      storefrontId,
      isNewStoreFront: Boolean(!storefrontId),
    })
  };

  const handleAlertPrimaryClick = () => {
    setShowSocialMediaAlert((prev) => ({
      ...prev,
      showSocialHandleAlert: false,
    }));
    handleSocialHandlesSetting(true);
    handleAddSocialMediaClick(true);
  };

  const handleAlertSecondaryClick = () => {
    setShowSocialMediaAlert((prev) => ({
      ...prev,
      showSocialHandleAlert: false,
    }));
    handleSocialHandlesSetting(false);
  };

  return (
    <>
      {openSocialMediaDrawer ? (
        <SuspenseWithLoader>
          <SocialHandleDrawerContainer
            handleClose={() =>  handleAddSocialMediaClick(false)}
            storefrontId={storefrontId}
          />
        </SuspenseWithLoader>
      ) : (
        <LineItems
          title="Add social handles"
          rightChildren={<RightChildren handleClick={handleAddSocialMediaClick} />}
          extraItems={
            <SocialSwitch
              isChecked={storefront?.entity?.settings?.base_config?.social_handles_enabled ?? false}
              onSwitchChange={handleSocialSwitchToggle}
            />
          }
          isDetailsFilled={storefront?.entity?.social_handles?.length > 0}
          isMobile={isMobile}
        />
      )}
      {showSocialMedialAlert && (
        <Alert
          title="Missing social handles"
          description="Your store is being published without social handles. To proceed, either add a social handle or disable the social handles option."
          marginTop="spacing.4"
          position="absolute"
          top="62px"
          right={isMobile ? 'spacing.5' : '60px'}
          left={isMobile ? 'spacing.4' : 'none'}
          actions={{
            primary: { onClick: handleAlertPrimaryClick, text: 'Upload Social Handles' },
            secondary: { onClick: handleAlertSecondaryClick, text: 'Disable Social Handles' },
          }}
          color="notice"
          emphasis="intense"
          onDismiss={() =>
            setShowSocialMediaAlert((prev) => ({
              ...prev,
              showSocialHandleAlert: false,
            }))
          }
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

export default connect(mapStateToProps, mapDispatchToProps)(AddSocialMediaDetails);
