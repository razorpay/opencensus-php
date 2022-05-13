import React, { useEffect, useState } from 'react';
import rTracking from 'react-tracking';
import CommissionCardBody from 'merchant/views/PartnerDashboard/Commissions/components/FUX-Cards/CommissionCard/CardBody';
import { setItem, getItem } from 'common/utils/localStorage';

const CommissionCard = ({ tracking }) => {
  const [closed, setClosed] = useState(true);
  const handleCardClose = () => {
    setClosed(true);
    setItem('fux-commission-card-closed', true);
    tracking?.trackEvent(window?.rzpQ?.onbr()?.interaction('fux.commission-card.close'));
  };

  useEffect(() => {
    const isCardClosed = getItem('fux-commission-card-closed') || false;
    setClosed(isCardClosed);
  }, []);

  if (closed) return null;
  return (
    <div className="fux-commission-cards lg fux-cards-close">
      <i className="fa fa-times close-icon" role="button" onClick={handleCardClose} />
      <div className="card-title">Get to know your rewards and Bonuses</div>
      <CommissionCardBody />
    </div>
  );
};

export default rTracking(() => window.rzpQ.component('CommissionCard'))(CommissionCard);
