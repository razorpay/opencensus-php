import EnableConfirmModal from './EnableConfirmModal';
import { connect } from 'react-redux';
import { updateFeatures } from 'merchant/reducers/config';
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchUser } from 'merchant/reducers/session';
import { compose } from 'redux';
import Rtracking from 'react-tracking';
import track from './track';
import { updateWidgetStatus } from 'merchant/reducers/affordability/affordabilityWidget';

const EnableWidgetButton = ({ enabled, openModal, closeModal, ...props }) => {
  const {
    affordability,
    updateFeatures,
    user,
    showNotification,
    onEnable,
    updateWidgetStatus,
    mode,
  } = props;
  const { pricing, trial_period_in_days } = affordability;

  const handleWidgetEnablement = async () => {
    const data = {
      features: {
        affordability_widget_set: true,
        affordability_widget: true,
      },
      should_sync: 0,
    };

    try {
      const response = await updateFeatures(data, user.current);

      if (response) {
        showNotification({
          type: 'success',
          message: 'Affordability Widget is enabled',
        });
        onEnable();
        closeModal();
        if (props.source === 'others') {
          updateWidgetStatus(true);
          props.history.replace('/affordability');
        }
      }
    } catch (err) {
      showNotification({
        type: 'error',
        message: err.errors,
      });
    }
  };

  const toggleWidgetEnablement = (source) => {
    openModal({
      size: 'large',
      component: (
        <EnableConfirmModal
          onConfirm={handleWidgetEnablement}
          pricing={pricing}
          context="submit"
          closeModal={closeModal}
          source={source}
          trialDays={trial_period_in_days}
          user={user}
          mode={mode}
          showNotification={showNotification}
        />
      ),
    });
  };

  const handleEnableWidget = () => {
    toggleWidgetEnablement(props.source);
    const sourceSetup = `${props.source === 'others' ? 'native' : props.source}_set_up_page`;
    track.enableWidget(sourceSetup);
  };

  if (enabled) {
    return (
      <button className="Button--primary btn-lg btn-feedback" disabled={true}>
        Enabled
      </button>
    );
  }
  return (
    <button onClick={handleEnableWidget} className="Button--primary btn-lg btn-primary">
      Enable Widget
    </button>
  );
};

export default compose(
  // eslint-disable-next-line babel/new-cap
  Rtracking(() => window.rzpQ.component('EnableWidgetButton')),
  connect(
    (state) => {
      return {
        user: state.session.user,
        mode: state.session.mode,
      };
    },
    {
      fetchUser: () => fetchUser(), // TODO: import fetchUser is not working
      updateFeatures,
      updateWidgetStatus,
      showNotification,
    },
  ),
)(EnableWidgetButton);
