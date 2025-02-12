import React from 'react';

import PreviewFormShell from 'merchant/views/PaymentButton/SubscriptionButton/Create/components/Preview/PreviewFormShell';

export default class CustomerDetailsPreview extends React.Component {
  render() {
    const { udfFields } = this.props;

    return (
      <PreviewFormShell
        shellTitle="CUSTOMER DETAILS"
        buttonTitle="Proceed to Pay"
        totalDots={2}
        activeDotsIndex={0}
        {...this.props}
      >
        <div>
          {/* TODO: Check for asterisk/optional RazorX experiment */}
          {udfFields.map((field, index) => {
            return (
              <div className="Field--dummy Field--dummy--udf" key={index}>
                {/* TODO: add dropdown icon for dropdown field */}
                <input className="Field-el" placeholder={field.title} readOnly />
                <div className="Field-description">{field.description}</div>
              </div>
            );
          })}
        </div>
      </PreviewFormShell>
    );
  }
}
