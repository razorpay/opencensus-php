import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import rTracking from 'react-tracking';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import { fetchIsAdminAsMerchant } from 'merchant/reducers/profile';
import { getCountryOnboardingUrl } from 'merchant/utils/urls';
import { compose } from 'redux';

class I18nOnboardingAnnouncement extends Component {
  constructor(props) {
    super(props);
    this.state = {};
  }

  sendL2StartEvent = () => {
    const isL1Submitted = this.props.user?.instantActivation?.isL1Submitted;
    if (isL1Submitted) {
      analyticsTrack({
        objectName: 'L2 Start',
        actionName: 'form fill initiated',
        screen: 'home page',
        properties: {
          clickSource: 'form submission popup',
          ...getCommonSegmentProperties(),
          milestone: 'L2 Start',
        },
      });
    }
  };

  componentDidMount() {
    const { user, isAdminAsMerchant, fetchIsAdminAsMerchant } = this.props;

    // Adding admin check call only for SG
    if (user.country_code === 'SG') {
      const { loading, error } = isAdminAsMerchant;
      if (loading && error === null) fetchIsAdminAsMerchant();
    }
  }

  i18nKycBannerChecks = (props) => {
    const { user, isAdminAsMerchant } = props;
    if (!user?.merchant?.activated && user?.activation_form_milestone === 'L1') {
      if (user?.country_code === 'MY') {
        return true;
      }
      if (user?.country_code === 'SG') {
        return !!isAdminAsMerchant?.data; // Banner for SG is shown only if the merchant is an admin
      }
    }
    return false;
  };

  getBannerContentByCountry = (countryCode) => {
    switch (countryCode) {
      // Add more country based content here
      default:
        return {
          theme: 'warning',
          title: 'Complete KYC details',
          ctaText: 'Complete KYC',
          announcementInfo:
            'Please submit your KYC details to get your account activated and start accepting payments ',
        };
    }
  };

  render() {
    const { user } = this.props;

    // Short circuit if the banner is not applicable for the country
    if (!this.i18nKycBannerChecks(this.props)) {
      return null;
    }

    const bannerContent = this.getBannerContentByCountry(user?.country_code);

    // Banner content
    const content = (
      <div className="announcement-container">
        <div className="announcement-info">{bannerContent.announcementInfo}</div>
        <div className="big-circle-seprator" />
        <Link
          to={getCountryOnboardingUrl(user?.country_code)}
          onClick={() => this.sendL2StartEvent()}
        >
          {bannerContent.ctaText}
        </Link>
      </div>
    );

    return (
      <AnnouncementBanner
        title={bannerContent.title}
        theme={bannerContent.theme}
        bannerKey="i18n-onboarding-announcement"
        canBeClosed={false}
        shouldShowTnCBannerForAxis={false}
        card_id="i18n-onboarding-announcement"
      >
        {content}
      </AnnouncementBanner>
    );
  }
}

export default compose(
  connect(
    (state) => ({
      isAdminAsMerchant: state.profile.isAdminAsMerchant,
    }),
    { fetchIsAdminAsMerchant },
  ),
  rTracking(() => window.rzpQ.component('I18nOnboardingAnnouncement')),
)(I18nOnboardingAnnouncement);
