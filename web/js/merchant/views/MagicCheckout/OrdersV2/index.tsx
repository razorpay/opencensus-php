import React from 'react';
import { connect } from 'react-redux';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import VerticalNavContainer from 'merchant/views/MagicCheckout/common/components/VerticalNavContainer';

import { DEFAULT_ROUTES as NAV_ITEMS } from 'merchant/views/MagicCheckout/OrdersV2/routes';

import { GenericRecord, User } from 'merchant/views/MagicCheckout/types';

import {
  PATH_PREFIX,
  COD_ORDERS,
  COD_ORDER_CONEVRSION,
  EDIT_ORDERS,
} from 'merchant/views/MagicCheckout/OrdersV2/constants';
import { PLATFORMS } from 'merchant/views/MagicCheckout/constants';

interface OrdersProps {
  magicCheckout: GenericRecord;
}

const Orders: React.FC<OrdersProps> = ({ magicCheckout }) => {
  const { platform, cod_order_control, one_cc_prepay_cod_conversion } = magicCheckout;

  const customRouteCheck = (item, _user: User) => {
    if (item.label === COD_ORDERS && !cod_order_control) return null;
    if (
      item.label === COD_ORDER_CONEVRSION &&
      (platform === 'native' || !one_cc_prepay_cod_conversion)
    )
      return null;
    if (item.label === EDIT_ORDERS && platform !== PLATFORMS.SHOPIFY) return null;
    return true;
  };

  //Common Component to render L2 Navigation
  return (
    <SuspenseWithLoader type="center">
      <VerticalNavContainer
        NAV_ITEMS={NAV_ITEMS}
        PATH_PREFIX={PATH_PREFIX}
        customRouteCheck={customRouteCheck}
      />
    </SuspenseWithLoader>
  );
};

const mapStateToProps = (state) => ({
  magicCheckout: state.magicCheckout,
});

export default connect(mapStateToProps, null)(Orders);
