import React, { useState } from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import OfferPage from './components/OfferPage';
import { OFFER_DETAILS } from './data';
import RazorpayXIFrame from './RazorpayXIFrame';

const ConnectedBanking = ({ bankNameProp, location }) => {
  const bankName = bankNameProp || 'ICICI';
  const url = location?.state?.url || `${window.bankingServiceUrl}/current-account-linking`;
  const [showState, setShowState] = useState(location?.state?.showState || 'offer-page');

  if (showState === 'offer-page')
    return <OfferPage offerDetails={OFFER_DETAILS[bankName]} setShowState={setShowState} />;

  return <RazorpayXIFrame url={url} />;
};

export default withRouter(ConnectedBanking);
