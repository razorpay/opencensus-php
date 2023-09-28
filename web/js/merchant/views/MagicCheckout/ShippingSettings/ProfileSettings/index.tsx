import React from 'react';
import { Box, Button, Heading } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { clearProfile } from 'merchant/reducers/magicCheckout/shippingEngine/action';
import { ShippingEngineStore } from 'merchant/reducers/magicCheckout/shippingEngine/types';
import { ADD_PROFILE } from 'merchant/views/MagicCheckout/ShippingSettings/constants';
import { useShippingSettingsRouteContext } from 'merchant/views/MagicCheckout/ShippingSettings/context/RouteContext';
import { Separator } from 'merchant/views/MagicCheckout/ShippingSettings/styles';

import ProductCategories from './ProductCategories';
import ShippingMethods from './ShippingMethods';
import Zones from './Zones';

const ProfileSettings = ({ clearProfile, shippingEngine }): JSX.Element => {
  const { default_profile, selected_profile, shipping_profiles } =
    shippingEngine as ShippingEngineStore;
  const profile = selected_profile ? shipping_profiles[selected_profile] : null;
  const isDefault = profile?.name === default_profile?.name;
  const isAdd = profile?.name === ADD_PROFILE;
  const hasZones = profile?.zones?.length;

  const { setActiveRoute } = useShippingSettingsRouteContext();

  const handleCancelClick = () => {
    clearProfile();
    setActiveRoute('preview');
  };

  return (
    <Box>
      <Heading marginBottom="spacing.8" size="medium">
        {isDefault ? 'Default' : 'General'} shipping profiles
      </Heading>
      <ProductCategories />
      <Separator />
      {!isAdd && (
        <>
          <Zones />
          <Separator />
        </>
      )}
      {hasZones ? <ShippingMethods /> : null}

      <Box display="flex" gap="spacing.3" justifyContent="flex-end" marginTop="spacing.8">
        <Button variant="secondary" onClick={handleCancelClick}>
          Go back
        </Button>
      </Box>
    </Box>
  );
};

const mapStateToProps = (state) => ({
  shippingEngine: state.magicShippingEngine,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      clearProfile,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(ProfileSettings);
