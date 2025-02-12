import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import EnableConfirmModal from 'merchant/views/Affordability/AffordabilityWidget/Onboarding/EnableConfirmModal';
import './affordability-banner.styl';
import { updateFeatures } from 'merchant/reducers/config';
import { connect } from 'react-redux';
import { updateWidgetStatus } from 'merchant/reducers/affordability/affordabilityWidget';

import { showNotification } from 'merchant_common/reducers/notifications';
import { compose } from 'redux';
import { useEffect } from 'react';
import track from './track';

const WidgetDisabledBanner = (props) => {
  const {
    closeModal,
    openModal,
    pricing,
    user,
    updateFeatures,
    showNotification,
    trialDays,
    updateWidgetStatus,
    mode,
  } = props;

  useEffect(() => {
    track.widgetDisableBanner();
  }, []);

  const onEnableWidget = async (data) => {
    try {
      const response = await updateFeatures(data, user.current);

      if (response) {
        showNotification({
          type: 'success',
          message: 'Affordability Widget is enabled',
        });
        updateWidgetStatus(true);
        closeModal();
      }
    } catch (err) {
      showNotification({
        type: 'error',
        message: err.errors,
      });
    }
  };

  const toggleEnableModal = () => {
    openModal({
      component: (
        <EnableConfirmModal
          onConfirm={onEnableWidget}
          closeModal={closeModal}
          pricing={pricing}
          source="banner"
          trialDays={trialDays}
          user={user}
          mode={mode}
          showNotification={showNotification}
        />
      ),
      size: 'xlarge',
    });
  };

  return (
    <AnnouncementBanner className="widget-disabled-banner" title="Widget Disabled" theme="danger">
      <span className="display-inline">
        Affordability Widget is currently deactivated. Click on Enable Widget to grow your business
        again
      </span>
      <button className="btn btn-primary" onClick={toggleEnableModal}>
        Enable Widget
      </button>
    </AnnouncementBanner>
  );
};

export default compose(
  connect(
    (state) => {
      return {
        ...state,
        user: state.session.user,
        mode: state.session.mode,
      };
    },
    {
      updateFeatures,
      showNotification,
      updateWidgetStatus,
    },
  ),
)(WidgetDisabledBanner);
