import React from 'react';

import Popover, { PopoverBody } from 'common/ui/Popover';
import { ACCOUNT_LINKABLE } from 'merchant/views/Settings/PaymentMethods/constants';

const ProductionAccount = () => {
  return (
    <div style={{ background: 'rgba(232, 235, 239, 0.32)', padding: '10px' }}>
      <div
        style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
        }}
      >
        <p>Live Mode</p>
        <div className="activated status">
          <Popover align="bottom" theme="dark">
            <PopoverBody>
              <div style={{ textAlign: 'left', textTransform: 'none' }}>Activated</div>
            </PopoverBody>
          </Popover>
        </div>
      </div>
      <p>
        Paytm Wallet is enabled on Live Mode, customers can transact with Paytm wallet. To view/edit
        your Paytm Production API Credentials, click here.
      </p>
    </div>
  );
};

export const PaytmWallet = ({ paytm_production_status, loading, handlePaytmWalletIntegration }) => {
  return (
    <div className="flex-end">
      {[ACCOUNT_LINKABLE].includes(paytm_production_status) && (
        <button
          className="btn btn-primary mr-25 ml-5"
          disabled={loading}
          onClick={handlePaytmWalletIntegration}
        >
          {(loading && 'Loading..') || 'Link Account'}
        </button>
      )}
      {paytm_production_status === 'activated' && <ProductionAccount loading={loading} />}
    </div>
  );
};
