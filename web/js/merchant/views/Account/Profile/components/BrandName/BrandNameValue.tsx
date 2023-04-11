import React from 'react';
import { connect } from 'react-redux';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import UpdateBillingLabel from 'merchant/views/Account/Profile/components/UpdateBillingLabel';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import {
  ACTION_QUERY_PARAM_KEY,
  UPDATE_BILLING_LABEL,
} from 'merchant/views/Account/Profile/deeplink-constants';
import { openModal } from 'merchant_common/reducers/modals';

const BrandNameValue = ({ user, openModal }) => {
  const openUpdateBillingLabelModal = () => {
    window.rzpAnalytics?.({
      eventCategory: 'Brand Name',
      eventAction: 'Edit brand name clicked',
      eventLabel: user.id,
    });

    openModal({
      size: 'med-large',
      component: <UpdateBillingLabel />,
      className: 'modal-white-background',
      queryParams: {
        [ACTION_QUERY_PARAM_KEY]: UPDATE_BILLING_LABEL,
      },
    });
  };

  return user.billing_label ? (
    <span>
      {user.billing_label}
      <a
        role="button"
        className="p-l"
        onClick={() => {
          selfServeTrackInitiate({
            selfServeAction: 'Brand Name Updated',
            page: 'Profile',
            screen: 'My Account',
          });
          analyticsTrack({
            objectName: 'Brand name edit',
            actionName: 'clicked',
            screen: 'my account',
            properties: {
              currentBrandName: user.billing_label,
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          openUpdateBillingLabelModal();
        }}
        title="Edit Billing Label"
      >
        <i className="i i-edit" />
      </a>
    </span>
  ) : (
    <a
      role="button"
      className="p-l"
      onClick={openUpdateBillingLabelModal}
      title="Set Billing Label"
    >
      Set Billing Label
    </a>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const mapDispatchToProps = {
  openModal,
};

export default connect(mapStateToProps, mapDispatchToProps)(BrandNameValue);
