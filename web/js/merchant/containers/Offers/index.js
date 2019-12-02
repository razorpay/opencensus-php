import React, { Component } from 'react';
import HeaderAction from 'common/ui/HeaderAction';
import Alert from 'common/ui/Forms/Alert';
import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import List from 'merchant/containers/Offers/List';
import { Route, Switch, NavLink } from 'react-router-dom';

export default class OfferIndex extends Component {
  render() {
    return (
      <tabbed-container>
        <header id="link-header">
          <NavLink exact to="/offers">
            Offers
          </NavLink>
        </header>

        <TestModeBanner />
        <Switch>
          <Route path="/offers">
            <content>
              <div className="content-wrapper">
                <HeaderAction>
                  <div className="btn-toolbar pull-right">
                    <ShowWhen
                      additionalCondition={user =>
                        (this.props.mode !== 'live' || !user.isRejected) &&
                        user.isAllowedEdit('offers')
                      }
                    >
                      <NavLink class="btn btn-primary" exact to="/offers/New">
                        <i className="i i-plus" />
                        <span
                          onClick={() => {
                            //@todo tracking
                            // this.props.tracking.trackEvent(
                            //   window.rzpQ.onbr().success('dash.pl_action', {
                            //     action: 'Initiate_PL_Creation',
                            //   })
                            // )
                          }}
                        >
                          Create New Offer
                        </span>
                      </NavLink>
                    </ShowWhen>
                  </div>
                </HeaderAction>
                <Alert type={status.type} message={status.message} />
                <List {...this.props} />
              </div>
            </content>
          </Route>
        </Switch>
      </tabbed-container>
    );
  }
}
