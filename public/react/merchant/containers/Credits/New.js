import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { fetchCreditBalance } from 'merchant/modules/credits'
import moment from 'moment'
import Amount from 'rzp/ui/Amount'
import Spinner from 'rzp/ui/Spinner'

@connect(
  (state) => {
    return {
      errorData: state.credits.errorData,
      creditsData: state.credits.creditsData,
      balanceData: state.credits.balanceData,
      user: state.session.user
    }
  },
  { fetchCreditBalance }
)

export default class CreditsList extends Component {
  constructor() {
    super(...arguments)
  }

  componentDidMount() {
    /*
      $scope.balance = result.data.balance;

      // Amount and Fee Credits
      $scope.credits = result.data.credits;
      $scope.fee_credits = result.data.fee_credits;
    */
    if (this.props.user.current) {
      this.props.fetchCreditBalance();
    }
  }

  getContent() {
    let content;

    let { balanceData, creditsData } = this.props;

    let amountCredits = null;
    let feeCredits = null;

      // Amount Credits
    if (balanceData.credits) {
      amountCredits = (
        <div class="list-group-item">
          <Amount class="pull-right" value={balanceData.credits} />
          Amount Credits
        </div>
      );
    }
    // Fee Credits
    if (balanceData.fee_credits) {
      feeCredits = (
        <div class="list-group-item">
          <Amount class="pull-right" value={balanceData.fee_credits} />
          Fee Credits
        </div>
      );
    }

    // Credits
    let credits = null
    let creditItems = null;

    credits = (
      <a class="list-group-item">
        {creditsData.items.length ||
          <span class="pull-right">
            No credits Assigned
          </span>
        }
        Credits
      </a>
    )

    creditItems = !creditsData.items.length ? null : (
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
              {creditsData.items.map((credit, index)=> {
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

    // Content for authenticated user
    content = (
      <div class="row wrapper">
        <div class="list-group">
          {amountCredits}
          {feeCredits}
          {credits}
          {creditItems}
        </div>
      </div>
    )
    return content;
  }

  render() {
    let content = <div class="text-center"><Spinner /></div>

    let { creditsData, errorData, user } = this.props;

    if (creditsData) {
      console.log('credits data');
      content = this.getContent();
    } else if (errorData) {
      content = (
        <div class="alert alert-danger text-center">
          {errorData[0]}
        </div>
      )
    } else if (!user.current) {
      content = (
        <div class="alert alert-danger text-center">
          Your user account is not associated at present with any active merchant account.
        </div>
      )
    }

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

            {content}
          </div>
        </div>
      </div>
    )
  }
}
