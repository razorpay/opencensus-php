import React from 'react';
import { pages } from 'merchant/views/Account/WebsiteAppDetails/data';

function PromptMobile({ closeModal }) {
  return (
    <div className="website-app-details-container">
      <div className="prompt-container">
        <div className="image-container">
          {/* TODO: Sample img link */}
          {/* <img src="https://cdn.razorpay.com/static/assets/capital/instant_settlement_no_transaction.svg" /> */}
        </div>
        <div className="prompt-description">
          According to RBI guidelines, we require the following pages on your website/app:
        </div>
        <ul className="page-listing">
          {pages.map((page, idx) => {
            return <li key={`${page}_${idx}`}>{page}</li>;
          })}
        </ul>
        <div className="actions">
          <button className="btn btn-primary">Update</button>
          <span className="btn btn-link" onClick={closeModal}>
            I'll do it later
          </span>
        </div>
      </div>
    </div>
  );
}

export default PromptMobile;
