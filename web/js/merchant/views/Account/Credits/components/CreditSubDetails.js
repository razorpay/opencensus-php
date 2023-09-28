import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import moment from 'moment';

import Amount from 'common/ui/Amount';
import Spinner from 'common/ui/Spinner';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

import { fetchCreditById } from 'merchant/reducers/credits';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

@connect(
  (state) => {
    return {
      credits: state.credits.creditsData.items,
    };
  },
  { ...NotificationsActions },
)
class CreditSubDetails extends Component {
  state = {
    credit: null,
    isLoading: true,
  };
  UNSAFE_componentWillMount() {
    const creditId = this.props.id;

    fetchCreditById(creditId).then((response) => {
      if (response.success) {
        this.setState({
          credit: response.data,
          isLoading: false,
        });
      } else {
        this.props.showNotification({
          type: 'error',
          message: response.errors.join(','),
        });
      }
    });
  }
  render() {
    const { credit, isLoading } = this.state;
    const isExpired =
      !isLoading && credit.expired_at && moment().isAfter(moment(credit.expired_at, 'X'));

    return (
      <div class="content-wrapper content-sm txn-details">
        {isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              <i class="i i-link text-primary" />
              <strong>Coupon Details: {credit.campaign}</strong>
            </div>
            <div class="SliderPanel__Body">
              <div class="panel-body">
                <div class="list-group details-row-container">
                  <EntityDetailRow label="Amount Credits">
                    <strong>
                      <Amount value={credit.value} currency="INR" />
                    </strong>
                  </EntityDetailRow>
                  <EntityDetailRow
                    label="Applied on"
                    value={moment(credit.created_at, 'X').format('DD MMM YYYY, hh:mm A')}
                  />
                  <EntityDetailRow
                    label="Valid till"
                    value={
                      isExpired
                        ? 'Expired'
                        : credit.expired_at
                        ? moment(credit.expired_at, 'X').format('DD MMM YYYY, hh:mm A')
                        : 'Unlimited Validity'
                    }
                  />
                </div>
                <div>
                  <h3>Terms and Conditions:</h3>
                  <p>1. Coupon applicable only for first time users.</p>
                  <p>2. Settlement of funds is a subject to KYC acceptance of users.</p>
                  <p />
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }
}

export default withRouter(CreditSubDetails);
