import React from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import MerchantAgreement from './MerchantAgreement';
import PricingAgreement from './PricingAgreement';

const POSMerchantAgreement = () => {
  return (
    <Routes>
      <Route index path="/sign" element={<MerchantAgreement />} />
      <Route path="/pricing" element={<PricingAgreement />} />
      <Route path="*" element={<Navigate to="/pos-merchant-agreement/sign" replace />} />
    </Routes>
  );
};

export default POSMerchantAgreement;
