import useOnClickOutside from 'common/hooks/useOnClickOutside';
import { useResizeLayout } from 'common/hooks/useResizeLayout';
import useDebounce from 'common/utils/useDebounce';
import Fuse from 'fuse.js';
import { fetchEnrollmentStatus as fetchEnrollmentStatusFn } from 'merchant/reducers/bundlePricing';
import { fetchFeatureByName as fetchFeatureByNameFn } from 'merchant/reducers/config';
import {
  fetchMerchantInstruments as fetchMerchantInstrumentsFn,
  fetchRequestedInstruments as fetchRequestedInstrumentsFn,
  setLoading as setLoadingFn,
} from 'merchant/reducers/instrumentRequests';
import { fetchMerchantWebsiteDetails as fetchMerchantWebsiteDetailsFn } from 'merchant/reducers/websitecompliance';
import { showNotification as showNotificationFn } from 'merchant_common/reducers/notifications';
import React, { useEffect, useRef, useState } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import type { RouteComponentProps, WithRouterProps } from 'common/deprecated/RouteComponentProps';
import { bindActionCreators } from 'redux';
import ProductListing from './components/ProductListing';
import SearchBar from './components/SearchBar';
import { POPULAR_PRODUCTS } from './constants/SearchProducts';
import { CommonStateProps, ProductType, UniversalSearchPropInterface } from './typings';
import {
  feature,
  getEligibleProductsForMerchants,
  handleTestModeVisibility,
  multiKeyOptions,
  options,
  trackSearchTypeInitiated,
  TRACK_TYPE_DEBOUNCE_DURATION,
} from './utils';
import { entitySearch } from './utils/EntitySearch';
import { getProductSearchResults } from './utils/productSearch';
import { useI18Service } from 'common/i18';
import { useSplitzService } from 'common/splitz';

type UniversalSearchProps = RouteComponentProps & UniversalSearchPropInterface & WithRouterProps;

