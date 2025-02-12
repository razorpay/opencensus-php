import React from 'react';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import track from 'merchant/views/Affordability/AffordabilityWidget/PlanDetails/track';
import { compose } from 'redux';
import DisableWidgetConfirmModal from 'merchant/views/Affordability/components/confirm/DisableWidgetConfirm';
import { connect } from 'react-redux';
import { showNotification } from 'merchant_common/reducers/notifications';

import { updateFeatures } from 'merchant/reducers/config';
import { updateWidgetStatus } from 'merchant/reducers/affordability/affordabilityWidget';

const SettingsDropdown = (props) => {
  const { openModal, closeModal, source, user, showNotification, updateFeatures } = props;

  const handleWidgetDisablement = async () => {
    track.disableWidgetConfirm();
    const data = {
      features: {
        affordability_widget_set: false,
        affordability_widget: false,
      },
      should_sync: 0,
    };

    try {
      const response = await updateFeatures(data, user.current);
      if (response) {
        showNotification({
          type: 'success',
          message: 'Affordability Widget is disabled',
        });
        closeModal();
        props.updateWidgetStatus(false, {
          name: 'DISABLE',
          createdTime: new Date().getTime(),
        });
      }
    } catch (err) {
      showNotification({
        type: 'error',
        message: err.errors,
      });
    }
  };

  const toggleDisableModal = () => {
    track.disableWidget();
    openModal({
      size: 'xlarge',
      component: (
        <DisableWidgetConfirmModal
          onConfirm={handleWidgetDisablement}
          closeModal={closeModal}
          context="submit"
          source={source}
        />
      ),
    });
  };
  return (
    <Dropdown>
      <DropdownTrigger className="dropdown-toggle switch-modes-toggle settings-dropdown">
        <span className="btn btn-link">
          <i className="i i-settings-outline" /> Settings
        </span>
      </DropdownTrigger>
      <DropdownContent>
        <ul className="dropdown-menu switch-modes-menu nav nav-stacked">
          <li data-test="Disable Widget">
            <a onClick={toggleDisableModal}>Disable Widget</a>
          </li>
        </ul>
      </DropdownContent>
    </Dropdown>
  );
};

export default compose(
  connect(
    (state) => {
      return {
        user: state.session.user,
      };
    },
    {
      updateFeatures,
      updateWidgetStatus,
      showNotification,
    },
  ),
)(SettingsDropdown);
