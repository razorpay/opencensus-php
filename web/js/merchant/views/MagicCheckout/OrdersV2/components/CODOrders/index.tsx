import React, { useEffect, useState } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { useLocation } from 'react-router-dom';

import OrderInfoSlider from 'merchant/views/MagicCheckout/CODOrdersTab/orderInfoDrawer';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import NavContainer from 'merchant/views/MagicCheckout/common/components/NavContainer';

import { updateFilters as updateOrderFilters } from 'merchant/reducers/magicCheckout/codOrders/action';

import { getURLQueryParams } from 'common/utils/rzp-utils';

import { TABS } from 'merchant/views/MagicCheckout/CODOrdersTab/constants';
import { DEFAULT_ROUTES } from 'merchant/views/MagicCheckout/OrdersV2/components/CODOrders/routes';
import { COD_ORDERS_PATH } from 'merchant/views/MagicCheckout/OrdersV2/constants';

const CODOrdersTab = ({ updateFilters }) => {
  const [activeNav, setActiveNav] = useState(TABS[0].id);
  const [isSliderOpen, setIsSliderOpen] = useState(false);
  const location = useLocation();
  const requiredOrderId = getURLQueryParams(window.location.search).order_id;

  useEffect(() => {
    updateFilters({
      id: '',
      receipt: '',
      riskTier: '',
      count: 25,
      from: '',
      to: '',
      skip: 0,
      selectedPresetFromParent: null,
      items: [],
      reviewMode: '',
    });
  }, [activeNav]);

  useEffect(() => {
    if (requiredOrderId) setIsSliderOpen(true);
  }, [requiredOrderId, location]);

  return (
    <SuspenseWithLoader type="center">
      <NavContainer
        navItems={DEFAULT_ROUTES}
        basePath={COD_ORDERS_PATH}
        handleNavClick={(tab) => setActiveNav(tab?.id)}
      />
      {requiredOrderId && (
        <OrderInfoSlider
          isSliderOpen={isSliderOpen}
          setIsSliderOpen={setIsSliderOpen}
          requiredOrderId={requiredOrderId}
        />
      )}
    </SuspenseWithLoader>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateFilters: updateOrderFilters,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(CODOrdersTab);
