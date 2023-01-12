import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';
import Profile from './sections/Profile';
import AccountAndProductSection from './sections/AccountAndProductSection';
import { PageLayoutContainer } from './styled';
import { getSectionCards } from './utils/sectionCard';
import { showNotification as showNotificationFn } from 'merchant_common/reducers/notifications';
import {
  fetchMerchantInstruments as fetchMerchantInstrumentsFn,
  fetchRequestedInstruments as fetchRequestedInstrumentsFn,
  setLoading as setLoadingFn,
} from 'merchant/reducers/instrumentRequests';
import Spinner from 'common/ui/Spinner';
import { fetchMerchantWebsiteDetails as fetchMerchantWebsiteDetailsFn } from 'merchant/reducers/websitecompliance';
import { fetchFeatureByName as fetchFeatureByNameFn } from 'merchant/reducers/config';
import { AccountAndSettingsHomePropInterface, SectionCardInterface } from './typings';

const feature = 'allow_cfb_international';

const AccountAndSettingsHome = (props: AccountAndSettingsHomePropInterface): JSX.Element => {
  const {
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
      {!sections?.length ? (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <AccountAndProductSection sections={sections} />
      )}
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
