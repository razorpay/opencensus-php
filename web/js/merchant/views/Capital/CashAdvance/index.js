import React, { Component } from 'react';
import { Redirect } from 'react-router-dom';
import { connect } from 'react-redux';
import { openModal } from 'merchant_common/reducers/modals';
import Onboarding from './onboarding';
import axios from 'axios';
import {
  fetchWithdrawalConfiguration,
  fetchWithdrawals,
  fetchWithdrawalConfigurationByMerchantID,
} from 'merchant/reducers/capital/withdrawals';
import Spinner from 'common/ui/Spinner';

@connect(
  state => ({
    user: state.session.user,
    withdrawalConfigurationDetails: state.withdrawals.withdrawalConfiguration,
    list: state.withdrawals.list,
  }),
  {
    openModal,
    fetchWithdrawals,
    fetchWithdrawalConfiguration,
    fetchWithdrawalConfigurationByMerchantID,
  }
)
class WithdrawalsRoot extends Component {
  state = {
    leadGenerated: false,
  };

  gaEventDispatcher = eventObject => {
    eventObject['eventCategory'] = 'Dashboard CA - Apply';
    window.rzpAnalytics(eventObject);
  };

  componentDidMount() {
    const { fetchSeedData, user } = this.props;

    // fetchSeedData();
    const hasLOCStage2Feature = user.isFlashCreditStage2Enabled;
    if (hasLOCStage2Feature) {
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
      eventLabel: `${this.props.user.current} | ${
        hasLOCStage2Feature ? 'loc_stage_2' : 'loc_stage_1'
      }`,
    });
  }

  fetchWithdrawalConfiguration = () => {
    const { fetchWithdrawalConfigurationByMerchantID, user } = this.props;
    fetchWithdrawalConfigurationByMerchantID({
      owner_id: user.current,
      owner_type: 'RZP_MERCHANT',
      status: 'ACTIVE',
      skip: 0,
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
        Authorization: 'Basic ' + TSYS_AUTH_TOKEN,
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
    }).then(res => {
      return res;
    });
  };

  onRaiseRequest = () => {
    this.setState({
      leadGenerated: true,
    });
  };

  render() {
    const {
      user,
      withdrawalConfigurationDetails: { loading: configLoading },
      list,
    } = this.props;
    const { leadGenerated } = this.state;

    const withdrawalConfigurationDetails = this.props
      .withdrawalConfigurationDetails.data;

    const hasLOCStage1Feature = user.isFlashCreditStage1Enabled;
    const hasLOCStage2Feature = user.isFlashCreditStage2Enabled;

    if (list.loading || configLoading)
      return (
        <div className="spinner center">
          <div className="double-bounce1" />
          <div className="double-bounce2" />
        </div>
      );

    const OnboardingDetails = (
      <Onboarding
        leadGenerated={leadGenerated || hasLOCStage2Feature}
        hasLOCStage2Feature={hasLOCStage2Feature}
        hasWithdrawalConfiguration={!!withdrawalConfigurationDetails}
        createFDTicket={WithdrawalsRoot.createCapitalFDTicket}
        onRaiseRequest={this.onRaiseRequest}
        withdrawalConfiguration={withdrawalConfigurationDetails}
        trackGA={this.gaEventDispatcher}
      />
    );

    return hasLOCStage2Feature ? (
      list.data && list.data.length > 0 ? (
        <Redirect to="/capital/cash-advance/withdrawals" />
      ) : (
        OnboardingDetails
      )
    ) : hasLOCStage1Feature ? (
      OnboardingDetails
    ) : (
      <Redirect to="/" />
    );
  }
}

export default WithdrawalsRoot;
