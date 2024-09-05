import { Link } from 'react-router-dom';
import { connect } from 'react-redux';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import Button from 'common/new-ui/Button';
import { useMagicExperiment } from 'merchant/views/MagicCheckout/utils/useMagicExperiment';

import { MAGIC_DASHBOARD_REVAMP_EXPERIMENT } from 'merchant/views/MagicCheckout/constants';
import {
  AUTOMATION_BANNER_SUBHEADING,
  AUTOMATION_TAB_LINK,
  AUTOMATION_TAB_LINK_V2,
} from 'merchant/views/MagicCheckout/CODOrdersTab/constants';

const CODAutomationBanner = ({ user }) => {
  const isMagicDashboardV2Enabled = useMagicExperiment(MAGIC_DASHBOARD_REVAMP_EXPERIMENT);
  if (!user.isMagicCODOrderAutomationEnabled) {
    return null;
  }

  return (
    <AnnouncementBanner
      title="Automate action on COD order"
      theme="primary"
      card_id="automate-magic-cod-order"
      className="automate-magic-cod-order-banner"
    >
      {AUTOMATION_BANNER_SUBHEADING}
      <Link to={isMagicDashboardV2Enabled ? AUTOMATION_TAB_LINK_V2 : AUTOMATION_TAB_LINK}>
        <Button.Primary type="button" className="btn btn-primary automate-cta">
          Automate now
        </Button.Primary>
      </Link>
    </AnnouncementBanner>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, null)(CODAutomationBanner);
