import React, { Component } from 'react';
import { ModalContent } from 'component/Modal';

import Form from 'ui/Form';
import { TextAreaField, SelectField, SwitchField } from 'ui/Field';
import MultiSelectField from 'ui//MultiSelectField';
import AsyncButton from 'ui/AsyncButton';
import { notifyError, notifySuccess, closeModal } from 'common/modal';

import fetch, { adminPost } from 'common/fetch';

export default class EditFeatures extends Component {
  state = { mode: 'test' };

  onSubmit = body => {
    const selectedFeatures = body.selectedFeatures.split(',');

    if (!selectedFeatures.length) {
      notifyError('No features selected');
      return;
    }

    let mode = body.mode;
    const requestData = {
      features: selectedFeatures,
    };

    if (body.shouldSync == 1) {
      mode = 'live';
      requestData['should_sync'] = 1;
    } else {
      requestData['should_sync'] = 0;
    }

    const { props } = this.props;
    requestData.mode = mode;

    return fetch({
      url: '/admin/features/merchant/' + props.merchantId,
      method: 'post',
      data: requestData,
    })
      .then(response => {
        if (response) {
          notifySuccess('Merchant features updated successfully.');
          closeModal();
          if (body.shouldSync == 1) {
            props.updateFeatures('live', selectedFeatures);
            props.updateFeatures('test', selectedFeatures);
          } else {
            props.updateFeatures(body.mode, selectedFeatures);
          }
        }
      })
      .catch(err => {
        notifyError(JSON.stringify(err.response));
      });
  };

  render() {
    const { features } = this.props.props.merchant;
    const curModeFeatures = features[this.state.mode];
    let availableFeaturesInMode = curModeFeatures
      ? curModeFeatures.all_features
      : [];

    if (curModeFeatures) {
      availableFeaturesInMode = curModeFeatures.all_features.filter(
        f => curModeFeatures.assigned_features.indexOf(f) === -1
      );
    }

    let featuresOptions = [];

    if (curModeFeatures) {
      featuresOptions = availableFeaturesInMode.map(feature => ({
        name: feature,
      }));
    }

    return (
      <ModalContent header="Edit Features" class="edit-features">
        <Form class="full-span">
          <SelectField
            name="mode"
            label="Mode"
            defaultValue="test"
            onChange={e => this.setState({ mode: e.target.value })}
          >
            <option value="test">Test</option>
            <option value="live">Live</option>
          </SelectField>

          <SwitchField
            name="shouldSync"
            label="Add to both Test and Live"
            style={{ marginTop: 0 }}
          />

          <MultiSelectField
            label="Features"
            name="selectedFeatures"
            options={featuresOptions}
            trackBy="name"
            keys={['name']}
            defaultValue={[]}
            placeholder="Select Features"
          />

          <TextAreaField
            label="Assigned Features"
            name="assigned_features"
            value={
              features[this.state.mode]
                ? features[this.state.mode].assigned_features.join(', ')
                : ''
            }
            readOnly
          />

          <AsyncButton
            text="OK"
            class="btn"
            pendingClass="small spinner"
            onSubmit={this.onSubmit}
          />
        </Form>
      </ModalContent>
    );
  }
}
