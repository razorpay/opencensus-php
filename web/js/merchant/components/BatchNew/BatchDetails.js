import React, { Component } from 'react';

import Spinner from 'rzp/ui/Spinner';

export default function BatchDetails(props) {
  let { batch, stats, isLoading } = props;

  return (
    <div class="content-wrapper content-sm txn-details batch-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            <i class="i i-link text-primary icon--formal" />{' '}
            <strong>{batch.name}</strong>
          </div>
          <div class="SliderPanel__Body">
            <div class="download-info row">
              <span class="col-md-8">
                Download the output file containing all the payment links data.
              </span>
              <a class="col-md-4 btn btn-primary">Download Report File</a>
            </div>
            <div class="stats-info">
              <table class="table">
                <tbody>
                  <tr>
                    <td class="td-info">
                      <span class="td-heading">Payment Links Created</span>
                      <span class="td-value">{stats.entities_processed}</span>
                    </td>
                    <td class="td-info">
                      <span class="td-heading">Payment Links Sent</span>
                      <span class="td-value">{stats.payment_links_sent}</span>
                    </td>
                  </tr>
                  <tr>
                    <td class="td-info">
                      <span class="td-heading">Paid</span>
                      <span class="td-value text-success">{stats.paid}</span>
                    </td>
                    <td class="td-info">
                      <span class="td-heading">Expired</span>
                      <span class="td-value text-danger">{stats.expired}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
