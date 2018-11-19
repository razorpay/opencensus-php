import React from 'react';
import { Link } from 'react-router-dom';

import { ModalMask, Modal, ModalContent } from 'component/Modal';

export default ({ onClose, onBack, track }) => {
  return (
    <ModalMask>
      <Modal className="products-suite-modal" onClose={onClose}>
        <div className="product-suite">
          <h1>
            <i className="i i-arrow-back cursor-pointer" onClick={onBack} />
            <span>Our Product Suite</span>
          </h1>
          <p>
            You can start receiving payments immediately using the following
            products
          </p>
          <ul className="nav">
            {/*
            <li>
              <Link to="/virtualaccounts">
                <img src="/dist/css/assets/symbols/sc.svg" />
                <p className="text-primary">Smart Collect</p>
                <p>Get paid in virtual accounts via NEFT, RTGS</p>
                <i className="i i-chevron-right" />
              </Link>
            </li>
            */}
            <li>
              <Link
                to="/paymentlinks"
                onClick={() => {
                  track.trackProductClick('Payment Links');
                }}
              >
                <img src="/dist/css/assets/symbols/pl.svg" />
                <p className="text-primary">Payment Links</p>
                <p>Create & share Payment Links via SMS, Email etc.</p>
                <i className="i i-chevron-right" />
              </Link>
            </li>
            <li>
              <Link
                to="/invoices"
                onClick={() => {
                  track.trackProductClick('Invoices');
                }}
              >
                <img src="/dist/css/assets/symbols/inv.svg" />
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
