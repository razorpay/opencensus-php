import React from 'react';
import LineItems from './LineItems';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { ChevronRightIcon, Button, Box, Text, Switch, Alert } from '@razorpay/blade/components';
import {
  editStorefront,
  PaymentPagesStorefrontType,
} from 'merchant/reducers/paymentPages/storefront';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { AlertState } from './types';

const AddSocialHandleDrawer = lazy(
  () => import(/* webpackChunkName: 'AddSocialHandleDrawer' */ './AddSocialHandleDrawer'),
);

interface IRightChildrenProps {
  handleClick: (val: boolean) => void;
}

interface IAddSocialMedialDetailsProps {
  handleClick: (val: boolean) => void;
  openSocialMediaDrawer?: boolean;
  storefront: PaymentPagesStorefrontType;
  editStorefront: (name: string, value: unknown) => void;
  showSocialMedialAlert: boolean;
  setShowSocialMedialAlert: React.Dispatch<React.SetStateAction<AlertState>>;
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
        accessibilityLabel="storefront-social-switch"
        onChange={({ isChecked }) => onSwitchChange(isChecked)}
      />
    </Box>
  );
};

const AddSocialMediaDetails: React.FC<IAddSocialMedialDetailsProps> = ({
  handleClick,
  openSocialMediaDrawer,
  storefront,
  editStorefront,
  isMobile,
  storefrontId,
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

  const handleBannerSwitchChange = (isChecked: boolean) => {
    handleSocialHandlesSetting(isChecked);
  };

  return (
    <>
      {openSocialMediaDrawer ? (
        <SuspenseWithLoader>
          <AddSocialHandleDrawer
            handleClose={() => handleClick(false)}
            storefrontId={storefrontId}
          />
        </SuspenseWithLoader>
      ) : (
        <LineItems
          title="Add social handles"
          rightChildren={<RightChildren handleClick={handleClick} />}
          extraItems={
            <SocialSwitch
              isChecked={storefront?.entity?.settings?.base_config?.social_handles_enabled ?? false}
              onSwitchChange={handleBannerSwitchChange}
            />
          }
          isDetailsFilled={storefront?.entity?.social_handles?.length > 0}
          isMobile={isMobile}
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
