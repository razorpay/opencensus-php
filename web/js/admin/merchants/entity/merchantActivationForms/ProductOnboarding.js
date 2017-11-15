import React, { Component } from 'react';

import { notifyError } from 'common/modal';
import { titleCase } from 'util/index';

import { adminFetch } from 'util/fetch';
import EntityRow from 'ui/EntityRow';

export default class ProductOnboarding extends Component {
  state = { onboarding: null };

  componentWillMount() {
    adminFetch({
      route_name: 'feature_onboarding_fetch_all_responses',
      merchant_id: this.props.merchantId,
    })
      .then(data => {
        const onboarding = {};

        Object.keys(data).map(key => {
          onboarding[titleCase(key)] = data[key];
        });

        this.setState({ onboarding });
      })
      .catch(err => {
        notifyError(err);
      });
  }

  render() {
    const { onboarding } = this.state;

    return (
      <div>
        <header>Product Onboarding</header>

        {!onboarding ? (
          <div class="spinner" />
        ) : (
          <div>
            {
              do {
                if (!Object.keys(onboarding).length) {
                  <div>No feature requests were made.</div>;
                } else {
                  Object.keys(onboarding).map(productName => {
                    const questionsList = onboarding[productName];

                    return (
                      <div key={productName} style={{ width: '40%' }}>
                        <b>{productName}</b>

                        {Object.keys(questionsList).map(questionName => (
                          <EntityRow
                            key={questionName}
                            label={titleCase(questionName)}
                            value={
                              questionName === 'vendor_agreement' ? (
                                <a
                                  href={questionsList[questionName]}
                                  target="_blank"
                                >
                                  {questionsList[questionName]}
                                </a>
                              ) : (
                                questionsList[questionName]
                              )
                            }
                          />
                        ))}
                      </div>
                    );
                  });
                }
              }
            }
          </div>
        )}
      </div>
    );
  }
}
