import { Component } from 'react';
import { connect } from 'react-redux';
import { Field } from 'redux-form';
import RTracking from 'react-tracking';
import moment from 'moment';

import { showNotification } from 'merchant_common/reducers/notifications';

import BatchCreateModal from 'merchant/components/BatchNew/CreateModal';

@connect(state => state.session, { showNotification })
@RTracking(() => window.rzpQ.component('BatchCreate'))
export default class BatchCreate extends Component {
  formInitialValues = {
    name: this.props.batchName,
  };

  @RTracking(() =>
    window.rzpQ.onbr().success('dash.pl_action', {
      action: 'Initiate_Batch_PL_Generation',
    })
  )
  handleBatchCreate = ({
    processing,
    scheduleDate,
    scheduleTime,
    ...props
  }) => {
    const data = {
      file_id: this.props.batch.file_id,
      ...props,
    };

    if (processing === 'scheduled') {
      const scheduleDateTs = scheduleDate
        .clone()
        .startOf('day')
        .valueOf();
      const scheduleTimeTs = scheduleTime.diff(
        scheduleTime.clone().startOf('day'),
        'milliseconds'
      );
      data.schedule = scheduleDateTs + scheduleTimeTs;
    }

    this.props.trackUploadBatch('Create');
    return this.props
      .createBatch(data)
      .then(response => {
        this.props.onCreation(response);
      })
      .catch(error => {
        this.props.showNotification({
          type: 'error',
          message: 'Failed to create batch.',
        });
      });
  };

  render() {
    const {
      handleBatchCreate,
      props: {
        closeModal,
        batch,
        ctaText,
        pendingText,
        batchName,
        batchType,
        renderBatchCreationForm,
        batchFormInitialValues = {},
        trackSampleInterpretation,
      },
    } = this;

    const initialValues = {
      name: batchName,
      ...batchFormInitialValues,
      ...batchFormDefaults,
    };

    return (
      <BatchCreateModal
        closeModal={closeModal}
        parsedEntries={batch.parsed_entries}
        batchType={batchType}
        onCreateBatch={handleBatchCreate}
        initialValues={initialValues}
        ctaText={ctaText}
        pendingText={pendingText}
        trackSampleInterpretation={trackSampleInterpretation}
        processingOptions={this.props.processingOptions}
      >
        {renderBatchCreationForm && renderBatchCreationForm()}
      </BatchCreateModal>
    );
  }
}

const batchFormDefaults = {
  processing: 'immediate',
  scheduleDate: moment(),
  scheduleTime: moment(),
};
