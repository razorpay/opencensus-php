import React, { Component } from 'react';
import { PageTable } from 'ui/Table';
import Collection from 'model/collection';
import { adminFetch } from 'common/fetch';
import { formatDate } from 'common/util';
import { showEntity, markEntityComplete } from './Entity';
import AsyncButton from 'ui/AsyncButton';

function openEntity(collection) {
  return function(e) {
    showEntity.call(this, collection);
  };
}

function markComplete(collection, item) {
  return function(e) {
    markEntityComplete(collection, item);
  };
}

export default class DowntimeList extends Component {
  collection = new Collection({
    data: {
      url: 'live/gateway/downtimes',
    },
    fetchFn: adminFetch,
  });

  fields = [
    ['id', item => item.id],
    ['Begin', item => formatDate(item.begin)],
    ['End', item => formatDate(item.end)],
    ['Method', item => item.method],
    ['Gateway', item => item.gateway],
    ['Issuer', item => item.issuer],
    ['Partial', item => item.partial.toString()],
    ['Scheduled', item => item.scheduled.toString()],
    ['Card Type', item => item.card_type],
    ['Network', item => item.network],
    ['Terminal', item => item.terminal_id],
    ['Reason Code', item => item.reason_code],
    ['Source', item => item.source],
    ['Comment', item => item.comment],
    [
      'Action',
      item => (
        <div>
          {item.scheduled ? (
            ''
          ) : (
            <AsyncButton
              class="link"
              pendingClass="link btn-pending"
              confirm={`Are you sure you want to mark downtime "${
                item.id
              }" as complete?`}
              onClick={markComplete(this.collection, item)}
            >
              Mark as complete<span class="dot-loader">.</span>
            </AsyncButton>
          )}
        </div>
      ),
    ],
  ];

  render() {
    const collection = this.collection;
    return (
      <div class="list-container downtime-list">
        <div class="box">
          <header>
            Gateway Downtimes
            <div class="btn pull-right" onClick={openEntity(collection)}>
              Add a Downtime
            </div>
          </header>
        </div>
        <PageTable
          model={this.collection}
          fields={this.fields}
          onClick={openEntity(collection)}
        />
      </div>
    );
  }
}
