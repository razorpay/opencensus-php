import React, { Component } from 'react';
import { connect } from 'react-redux';
import moment from 'moment';

import { showNotification } from 'rzp/modules/notifications';
import { customRangeText } from 'rzp/ui/DateRangePicker';
import {
  oldestTransactionQuery,
  getDefaultPaymentFilter,
  platformGroupingVals,
  groupByPlatform,
  OTHERS,
} from 'rzp/utils/pokedex';
import LocalStorageService from 'rzp/utils/localStorage';
import debounce from 'rzp/utils/debounce';

import * as HomeActions from 'merchantLA/modules/home';
import { fetch } from 'merchantLA/modules/pokedex';
import { fetchTransfers } from 'merchantLA/modules/collection';
import {
  API_ERROR,
  API_INVALID_RESP,
  isMobileDevice,
} from 'merchantLA/components/Home/data';

import {
  trackError,
  trackDatesChange,
  trackPlatformAnalyticsHidden,
} from './ga';
import Desktop from './Desktop';
import Mobile from './Mobile';

const dateRangePresets = [
    ['Past 7 Days', -7, 'days'],
    ['Past 30 Days', -30, 'days'],
    ['Past 90 Days', -90, 'days'],
    ['All Time', -10, 'years'],
  ],
  defaultPreset = 1;

const getPreviousDates = ({ startDate, endDate }) => {
  const diff = endDate.diff(startDate);

  return {
    startDate: startDate.clone().subtract(diff, 'ms'),
    endDate: endDate
      .clone()
      .subtract(1, 'day')
      .subtract(diff, 'ms')
      .endOf('day'),
  };
};

const bodyClass = ' analytics-v2-active';

// used to show titles for sections and also GA
const keymetricsSectionTitle = 'Transactions Overview',
  trafficSectionTitle = 'Traffic split on platforms',
  recentActivityTitle = 'Recent Activity';

@connect(
  state => {
    return {
      user: state.session.user,
      mode: state.session.mode,
      current_balance: state.home.current_balance,
    };
  },
  {
    ...HomeActions,
    showNotification,
    fetchTransfers,
  }
)
export default class HomeContainer extends Component {
  constructor(props) {
    super(props);

    // recording new analytics interactions in hotjar
    if (typeof window.hj === 'function') {
      window.hj('trigger', 'new_analytics');
      window.hj('tagRecording', ['new_analytics']);
    }

    let endDate = moment().endOf('day'),
      startDate = endDate.clone().startOf('day');

    startDate.add(...dateRangePresets[defaultPreset].slice(1));

    const { user, mode, isAdmin } = props,
      // onboarding card is shown if this is present in localstorage
      onboardingCardToken = 'show_onboarding_card',
      // onboarding card first step is shown if this is present in localstorage
      firstStepToken = 'onboarding_first_step';

    // tokens particular for the current merchant
    this.onboardingBannerToken = `${onboardingCardToken}--${user.current}`;
    this.firstStepToken = `${firstStepToken}--${user.current}`;

    /*
     * Earlier , the tokens apply at browser level, if old tokens are present
     * converting them specific to the merchants the current user can switch to
     */
    if (LocalStorageService.getItem(onboardingCardToken)) {
      Object.keys(user.merchants).forEach(key => {
        LocalStorageService.setItem(`${onboardingCardToken}--${key}`, 'true');
      });

      LocalStorageService.removeItem(onboardingCardToken);
    }

    if (LocalStorageService.getItem(firstStepToken)) {
      Object.keys(user.merchants).forEach(key => {
        LocalStorageService.setItem(`${firstStepToken}--${key}`, 'true');
      });

      LocalStorageService.removeItem(firstStepToken);
    }

    const hasAccessToOnboardingBanner = (this.hasAccessToOnboardingBanner =
      ['manager', 'owner', 'admin'].indexOf(user.role) >= 0);

    const showOnboardingBanner =
        hasAccessToOnboardingBanner &&
        LocalStorageService.getItem(this.onboardingBannerToken),
      showOnboardingBannerFirstStep = LocalStorageService.getItem(
        this.firstStepToken
      );

    this.state = {
      startDate,
      endDate,
      oldestTransactionDate: {
        value: null,
        loading: false,
        error: '',
        ...getPreviousDates({ startDate, endDate }),
      },
      isMobile: isMobileDevice(),
      dateRangePresets,
      showGroupingByPtfm: false,
      scrollAmountToStickHeader: 0,
      expandOnboardingBanner: showOnboardingBanner, // used for transition
      showOnboardingBanner,
      showOnboardingBannerFirstStep,
      // payments is used to change content in the integration step
      transfers: {
        loading: true,
        items: [],
      },
    };

    /*
     * If token not present to show the banner,
     * Need to show the banner until the user integrates in live mode
     * which we can check by checking his live transactions
     *
     * If the user is in live mode, we make fetchAll payments in
     * RecentActivity component, which will be done using `onFetchTransfers`
     * below
     */
    if (hasAccessToOnboardingBanner && !showOnboardingBanner) {
      if (!user.isActivated) {
        this.state = {
          ...this.state,
          showOnboardingBanner: true,
          showOnboardingBannerFirstStep: true,
          expandOnboardingBanner: true,
        };

        LocalStorageService.setItem(this.onboardingBannerToken, 'true');
        LocalStorageService.setItem(this.firstStepToken, 'true');
      } else if (mode !== 'live') {
        this.props.fetchTransfers({ mode: 'live' }).then(data => {
          data = data.data;

          if (data && data.items && data.items.length === 0) {
            this.setShowOnboardingBanner();
          }
        });
      }
    }

    this.oldestTxnReqId = 0;
    this.onDatesChange = this.onDatesChange.bind(this);
    this.onFetchTransfers = this.onFetchTransfers.bind(this);
    this.setScrollAmountToStickHeader = this.setScrollAmountToStickHeader.bind(
      this
    );
    this.onHideOnboardingBanner = this.onHideOnboardingBanner.bind(this);
    this.onFirstStepClose = this.onFirstStepClose.bind(this);
    this.onExtraContentMount = this.onExtraContentMount.bind(this);
    this.onResize = debounce(this.onResize.bind(this), 500);
  }

