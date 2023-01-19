import { fetchFeatureByName as fetchFeatureByNameFn } from 'merchant/reducers/config';
import {
  fetchMerchantInstruments as fetchMerchantInstrumentsFn,
  fetchRequestedInstruments as fetchRequestedInstrumentsFn,
  setLoading as setLoadingFn,
} from 'merchant/reducers/instrumentRequests';
import { fetchMerchantWebsiteDetails as fetchMerchantWebsiteDetailsFn } from 'merchant/reducers/websitecompliance';
import { showNotification as showNotificationFn } from 'merchant_common/reducers/notifications';
import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import AccountAndProductSection from './sections/AccountAndProductSection';
import Profile from './sections/Profile';
import { PageLayoutContainer } from './styled';
import { AccountAndSettingsHomePropInterface, SectionCardInterface } from './typings';
import { getSectionCards } from './utils/sectionCard';

const feature = 'allow_cfb_international';

const AccountAndSettingsHome = (props: AccountAndSettingsHomePropInterface): JSX.Element => {
  const {
    isMobile,
    user,
    profile,
    mode,
    instruments,
    loading: isIntrumentLoading,
    websiteSectionDetailsData,
    featureStatusConfig,
    fetchFeatureByNameFn: fetchFeatureByName,
    fetchMerchantInstrumentsFn: fetchMerchantInstruments,
    fetchRequestedInstrumentsFn: fetchRequestedInstruments,
    setLoadingFn: setLoading,
    showNotificationFn: showNotification,
    fetchMerchantWebsiteDetailsFn: fetchMerchantWebsiteDetails,
  } = props;

  const [sections, setSections] = useState<SectionCardInterface[]>([]);

  const fetchAllInstruments = async (): Promise<void> => {
    await Promise.all([fetchMerchantInstruments(), fetchRequestedInstruments()]).catch((errors) => {
      showNotification({
        type: 'error',
        message: errors[0] || 'Something went wrong',
      });
    });
  };

  useEffect(() => {
    const { data } = featureStatusConfig;
    setLoading();
    fetchAllInstruments();
    if (!data.hasOwnProperty(feature)) {
      fetchFeatureByName({ userId: user.id, feature });
    }
  }, []);

  useEffect(() => {
    const { data, error, loading: isDetailsLoading } = websiteSectionDetailsData;
    if (!Object.keys(data).length && !error && !isDetailsLoading) {
      if (user.isWebsiteComplianceFlowEnabled) {
        fetchMerchantWebsiteDetails();
      }
    }
  }, [user, websiteSectionDetailsData]);

  useEffect(() => {
    const { data: featureData, loading: isFeatureLoading } = featureStatusConfig;
    if (!isIntrumentLoading && !isFeatureLoading) {
      const sectionCards = getSectionCards({
        user,
        instruments,
        mode,
        websiteSectionDetailsData,
        profile,
        allowCFBInternational: featureData[feature],
      });
      setSections(sectionCards);
    }
  }, [
    isIntrumentLoading,
    instruments,
    mode,
    user,
    websiteSectionDetailsData,
    profile,
    featureStatusConfig,
  ]);

  return (
    <PageLayoutContainer>
      <Profile />
      <AccountAndProductSection sections={sections} isMobile={isMobile} />
    </PageLayoutContainer>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    profile: state.profile,
    mode: state.session.mode,
    instruments: state.instrumentRequests.pg,
    loading: state.instrumentRequests.loading,
    websiteSectionDetailsData: state.websiteCompliance.websiteSectionDetailsData,
    featureStatusConfig: state.config.featureStatusConfig,
    isMobile: state.app.isMobileResolution,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      setLoadingFn,
      fetchMerchantInstrumentsFn,
      fetchRequestedInstrumentsFn,
      showNotificationFn,
      fetchFeatureByNameFn,
      fetchMerchantWebsiteDetailsFn,
    },
    dispatch,
  );
};
export default compose(connect(mapStateToProps, mapDispatchToProps))(AccountAndSettingsHome);
