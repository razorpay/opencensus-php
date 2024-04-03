import React from 'react';
import { DownloadIcon } from '@razorpay/blade/components';
import { ReportsCardWrapper } from 'merchant/views/MagicCheckout/OrderAnalytics/Reports/styled';

const ReportsCard = (props) => {
  const { getDownloadLink, category, heading, desc } = props;

  return (
    <ReportsCardWrapper>
      <div className="card" onClick={() => getDownloadLink(category)}>
        <div className="text-container">
          <div className="heading">{heading}</div>
          <div className="desc">{desc}</div>
        </div>
        <div className="download-icon">
          <DownloadIcon size="medium" color="interactive.icon.primary.normal" />
        </div>
      </div>
    </ReportsCardWrapper>
  );
};

export default ReportsCard;