  onExtraContentMount(node) {
    this.extraContent = node;
  }

  fetchTxnsGroupedByPlatform() {
    // need to figureout whether we should show group by platform
    // or not

    const { startDate, endDate } = this.state,
      { isAdmin, analyticsFetch } = this.props;

    const query = {
      filters: {
        default: [getDefaultPaymentFilter(startDate.unix(), endDate.unix())],
      },
      aggregations: {
        records: {
          agg_type: 'count',
          details: {
            index: 'payments',
            group_by: platformGroupingVals,
          },
        },
      },
    };

    return (analyticsFetch || fetch)(query, this.props.mode)
      .then(data => {
        if (!data.success) {
          return API_ERROR;
        }

        if (!data.data || !data.data.records) {
          return API_INVALID_RESP;
        }

        data = groupByPlatform(data.data.records.result);

        return data;
      })
      .catch(err => {
        console.error(err);

        return API_ERROR;
      })
      .then(data => {
        if (data.error) {
          trackError(`While Fetching Txns Grouped by Ptfm`);

          return this.props.showNotification({
            type: 'error',
            message: data.error,
            hidePrevious: true,
          });
        }

        if (!isAdmin) {
          const platforms = Object.keys(data);

          // if we do not get platforms for given daterange
          // do not show grouping
          if (platforms.length === 0) {
            return;
          }

          let grandTotal = 0;

          const totalByPlatform = platforms.reduce((group, platform) => {
            group[platform] = data[platform].reduce((sum, entry) => {
              return sum + entry.value;
            }, 0);

            grandTotal += group[platform];

            return group;
          }, {});

          // if the txn count of platforms for given daterange
          // do not show grouping
          if (grandTotal === 0) {
            return;
          }

          const ratio = (totalByPlatform[OTHERS] || 0) / grandTotal;

          // if `Others` platform count is greater than 30%
          // do not show grouping
          if (ratio > 0.3) {
            trackPlatformAnalyticsHidden(ratio * 100);
            return;
          }
        }

        // this will show group by platform dropdowns and also
        // traffic graph
        this.setState({
          showGroupingByPtfm: true,
        });
      });
  }

  fetchOldestTransactionDate() {
    let { oldestTransactionDate, dateRangePresets } = this.state;

    const { onFirstTxnDate, analyticsFetch } = this.props;

    var oldestTxnReqId = ++this.oldestTxnReqId;

    oldestTransactionDate = { ...oldestTransactionDate };

    oldestTransactionDate.error = '';
    oldestTransactionDate.loading = true;

    this.setState({
      oldestTransactionDate: { ...oldestTransactionDate },
    });

    return (analyticsFetch || fetch)(oldestTransactionQuery, this.props.mode)
      .then(data => {
        if (oldestTxnReqId !== this.oldestTxnReqId) {
          return null;
        }

        if (!data.success) {
          return API_ERROR;
        }

        if (!data.data || !data.data.records) {
          return API_INVALID_RESP;
        }

        const records = data.data.records.result[0],
          value = records && records.created_at;

        return { value };
      })
      .catch(err => {
        console.error(err);

        return API_ERROR;
      })
      .then(data => {
        oldestTransactionDate.loading = false;

        if (!data || data.error) {
          if (data.error) {
            oldestTransactionDate.error = data.error;

            trackError(`While Fetching Oldest txn date`);

            this.props.showNotification({
              type: 'error',
              message: data.error,
              hidePrevious: true,
            });
          }

          this.setState({
            oldestTransactionDate,
          });

          return onFirstTxnDate && onFirstTxnDate();
        }

        const presetsLastIndex = dateRangePresets.length - 1,
          presetsLastItem = dateRangePresets[presetsLastIndex];

        // updates All Time present in daterange picker
        dateRangePresets = [...dateRangePresets];

        dateRangePresets.splice(presetsLastIndex, 1, [
          presetsLastItem[0],
          -(moment().unix() - data.value),
          'seconds',
        ]);

        this.setState({
          oldestTransactionDate: {
            ...oldestTransactionDate,
            value: data.value,
          },
          dateRangePresets,
        });

        return onFirstTxnDate && onFirstTxnDate(data.value);
      });
  }

