import React from 'react';
import { Box, Button, Heading, PlusCircleIcon, Text } from '@razorpay/blade/components';
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
        <Box
          display="flex"
          alignItems="left"
          justifyContent="space-between"
          marginY="spacing.7"
          flexDirection="column"
          width="80%"
        >
          <Heading>Custom Shipping Profile</Heading>
          <Text type="subdued" size="small">
            Create distinct shipping profiles for specific products or categories, ensuring optimal
            rates, faster deliveries, and delighted customer experience.
          </Text>
        </Box>
        <Button onClick={handleAddProfile} icon={PlusCircleIcon}>
          Add profile
        </Button>
      </Box>
      <ProfileTable type="general" />
      <Box display="flex" alignItems="center" justifyContent="space-between" marginY="spacing.7">
        <Box
          display="flex"
          alignItems="left"
          justifyContent="space-between"
          marginY="spacing.7"
          flexDirection="column"
          width="75%"
        >
          <Heading>Default Shipping Profile (Mandatory)</Heading>
          <Text type="subdued" size="small">
            This profile acts as your safety net, ensuring there's always a shipping rate available
            for your products. Whenever configurations for products added to cart aren't found,
            we'll rely on these generic rates to provide a seamless checkout experience for your
            customers.
          </Text>
        </Box>
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
