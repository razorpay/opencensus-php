import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { BannerType } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/Banner/config';
import React from 'react';
import { Link } from 'react-router-dom';
import { getHomePageBanners } from './config';

const BankAccountUpdateBanner = ({ type }: { type: BannerType }): JSX.Element | null => {
  const bannerInfo = getHomePageBanners({
    type,
  });
  if (!bannerInfo) return null;

  return (
    <AnnouncementBanner title={bannerInfo.title} theme={bannerInfo.theme}>
      {bannerInfo.description} •{' '}
      <Link to={bannerInfo.knowMoreLink} className="pointer">
        <strong>Know More</strong>
      </Link>{' '}
    </AnnouncementBanner>
  );
};

export default BankAccountUpdateBanner;
