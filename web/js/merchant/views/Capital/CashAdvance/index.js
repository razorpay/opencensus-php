import React, { Component } from 'react';
import { Redirect } from 'react-router-dom';
import { connect } from 'react-redux';
import { openModal } from 'merchant_common/reducers/modals';
import Onboarding from './onboarding';
import axios from 'axios';
import {
  fetchWithdrawalConfiguration,
  fetchWithdrawals,
  fetchFunctionalWithdrawalConfigByMerchantID,
} from 'merchant/reducers/capital/withdrawals';
import { CASH_ADVANCE_BASE_URL, CASH_ADVANCE_SECTIONS } from './constants';

@connect(
  (state) => ({
    user: state.session.user,
    withdrawalConfigurationDetails: state.withdrawals.withdrawalConfiguration,
    list: state.withdrawals.list,
  }),
  {
    openModal,
    fetchWithdrawals,
    fetchWithdrawalConfiguration,
    fetchFunctionalWithdrawalConfigByMerchantID,
  },
)
class WithdrawalsRoot extends Component {
  state = {
    // eslint-disable-next-line react/no-unused-state
    leadGenerated: false,
  };

  gaEventDispatcher = (eventObject) => {
    const { state: { eventCategory = null } = {} } = this.props.location;
    // eslint-disable-next-line dot-notation
    eventObject['eventCategory'] = eventCategory ? eventCategory : 'Dashboard CA - Apply';
    window.rzpAnalytics?.(eventObject);
  };

  componentDidMount() {
    const { user } = this.props;

    // fetchSeedData();
    const hasWithdrawFeature = user.isWithdrawFeatureEnabled;

    if (hasWithdrawFeature) {
      this.fetchWithdrawalConfiguration();
      this.props.fetchWithdrawals({
        order_by: 'CREATED_AT',
        order_direction: 'desc',
        reference: [
          {
            reference_id: this.props.user.current,
            reference_type: 'OWNER_ID',
          },
        ],
        limit: 20,
      });
    }

    this.gaEventDispatcher({
      eventAction: 'Flash Credit Tab',
      eventLabel: `${this.props.user.current} | loc_stage_1`,
    });
  }

  fetchWithdrawalConfiguration = () => {
    const { fetchFunctionalWithdrawalConfigByMerchantID, user } = this.props;

    fetchFunctionalWithdrawalConfigByMerchantID({
      owner_id: user.current,
      owner_type: 'RZP_MERCHANT',
    });
  };

  static createCapitalFDTicket = (content, user) => {
    const TSYS_AUTH_TOKEN = '4d482bcf908b56771a86db388bae8ee7639b0f81';
    const apiUrl = 'https://support-tsa.razorpay.com/api/fd/ticket/create';

    return axios({
      method: 'post',
      baseURL: apiUrl,
      headers: {
        sendImmediately: true,
        Authorization: `Basic ${TSYS_AUTH_TOKEN}`,
        'Content-Type': 'application/json',
      },
      data: {
        priority: 3,
        email: user.email,
        phone: user.contact_mobile,
        subject: `Merchant[${user.current}] - Line Of Credit`,
        description: content,
        env: 'capital',
        custom_fields: {
          cf_requester_category: 'Merchant',
          cf_requestor_subcategory: 'Other',
          cf_merchant_id: user.current,
        },
      },
    }).then((res) => {
      return res;
    });
  };

  onRaiseRequest = () => {
    this.setState({
      // eslint-disable-next-line react/no-unused-state
      leadGenerated: true,
    });
  };

  render() {
    const {
      user,
      withdrawalConfigurationDetails: { loading: configLoading, error: wcError },
      list,
    } = this.props;

    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;

    const hasLOCStage2Feature = user.isCashAdvanceStage2Enabled;
    const hasWithdrawFeature = user.isWithdrawFeatureEnabled;
    const isLOSEnabled = user.isLOSEnabled;
    const isLOCEnabled = user.isLOCEnabled;

    const hasWC = !!withdrawalConfigurationDetails;

    if (list.loading || configLoading)
      return (
        <div className="spinner center">
          <div className="double-bounce1" />
          <div className="double-bounce2" />
        </div>
      );

    const OnboardingSection = (
      <Onboarding
        leadGenerated={true}
        hasLOCStage2Feature={true}
        hasWithdrawalConfiguration={hasWC}
        createFDTicket={WithdrawalsRoot.createCapitalFDTicket}
        onRaiseRequest={this.onRaiseRequest}
        withdrawalConfiguration={withdrawalConfigurationDetails}
        trackGA={this.gaEventDispatcher}
      />
    );

    if (hasWithdrawFeature) {
      if (wcError) {
        return 'Error while loading WC.';
      }
      return <Redirect to={`${CASH_ADVANCE_BASE_URL}${CASH_ADVANCE_SECTIONS.OVERVIEW}`} />; // nosemgrep : https://semgrep.dev/s/w48P
    } else if (hasLOCStage2Feature) {
      return OnboardingSection;
    } else if (isLOSEnabled && isLOCEnabled) {
      return <Redirect to={`${CASH_ADVANCE_BASE_URL}apply`} />; // nosemgrep : https://semgrep.dev/s/w48P
    } else {
      return <Redirect to="/" />;
    }
  }
}

export default WithdrawalsRoot;
