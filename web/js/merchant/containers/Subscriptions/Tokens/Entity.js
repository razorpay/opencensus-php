import { Component, Fragment } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import ContentToggler from 'rzp/ui/Toggler/ContentToggler';
import Time from 'rzp/ui/Time';
import Definition from 'rzp/ui/Definition';

import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { TokenStatusLabel } from 'merchant/components/StatusLabel';

import { fetchToken } from 'merchant/modules/token';

import { getTokenStatus } from './List';

@withRouter
@connect(state => ({ ...state.token }), { fetchToken })
export default class TokenEntityContainer extends Component {
  componentWillMount() {
    this.props.fetchToken(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchToken(nextProps.id);
    }
  }

  render() {
    const { loading: isLoading, entity, error } = this.props;
    return (
      <div class="content-wrapper content-sm txn-details">
        {isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">{entity.id}</div>
            <Alert type="error" message={error} />
            <div class="SliderPanel__Body">
              <div class="panel-body">
                <div class="list-group details-row-container">
                  {/* status of token */}
                  <EntityDetailRow label="Status">
                    <TokenStatusLabel status={getTokenStatus(entity)} />
                  </EntityDetailRow>

                  {/*  */}
                  <EntityDetailRow label="Payment Method">
                    {entity.method}
                  </EntityDetailRow>

                  <EntityDetailRow label="Customer Details">
                    <span>Customer Details not coming from api</span>
                  </EntityDetailRow>

                  <EntityDetailRow label="Created At">
                    <TimeStamps token={entity} />
                  </EntityDetailRow>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }
}

function TimeStamps({ token }) {
  const timeFormat = 'LL, hh:mm A';
  return (
    <ContentToggler>
      <span class="text-primary">
        <Time value={token.created_at} format={timeFormat} />
      </span>
      <Definition>
        <></>
        <>
          Last Used At: <Time value={token.used_at} format={timeFormat} />
        </>
      </Definition>
    </ContentToggler>
  );
}
