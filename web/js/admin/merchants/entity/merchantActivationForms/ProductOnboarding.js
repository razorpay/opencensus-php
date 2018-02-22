import React, { Component } from 'react';

import { notifyError } from 'common/modal';
import { titleCase, snakeToTitleCase } from 'common/util';

import fetch from 'common/fetch';
import EntityRow from 'ui/EntityRow';

export default class ProductOnboarding extends Component {
  state = { onboarding: null };

  componentWillMount() {
    fetch({
      url: '/admin/api/live/feature/onboarding/responses',
      headers: {'X-Razorpay-Account': this.props.merchantId}
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
      <div class="container">
        <header class="m-b">{this.props.title}</header>

        {!onboarding ? (
          <div class="spinner center m-t" />
        ) : (
          <div class="limited">
            {do {
              if (!Object.keys(onboarding).length) {
                <div>No feature requests were made.</div>;
              } else {
                Object.keys(onboarding).map(productName => {
                  const questionsList = onboarding[productName];

                  return (
                    <div key={productName} class="m-b">
                      <div class="heading">{snakeToTitleCase(productName)}</div>

                      {Object.keys(questionsList).map(questionName => (
                        <EntityRow
                          key={questionName}
                          label={snakeToTitleCase(questionName)}
                          value={
                            questionName === 'vendor_agreement' ||
                            questionName === 'website_details'
                              ? () => (
                                  <a
                                    href={questionsList[questionName]}
                                    target="_blank"
                                  >
                                    {questionsList[questionName].length <= 25
                                      ? questionsList[questionName]
                                      : 'Link'}
                                  </a>
                                )
                              : typeof questionsList[questionName] === 'object'
                                ? JSON.stringify(questionsList[questionName])
                                : questionsList[questionName]
                          }
                        />
                      ))}
                    </div>
                  );
                });
              }
            }}
          </div>
        )}
      </div>
    );
  }
}
