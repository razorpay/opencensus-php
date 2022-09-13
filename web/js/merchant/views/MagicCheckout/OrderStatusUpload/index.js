import { useCallback, useState } from 'react';
import SideNav from 'merchant/views/MagicCheckout/OrderStatusUpload/components/SideNav';
import MainContent from 'merchant/views/MagicCheckout/OrderStatusUpload/components/MainContent';
import { NAV_ITEM } from 'merchant/views/MagicCheckout/OrderStatusUpload/constants';

const OrderStatusUpload = () => {
  const [activeNav, setActiveNav] = useState(NAV_ITEM[0].id);

  const setContent = useCallback(
    (selectedTab) =>
      NAV_ITEM.map((item) =>
        item.id === selectedTab ? <div key={item.id}>{item.component}</div> : null,
      ),
    [activeNav],
  );

  return (
    <div className="display-flex nav-container order-status-container">
      <SideNav tabs={NAV_ITEM} onTabClick={(id) => setActiveNav(id)} activeNav={activeNav} />
      <MainContent activeNav={activeNav} render={setContent} />
    </div>
  );
};

export default OrderStatusUpload;
