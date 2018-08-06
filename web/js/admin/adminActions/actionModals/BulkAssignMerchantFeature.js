import React, { Component } from 'react';
import Form from 'ui/Form';
import {
  SelectField,
  TextAreaField,
  SwitchField,
  SearchableSelectField,
} from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

import { adminPost, adminFetch } from 'common/fetch';
import { splitAndFilter } from 'common/util';
import { closeModal, notifyError, notifySuccess } from 'common/modal';

export default class BulkAssignMerchantFeature extends Component {
  static title = 'Assign/Remove Feature for Mulitple Merchants';
  state = {
    mode: 'test',
    action: 'assign',
    isFetching: true,
    availableFeatures: [],
  };

  componentDidMount() {
    // TODO: Update the API URL. Using the hard coded merchant get URL as the generic API doesn't exist right now.
    adminFetch('live/features/10000000000000').then(data => {
      if (data) {
        this.setState({
          availableFeatures: data.all_features,
          isFetching: false,
        });
      }
    });
  }

  onSubmit = body => {
    if (!body.selectedFeature) {
      notifyError('Please select a feature.');
      return;
    }

    if (!body.merchantIds) {
      notifyError('Please enter valid merchant IDs.');
      return;
    }

    const requestData = {
      mode: body.mode,
      name: body.selectedFeature,
      entity_type: 'merchant',
      entity_ids: splitAndFilter(body.merchantIds, ','),
    };

    if (body.shouldSync == 1) {
      requestData.mode = 'live';
      requestData.should_sync = 1;
    } else {
      requestData.should_sync = 0;
    }

    return adminPost({
      url: `${requestData.mode}/features/${this.state.action}`,
      data: requestData,
    }).then(response => {
      if (response) {
        notifySuccess(
          `Feature successfully updated for ${response.length} merchant(s).`
        );
        closeModal();
      }
      return response;
    });
  };

  handleChange = e => {
    let obj = {};
    obj[e.target.name] = e.target.value;
    this.setState(obj);
  };

  render() {
    let featuresOptions = this.state.availableFeatures.map(feature => ({
      name: feature,
      value: feature,
    }));

    return (
      <div class="bulk-merchants-feature">
        {this.state.isFetching ? (
          <div class="spinner center" />
        ) : (
          <Form class="full-span">
            <SelectField
              name="mode"
              label="Mode"
              defaultValue="test"
              onChange={this.handleChange}
            >
              <option value="test">Test</option>
              <option value="live">Live</option>
            </SelectField>

            <SwitchField name="shouldSync" label="Add to both Test and Live" />

            <SelectField
              name="action"
              label="Action"
              defaultValue={this.state.action}
              onChange={this.handleChange}
            >
              <option value="assign">Assign</option>
              <option value="remove">Remove</option>
            </SelectField>

            <SearchableSelectField
              required
              label="Feature"
              name="selectedFeature"
              options={featuresOptions}
            />

            <TextAreaField
              label="Merchant Ids"
              type="text"
              name="merchantIds"
              required
              placeholder="Enter comma separated merchant ids"
              class="merchant-ids"
            />

            <div class="form-actions text-right">
              <AsyncButton
                text={
                  this.state.action === 'assign'
                    ? 'Assign Feature'
                    : 'Remove Feature'
                }
                class="btn"
                pendingClass="small spinner"
                onSubmit={this.onSubmit}
              />
              <span class="btn btn-default" onClick={closeModal}>
                Cancel
              </span>
            </div>
          </Form>
        )}
      </div>
    );
  }
}
