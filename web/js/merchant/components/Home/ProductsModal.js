import React from 'react';
import { Link } from 'react-router-dom';

import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';

import SymbolSC from "assets/symbols/sc.svg"
import SymbolPL from "assets/symbols/pl.svg"
import SymbolINV from "assets/symbols/inv.svg"

export default ({ onClose, onBack, track }) => {
  return (
    <ModalMask>
      <Modal className="products-suite-modal" onClose={onClose}>
        <div className="product-suite">
          <h1>
            {onBack ? <i className="i i-arrow-back cursor-pointer" onClick={onBack} /> : null}

            <span>Our Product Suite</span>
          </h1>
          <p>You can start receiving payments immediately using the following products</p>
          <ul className="nav">
            {/*
            <li>
              <Link to="/virtualaccounts">
                <img src={SymbolSC} />
                <p className="text-primary">Smart Collect</p>
                <p>Get paid in virtual accounts via NEFT, RTGS</p>
                <i className="i i-chevron-right" />
              </Link>
            </li>
            */}
            <li>
              <Link
                to="/paymentpages"
                onClick={() => {
                  track.trackProductClick('Paymentpages');
                  onClose();
                }}
              >
                <img src="https://cdn.razorpay.com/static/assets/paymentpages/display_icon.svg" />
                <p className="text-primary">Payment Pages</p>
                <p>Effortlessly create personalised web pages without the need to code</p>
                <i className="i i-chevron-right" />
              </Link>
            </li>
            <li>
              <Link
                to="/paymentlinks"
                onClick={() => {
                  track.trackProductClick('Payment Links');
                  onClose();
                }}
              >
                <img src={SymbolPL} />
                <p className="text-primary">Payment Links</p>
                <p>Create & share Payment Links via SMS, Email etc</p>
                <i className="i i-chevron-right" />
              </Link>
            </li>
            <li>
              <Link
                to="/invoices"
                onClick={() => {
                  track.trackProductClick('Invoices');
                  onClose();
                }}
              >
                <img src={SymbolINV} />
                <p className="text-primary">Invoices</p>
                <p>Create & send GST compliant Invoices</p>
                <i className="i i-chevron-right" />
              </Link>
            </li>
          </ul>
        </div>
      </Modal>
    </ModalMask>
  );
};
