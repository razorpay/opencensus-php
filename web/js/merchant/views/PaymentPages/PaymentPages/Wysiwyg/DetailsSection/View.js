import React from 'react';
import { connect } from 'react-redux';

import ShowWhen from 'merchant/components/ShowWhen';
import { updateData } from 'merchant/reducers/wysiwyg';

import Description from './Description';
import DonationGoalTracker from './DonationGoalTracker';
import Share from './Share';
import Support from './Support';
import Terms from './Terms';
import Title from './Title';

class View extends React.PureComponent {
  updateData({ target }) {
    const { name } = target;
    let { value } = target;

    if (target.type === 'checkbox') {
      value = Number(target.checked); // Convert to 1 / 0
    }

    const dataToUpdate = {
      [name]: value,
    };

    if (['allow_social_share', 'goal_tracker'].indexOf(target.name) > -1) {
      // Fields with settings
      this.props.updateData({ settings: { ...dataToUpdate } });
    } else {
      this.props.updateData(dataToUpdate);
    }
  }

  updateData = this.updateData.bind(this);

  render() {
    const { paymentPageEntity, isPageDirty, user, isBatchPaymentPages } = this.props;

    if (!paymentPageEntity) {
      return null;
    }

    if (paymentPageEntity.id && typeof paymentPageEntity.title === 'undefined') {
      return (
        <div className="spinner-container">
          <div className="spin-btn large visible" />
        </div>
      );
    }

    const settings = paymentPageEntity.settings || {};

    return (
      <div className="details-container">
        <div id="description-details">
          <Title
            title={paymentPageEntity.title}
            key={paymentPageEntity.id ? `${paymentPageEntity.id}-title` : 'title'}
            updateData={this.updateData}
          />
          <ShowWhen additionalCondition={() => !isBatchPaymentPages}>
            <DonationGoalTracker
              goal_tracker={paymentPageEntity.settings.goal_tracker}
              updateData={this.updateData}
            />
          </ShowWhen>
          <Description
            description={paymentPageEntity.description}
            isPageDirty={isPageDirty}
            key={paymentPageEntity.id ? `${paymentPageEntity.id}-description` : 'description'}
            updateData={this.updateData}
          />
        </div>

        <Share allowSocialShare={settings.allow_social_share} updateData={this.updateData} />

        <Support
          support_contact={paymentPageEntity.support_contact}
          support_email={paymentPageEntity.support_email}
          supportPhoneRef={this.props.supportPhoneRef}
          supportEmailRef={this.props.supportEmailRef}
          updateData={this.updateData}
          user={user}
        />

        <Terms
          terms={paymentPageEntity.terms}
          updateData={this.updateData}
          merchantName={user.billing_label || user.name}
        />
      </div>
    );
  }
}

export default connect(
  (state) => ({
    user: state.session.user,
    paymentPageEntity: state.wysiwyg.paymentPageEntity,
    isPageDirty: state.wysiwyg.isPageDirty,
  }),
  {
    updateData,
  },
)(View);
