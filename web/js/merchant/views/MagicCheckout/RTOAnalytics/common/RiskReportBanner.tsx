import React from 'react';
import { NavLink } from 'react-router-dom';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import Button from 'common/new-ui/Button';

import { useMagicExperiment } from 'merchant/views/MagicCheckout/utils/useMagicExperiment';

import { MAGIC_DASHBOARD_REVAMP_EXPERIMENT } from 'merchant/views/MagicCheckout/constants';
import { RTO_REDUCTION_SETUP_ROUTE_V2 } from '../widgets/constants';

import graphScale from 'assets/magic_checkout/graph-scale.svg';

const RiskReportBanner = (): JSX.Element => {
  const isMagicDashboardV2Enabled = useMagicExperiment(MAGIC_DASHBOARD_REVAMP_EXPERIMENT);

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
      <NavLink
        to={
          isMagicDashboardV2Enabled ? RTO_REDUCTION_SETUP_ROUTE_V2 : '/magic/settings/rto-settings'
        }
      >
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
