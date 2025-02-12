import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { useEffect } from 'react';
import track from './track';

const SpecialOfferBanner = ({ pricing, trialDays }) => {
  useEffect(() => {
    track.specialOffer({
      default_price: pricing.default,
      custom_price: pricing.rate,
    });

    if (trialDays) {
      track.trialPeriod({
        trial_days: trialDays,
      });
    }
  }, []);

  let discountInPercent;

  if (pricing && pricing.default) {
    discountInPercent = Math.round(((pricing.default - pricing.rate) / pricing.default) * 100);
  }

  return (
    <AnnouncementBanner
      className="widget-live-banner"
      title="🎉 Limited period offer"
      theme="purply"
    >
      <span className="display-inline">
        Affordability widget gets more affordable! Avail{' '}
        {pricing.default && pricing.rate < pricing.default ? (
          <>
            a <span className="trial-days">{discountInPercent}%</span> discount
            {trialDays ? ' with' : ''}
          </>
        ) : null}{' '}
        {trialDays ? (
          <>
            <span className="trial-days"> {trialDays}-day free</span> trial
          </>
        ) : null}
        . Get started now!
      </span>
    </AnnouncementBanner>
  );
};

export default SpecialOfferBanner;
