import React, { Component, Fragment } from 'react';

import Group, { GroupItem } from 'common/ui/Group';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import Button from 'common/new-ui/Button';
import ShowWhen from 'merchant/components/ShowWhen';
import rTracking from 'react-tracking';

import SymbolCard from 'assets/symbols/card.svg';
import SymbolPL from 'assets/symbols/pl.svg';
import SymbolInv from 'assets/symbols/inv.svg';
import { compose } from 'redux';

class InstantActivationSuccess extends Component {
  constructor(props) {
    super(props);
    this.handleProductsView = this.handleProductsView.bind(this);
  }

  handleProductsView() {
    window.rzpQ.onbr().initiated('dash.accept_payments_popup_action', {
      action: 'View_Products',
    });
    const { onClose, showProductsModal, showTransactionsModal } = this.props;
    this.props.track.trackViewProducts();
    onClose();
    showProductsModal(() => showTransactionsModal());
  }

  render() {
    const { onClose, isKLA, track } = this.props;

    return (
      <ModalMask>
        <Modal className="transactions-helper" onClose={onClose}>
          <modal-header>
            <h1>Start accepting payments</h1>
            <p>You can accept payments from your customers using the following methods</p>
          </modal-header>
          <modal-body>
            <Group>
              {isKLA && (
                <Fragment>
                  <GroupItem>
                    <p>
                      <img src={SymbolCard} />
                    </p>
                    <p>
                      <b>Accept payments on your website</b>
                    </p>
                    <p>Integrate Razorpay onto your website. Want to know how to integrate?</p>
                    <ShowWhen
                      additionalCondition={(user) =>
                        user.isOrgAllowedFunctionality('external_links')
                      }
                    >
                      <a
                        className="Button--secondary Button active"
                        target="_blank"
                        rel="noreferrer noopener"
                        href="https://razorpay.com/docs"
                        onClick={() => {
                          track.trackIntegration();
                          onClose();
                        }}
                      >
                        Read Integration Docs
                      </a>
                    </ShowWhen>
                  </GroupItem>
                  <GroupItem className="vertical-splitter">
                    <div />
                  </GroupItem>
                </Fragment>
              )}
              <GroupItem>
                <p>
                  {/* <img src={SymbolSC} /> */}
                  <img src={SymbolPL} />
                  <img className="m-l" src={SymbolInv} />
                </p>
                <p>
                  <b>Accept payments using products</b>
                </p>
                <p>You can receive Payment through Payment Links and Invoices</p>
                <Button.Secondary onClick={this.handleProductsView}>View products</Button.Secondary>
              </GroupItem>
            </Group>
          </modal-body>
        </Modal>
      </ModalMask>
    );
  }
}

export default compose(
  rTracking(() => {
    return window.rzpQ.component('InstantActivationSuccess');
  }),
)(InstantActivationSuccess);
