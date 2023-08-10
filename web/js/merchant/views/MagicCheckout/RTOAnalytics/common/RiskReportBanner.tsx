import React from 'react';
import { NavLink } from 'react-router-dom';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import Button from 'common/new-ui/Button';

import graphScale from 'assets/magic_checkout/graph-scale.svg';

const RiskReportBanner = (): JSX.Element => {
  return (
    <AnnouncementBanner
      title="Increase prepaid orders"
      theme="primary"
      card_id="nudging-cod-intelligence-banner"
      className="nudging-cod-intelligence-banner"
      canBeClosed
    >
      <p>
        Increase your prepaid orders by 10%{' '}
        <img src={graphScale} alt="graph-scale" className="graph-scale" loading="lazy" /> with COD
        intelligence integration.
      </p>
      <NavLink to="/magic/settings/rto-settings">
        <Button.Transparent type="button" className="btn btn-primary intelligence-link-cta">
          Integrate Now
          <span>
            <i className="i i-chevron-right" />
          </span>
        </Button.Transparent>
      </NavLink>
    </AnnouncementBanner>
  );
};

export default RiskReportBanner;