  onDatesChange(startDate, endDate, selectedPreset) {
    const { oldestTransactionDate } = this.state;

    this.setState({
      startDate,
      endDate,
      oldestTransactionDate: {
        ...oldestTransactionDate,
        ...getPreviousDates({ startDate, endDate }),
      },
    });

    if (selectedPreset.name === customRangeText) {
      trackDatesChange(startDate, endDate);
    }
  }

  componentWillMount() {
    // to style react-power-selct specific to this tab
    document.body.className += bodyClass;

    this.props.fetchCurrentBalance();
    this.fetchOldestTransactionDate();
    this.fetchTxnsGroupedByPlatform();
  }

  componentWillUnmount() {
    document.body.className = document.body.className.replace(bodyClass, '');
    window.removeEventListener('resize', this.onResize);
  }

  setScrollAmountToStickHeader() {
    const scrollAmountToStickHeader = this.extraContent
      ? this.extraContent.clientHeight
      : 0;

    this.setState({ scrollAmountToStickHeader });
  }

  onResize() {
    this.setState({
      isMobile: isMobileDevice(),
    });

    this.setScrollAmountToStickHeader();
  }

  componentDidMount() {
    this.setScrollAmountToStickHeader();

    window.addEventListener('resize', this.onResize);
  }

  onFirstStepClose() {
    this.setState(
      {
        showOnboardingBannerFirstStep: false,
      },
      () => {
        // when first step is closed, the banner height gets changes,
        // adjusting the scroll amount when the datepicker bar should stick
        // on top of the page
        this.setScrollAmountToStickHeader();
      }
    );

    LocalStorageService.removeItem(this.firstStepToken);
  }

  onHideOnboardingBanner() {
    this.setState(
      {
        expandOnboardingBanner: false,
      },
      () => {
        this.setState({
          showOnboardingBanner: false,
        });

        this.setScrollAmountToStickHeader();
      }
    );

    LocalStorageService.removeItem(this.onboardingBannerToken);
  }

  setShowOnboardingBanner() {
    this.setState(
      {
        showOnboardingBanner: true,
        showOnboardingBannerFirstStep: true,
      },
      () => {
        this.setState(
          {
            expandOnboardingBanner: true,
          },
          () => {
            this.setScrollAmountToStickHeader();
          }
        );
      }
    );

    LocalStorageService.setItem(this.onboardingBannerToken, 'true');
    LocalStorageService.setItem(this.firstStepToken, 'true');
  }

  onFetchTransfers(data) {
    const { user, mode } = this.props;

    const items = (data && data.items) || [];

    const { showOnboardingBanner } = this.state;

    this.setState({
      transfers: {
        loading: false,
        items,
      },
    });

    /*
     * When fetched payments in live mode, using recent activity component
     * we use it to show the banner , if there are no trasaction
     */
    if (user.isActivated && mode === 'live') {
      // show hotjar if number of transfers is greater than 50
      if (items.length > 50) {
        document.body.className += ' show-hotjar-poll';
      }

      if (
        this.hasAccessToOnboardingBanner &&
        !this.state.showOnboardingBanner &&
        items.length === 0
      ) {
        this.setShowOnboardingBanner();
      }
    }
  }

  render() {
    let {
      mode,
      current_balance,
      tabsMeta,

      // following three props will be sent by admin analytics
      // - web/pokedex.js
      isAdmin,
      analyticsFetch,
      onFilterChange,
    } = this.props;

    const {
      startDate,
      endDate,
      oldestTransactionDate,
      dateRangePresets,
      showGroupingByPtfm,
      scrollAmountToStickHeader,
      showOnboardingBanner,
      expandOnboardingBanner,
      payments,
      showOnboardingBannerFirstStep,
      isMobile,
    } = this.state;

    const {
      onHideOnboardingBanner,
      onFirstStepClose,
      onDatesChange,
      onFetchTransfers,
      onExtraContentMount,
      setScrollAmountToStickHeader,
    } = this;

    const commonProps = {
      mode,
      current_balance,
      tabsMeta,
      isAdmin,
      analyticsFetch,
      onFilterChange,
      startDate,
      endDate,
      oldestTransactionDate,
      dateRangePresets,
      showGroupingByPtfm,
      scrollAmountToStickHeader,
      showOnboardingBannerFirstStep,
      expandOnboardingBanner,
      payments,
      showOnboardingBanner,
      isMobile,

      onHideOnboardingBanner,
      onFirstStepClose,
      onDatesChange,
      onFetchTransfers,
      onExtraContentMount,
      setScrollAmountToStickHeader,

      defaultPreset,
      keymetricsSectionTitle,
      recentActivityTitle,
      trafficSectionTitle,
    };

    return (
      <div class="react-root dashboard-home">
        {isMobile ? <Mobile {...commonProps} /> : <Desktop {...commonProps} />}
      </div>
    );
  }
}
