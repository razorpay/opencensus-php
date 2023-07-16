import React from 'react';
import PageContent from 'merchant/views/MagicCheckout/ShopifyOrderEditing/PageContent';

const ShopifyOrderEditing: React.FC = () => {
  return (
    <div className="display-flex nav-container cod-orders-container">
      <div className="tab-content col-sm-10 no-padding">
        <PageContent />
      </div>
    </div>
  );
};

export default ShopifyOrderEditing;
