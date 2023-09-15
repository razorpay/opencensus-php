import { useEffect, useState } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import SideNav from 'merchant/views/MagicCheckout/OrderStatusUpload/components/SideNav';
import MainContent from 'merchant/views/MagicCheckout/OrderStatusUpload/components/MainContent';
import OrderInfoSlider from 'merchant/views/MagicCheckout/CODOrdersTab/orderInfoDrawer';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

import { getURLQueryParams } from 'common/utils/rzp-utils';
import { updateFilters as updateOrderFilters } from 'merchant/reducers/magicCheckout/codOrders/action';
import { TABS } from 'merchant/views/MagicCheckout/CODOrdersTab/constants';

const CODOrdersTab = ({ updateFilters }) => {
  const [activeNav, setActiveNav] = useState(TABS[0].id);
  const [isSliderOpen, setIsSliderOpen] = useState(false);

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
  }, [requiredOrderId]);

  const setContent = (selectedTab) =>
    TABS.map((item) =>
      item.id === selectedTab ? <div key={item.id}>{item.component}</div> : null,
    );

  return (
    <SuspenseWithLoader type="center">
      <div className="display-flex nav-container cod-orders-container">
        <SideNav tabs={TABS} onTabClick={(id) => setActiveNav(id)} activeNav={activeNav} />
        <MainContent activeNav={activeNav} render={setContent} />
      </div>
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
