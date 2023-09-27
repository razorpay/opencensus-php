import React from 'react';
import { Box, Button, Heading, PlusCircleIcon } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { setProfile } from 'merchant/reducers/magicCheckout/shippingEngine/action';
import ProfileTable from 'merchant/views/MagicCheckout/ShippingSettings/common/ProfileTable';
import { ADD_PROFILE } from 'merchant/views/MagicCheckout/ShippingSettings/constants';
import { useShippingSettingsRouteContext } from 'merchant/views/MagicCheckout/ShippingSettings/context/RouteContext';

const PreviewSettings = ({ setProfile, default_profile }): JSX.Element => {
  const { setActiveRoute } = useShippingSettingsRouteContext();
  const handleClick = () => {
    setActiveRoute('profile');
    setProfile(default_profile?.name);
  };

  const handleAddProfile = () => {
    setActiveRoute('profile');
    setProfile(ADD_PROFILE);
  };

  return (
    <>
      <Heading size="medium">Shipping Profiles</Heading>
      <Box display="flex" alignItems="center" justifyContent="space-between" marginY="spacing.7">
        <Heading>General Shipping Profile</Heading>
        <Button onClick={handleAddProfile} icon={PlusCircleIcon}>
          Add profile
        </Button>
      </Box>
      <ProfileTable type="general" />
      <Box display="flex" alignItems="center" justifyContent="space-between" marginY="spacing.7">
        <Heading>Default Shipping Profile</Heading>
        <Button onClick={handleClick} icon={PlusCircleIcon}>
          Set Default profile
        </Button>
      </Box>
      <ProfileTable />
    </>
  );
};

const mapStateToProps = (state) => ({
  default_profile:
    state.magicShippingEngine.shipping_profiles[state.magicShippingEngine.default_profile?.name],
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      setProfile,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(PreviewSettings);