const UniversalSearch = ({
  isMobile,
  history,
  user,
  mode,
  instruments,
  isInstrumentLoading,
  websiteSectionDetailsData,
  featureStatusConfig,
  fetchFeatureByNameFn: fetchFeatureByName,
  fetchMerchantInstrumentsFn: fetchMerchantInstruments,
  fetchRequestedInstrumentsFn: fetchRequestedInstruments,
  setLoadingFn: setInstrumentsLoading,
  showNotificationFn: showNotification,
  fetchMerchantWebsiteDetailsFn: fetchMerchantWebsiteDetails,
  fetchEnrollmentStatus,
  enrollmentStatus,
  isRTUXHomepage,
}: UniversalSearchProps): JSX.Element => {
  const haveIndexedApiBasedItems = useRef(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState<{
    isPopular: boolean;
    products: ProductType[];
  }>({
    isPopular: true,
    products: POPULAR_PRODUCTS,
  });
  const [isFocussed, setIsFocussed] = useState<boolean>(false);
  const inputRef = useRef(null);
  const searchContainerRef = useRef(null);
  const listingRef = useRef(null);
  const fuseSearch = useRef<any>({});
  const isDeviceInBreakpoint = useResizeLayout({ innerWidth: 930 });
  const { isConfigTagEnabled } = useI18Service();
  const { abExperiments } = useSplitzService();

  const debouncedTrackCall = useDebounce(trackSearchTypeInitiated, TRACK_TYPE_DEBOUNCE_DURATION);

  useOnClickOutside([searchContainerRef, listingRef], (): void => {
    setIsFocussed(false);
  });

  const fetchAllInstruments = async (): Promise<void> => {
    await Promise.all([
      setInstrumentsLoading(),
      fetchMerchantInstruments(),
      fetchRequestedInstruments(),
    ]).catch((errors) => {
      showNotification({
        type: 'error',
        message: errors[0] || 'Something went wrong',
      });
    });
  };

  const setUpIndexing = ({ products }): void => {
    fuseSearch.current.query = new Fuse(products, options);
    fuseSearch.current.multiKey = new Fuse(products, multiKeyOptions);
  };

  useEffect((): void => {
    fetchAllInstruments();
    if (user.isBundlePricingEnabled) fetchEnrollmentStatus();
    if (!featureStatusConfig.data?.hasOwnProperty(feature)) {
      fetchFeatureByName({ userId: user.id, feature });
    }
    const {
      data: websiteSectionData,
      error,
      loading: isDetailsLoading,
    } = websiteSectionDetailsData;
    if (!Object.keys(websiteSectionData).length && !error && !isDetailsLoading) {
      if (user.isWebsiteComplianceFlowEnabled) fetchMerchantWebsiteDetails();
    }
  }, []);

  // We have two types of products one with visibility depends upon api and other without api data.
  // We separated two useEffect 1st one called on mount and based on user object created the product list
  // as soon as merchant mounts.
  // parallely some apis will fetch data and after api completion we fetch other set of data which is
  // dependent on api response and index that also in fuse in 2nd useEffect.
  // we are not blocking entire data just to wait for api in order to improve merchant experience.

  useEffect((): void => {
    const products = getEligibleProductsForMerchants(
      {
        user,
        mode,
        instruments,
        websiteSectionDetailsData,
        allowCFBInternational: featureStatusConfig.data?.[feature],
        hasEnrolled: enrollmentStatus.hasEnrolled,
        extraConfig: { abExperiments, isConfigTagEnabled },
      },
      true,
    );
    setUpIndexing({ products });
  }, []);

  useEffect((): void => {
    const { data: featureData, loading: isFeatureLoading } = featureStatusConfig;
    if (
      !haveIndexedApiBasedItems.current &&
      !isInstrumentLoading &&
      !isFeatureLoading &&
      !enrollmentStatus.loading &&
      !websiteSectionDetailsData.loading
    ) {
      const products = getEligibleProductsForMerchants(
        {
          user,
          instruments,
          mode,
          websiteSectionDetailsData,
          allowCFBInternational: featureData?.[feature],
          hasEnrolled: enrollmentStatus.hasEnrolled,
          extraConfig: { abExperiments, isConfigTagEnabled },
        },
        false,
      );
      if (products.length) {
        setUpIndexing({ products });
        haveIndexedApiBasedItems.current = true;
      }
    }
  }, [
    isInstrumentLoading,
    instruments,
    mode,
    user,
    websiteSectionDetailsData,
    featureStatusConfig,
    enrollmentStatus.loading,
    enrollmentStatus.hasEnrolled,
  ]);

  const performSearch = (searchQuery, fuseSearch, isSearchv2Phase1Enabled) => {
    let isPopular = true;
    let products = POPULAR_PRODUCTS;

    if (searchQuery.length >= 3 && fuseSearch.current) {
      const entitySearchResults = isSearchv2Phase1Enabled
        ? entitySearch(searchQuery)
        : { success: false, results: [] };
      isPopular = false;

      if (entitySearchResults.success) {
        products = entitySearchResults.results;
      } else {
        const productSearchResults = getProductSearchResults({
          FuseClient: fuseSearch.current,
          query: searchQuery,
        });
        products = [...productSearchResults, ...entitySearchResults.results];
      }
    }

    return { isPopular, products };
  };

  // main useEffect
  useEffect(() => {
    if (searchQuery.length) {
      const { isPopular, products } = performSearch(
        searchQuery,
        fuseSearch,
        user.isSearchv2Phase1Enabled,
      );
      setSearchResults({ isPopular, products });
      debouncedTrackCall({
        queryTyped: searchQuery,
        optionSet: products.length ? 1 : 0,
        optionSetTotal: products.length,
      });
    } else {
      // Incase nothing is present in searchbar, we show only popular products
      setSearchResults({
        isPopular: true,
        products: POPULAR_PRODUCTS,
      });
    }
  }, [searchQuery]);

  useEffect(() => {
    if (mode === 'test' && !isMobile) {
      handleTestModeVisibility(isFocussed);
    }
  }, [isFocussed, mode, isMobile]);

  const commonProps: CommonStateProps = {
    setSearch: setSearchQuery,
    setFocussed: setIsFocussed,
    isDeviceInBreakpoint,
    show: isFocussed,
    searchQuery,
  };

  return (
    <div ref={searchContainerRef}>
      <SearchBar ref={inputRef} isRTUXHomepage={isRTUXHomepage} {...commonProps} />
      <ProductListing
        ref={listingRef}
        history={history}
        isMobile={isMobile}
        searchResults={searchResults}
        {...commonProps}
      />
    </div>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      setLoadingFn,
      fetchMerchantInstrumentsFn,
      fetchRequestedInstrumentsFn,
      showNotificationFn,
      fetchFeatureByNameFn,
      fetchMerchantWebsiteDetailsFn,
      fetchEnrollmentStatus: fetchEnrollmentStatusFn,
    },
    dispatch,
  );

const mapStateToProps = (state) => ({
  isMobile: state.app.isMobileResolution,
  user: state.session.user,
  mode: state.session.mode,
  instruments: state.instrumentRequests.pg,
  isInstrumentLoading: state.instrumentRequests.loading,
  websiteSectionDetailsData: state.websiteCompliance.websiteSectionDetailsData,
  featureStatusConfig: state.config.featureStatusConfig,
  enrollmentStatus: state?.bundlePricing?.enrollmentStatus || {},
});

export default withRouter<UniversalSearchProps>(
  connect(mapStateToProps, mapDispatchToProps)(UniversalSearch),
);
