import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { getCreditsData, fetchBalance } from 'merchant/modules/credits'

@connect(
  (state) => {
    return {
      creditData: credits.creditData,
      balanceData: credits.balanceData
    }
  },
  { getCreditsData, fetchBalance }
)
export default class CreditsList extends Component {
  constructor() {
    super(...arguments)
  }

  componentWillMount() {
    this.props.getCreditsData();

  /*
    $scope.balance = result.data.balance;

    // Amount and Fee Credits
    $scope.credits = result.data.credits;
    $scope.fee_credits = result.data.fee_credits;
  */

    this.props.fetchBalance().then((response)=>{
      $('.fake_hide_till_loaded').removeClass('fake_hide_till_loaded');
    }).catch((err)=>{
      $('.fake_hide_till_loaded').removeClass('fake_hide_till_loaded');
    });
  }

  render() {

    return (
      <div class='react-root'>

        <div class="bg-light lter b-b wrapper-md">
          <h1 class="m-n font-thin h3">
            Your Credits
            <spinner class="inline"></spinner>
          </h1>
        </div>

        <div class="wrapper-md profile-wrapper" ng-controller="CreditsCtrl">
          <div class="panel panel-default panel-form col-sm-10 col-sm-offset-1">
            <div class="panel-heading m-t m-b">
              Your Credits

              <small class="pull-right">
                <a href="https://docs.razorpay.com/v1/page/credits" class="highlight" target="_blank">DOCUMENTATION &nbsp;<i class="fa fa-external-link"></i></a>
              </small>
            </div>

            <div class="text-center m-t">
              <alert ng-repeat="alert in alerts.getAlerts()" type="{{alert.type}}" close="alerts.closeAlert($index)">{{alert.msg}}</alert>
            </div>
            <alert type="danger" class="text-center" ng-show="!user.current">
              Your user account is not associated at present with any active merchant account.
            </alert>
            <div class="row wrapper" ng-show="user.current">
              <div class="list-group">
                <a class="list-group-item" ng-show="credits != 0">
                  <span class="pull-right">{{credits/100|rupee}}</span>
                  Amount Credits
                </a>

                <a class="list-group-item" ng-show="fee_credits != 0">
                  <span class="pull-right">{{fee_credits/100|rupee}}</span>
                  Fee Credits
                </a>

                <!-- Credits -->
                <a href class="list-group-item" ng-init="isCreditsLogCollapsed = true">
                  <span class="pull-right">
                    <button class="btn btn-default btn-xs pull-right" ng-click="isCreditsLogCollapsed = !isCreditsLogCollapsed"
                    ng-show="creditsData.data.items.length != 0">
                      Show/Hide
                    </button>
                    <span ng-hide="creditsData.data.items.length != 0">
                      No credits Assigned
                    </span>
                  </span>
                  Credits
                </a>

                <div collapse="isCreditsLogCollapsed" class="panel-body">
                  <div class="m-t">
                    <table class="table table-striped b-light">
                      <thead>
                      <th>Id</th>
                      <th>Campaign</th>
                      <th>Type</th>
                      <th>Value</th>
                      <th>Created At</th>
                      </thead>
                      <tbody>
                      <tr ng-repeat="credit in creditsData.data.items">
                        <td>{{credit.id}}</td>
                        <td>{{credit.campaign}}</td>
                        <td>{{credit.type}}</td>
                        <td>{{credit.value/100 | rupee:"INR "}}</td>
                        <td>{{credit.created_at*1000 | date:'short'}}</td>
                      </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

              </div>
            </div>

          </div>
        </div>
      </div>
    )
  }
}
