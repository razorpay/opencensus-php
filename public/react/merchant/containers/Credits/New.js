import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { getCreditsData, fetchBalance } from 'merchant/modules/credits'
import moment from 'moment'
import Amount from 'rzp/ui/Amount'

@connect(
  (state) => {
    return {
      creditsData: state.credits.creditsData,
      balanceData: state.credits.balanceData,
      user: state.session.user
    }
  },
  { getCreditsData, fetchBalance }
)
export default class CreditsList extends Component {
  constructor() {
    super(...arguments)
  }

  componentDidMount() {
    this.props.getCreditsData();

  /*
    $scope.balance = result.data.balance;

    // Amount and Fee Credits
    $scope.credits = result.data.credits;
    $scope.fee_credits = result.data.fee_credits;
  */

    this.props.fetchBalance().then((response)=>{
      $('.fake_hide_till_loaded').removeClass('fake_hide_till_loaded');
      this.forceUpdate();
    }).catch((err)=> {
      $('.fake_hide_till_loaded').removeClass('fake_hide_till_loaded');
    });
  }

  getContent() {
    let content;

    let { user, balanceData, creditsData } = this.props;

    if (user.current) {
      let amountCredits = null;
      let feeCredits = null;

      if (balanceData) {
        // Amount Credits
        if (balanceData.credits) {
          amountCredits = (
            <a class="list-group-item">
              <Amount class="pull-right" value={balanceData.credits} />
              Amount Credits
            </a>
          );
        }
        // Fee Credits
        if (balanceData.fee_credits) {
          feeCredits = (
            <a class="list-group-item">
              <Amount class="pull-right" value={balanceData.fee_credits} />
              Fee Credits
            </a>
          );
        }
      }

      // Credits
      let credits = null
      let creditItemBLock = null;

      if (creditsData) {
        credits = (
          <a class="list-group-item">
            {creditsData.data.items.length ||
              <span class="pull-right">
                No credits Assigned
              </span>
            }
            Credits
          </a>
        )

        creditItemBLock = !creditsData.data.items.length ? null : (
          <div class="panel-body">
            <div class="m-t">
              <table class="table table-striped b-light">
                <thead>
                  <tr>
                    <th>Id</th>
                    <th>Campaign</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Created At</th>
                  </tr>
                </thead>
                <tbody>
                  {creditsData.data.items.map((credit, index)=> {
                    return (
                      <tr key={index}>
                        <td>{credit.id}</td>
                        <td>{credit.campaign}</td>
                        <td>{credit.type}</td>
                        <td><Amount value={credit.value} /></td>
                        <td>{moment.unix(credit.created_at).format("DD/MM/YYYY H:mm a")}</td>
                      </tr>
                    )})
                  }
                </tbody>
              </table>
            </div>
          </div>
        )
      }
    // Content for authenticated user
      content = (
        <div class="row wrapper">
          <div class="list-group">
            {amountCredits}
            {feeCredits}
            {credits}
            {creditItemBLock}
          </div>
        </div>
      )
    } else {
      content = (
        <div class="alert alert-danger text-center">
          Your user account is not associated at present with any active merchant account.
        </div>
      )
    }
    return content;
  }

  render() {
    const content = this.getContent();
    return (
      <div class='react-root'>

        <div class="bg-light lter b-b wrapper-md">
          <h1 class="m-n font-thin h3">
            Your Credits
            <spinner class="inline"></spinner>
          </h1>
        </div>

        <div class="wrapper-md profile-wrapper">
          <div class="panel panel-default panel-form col-sm-8 col-sm-offset-2">
            <div class="panel-heading m-t m-b"
                 style={{backgroundColor: '#eaeff0'}}>
              Your Credits

              <small class="pull-right">
                <a href="https://docs.razorpay.com/v1/page/credits" class="highlight" target="_blank">
                  DOCUMENTATION &nbsp;
                  <i class="fa fa-external-link"></i>
                </a>
              </small>
            </div>

            <div class="text-center m-t">
              {/*
              <alert ng-repeat="alert in alerts.getAlerts()" type="{{alert.type}}" close="alerts.closeAlert($index)">{{alert.msg}}</alert>
              */}
            </div>
            {content}
          </div>
        </div>
      </div>
    )
  }
}
