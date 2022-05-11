import { Component } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import moment from 'moment';
import { compose, bindActionCreators } from 'redux';
import { showNotification } from 'merchant_common/reducers/notifications';
import BatchCreateModal from 'merchant/components/BatchNew/CreateModal';

const oneHour = 3600 * 1000; //1 hour in milliseconds

const batchFormDefaults = {
  processing: 'immediate',
  scheduleDate: moment().add(1, 'days'),
  scheduleTime: moment(),
};

class BatchCreate extends Component {
  formInitialValues = {
    name: this.props.batchName,
  };

  getSchedulingOptions = (scheduleDate, scheduleTime) => {
    const scheduleDateTs = scheduleDate.clone().startOf('day').valueOf();

    const scheduleTimeTs = scheduleTime.diff(scheduleTime.clone().startOf('day'), 'milliseconds');

    const schedule = scheduleDateTs + scheduleTimeTs;

    if (schedule - moment().valueOf() < oneHour) {
      this.props.showNotification({
        type: 'error',
        message: 'Please select a time with atleast one hour gap from now',
      });
      return false;
    }

    return schedule;
  };

  @RTracking(() =>
    window.rzpQ.onbr().success('dash.pl_action', {
      action: 'Initiate_Batch_PL_Generation',
    }),
  )
  handleBatchCreate = ({ processing, scheduleDate, scheduleTime, ...props }) => {
    const data = {
      file_id: this.props.batch.file_id,
      ...props,
    };

    if (processing === 'scheduled') {
      const schedule = this.getSchedulingOptions(scheduleDate, scheduleTime);

      if (!schedule) {
        return;
      }

      data.schedule = schedule;
    }

    this.props.trackUploadBatch('Create');
    this.props
      .createBatch(data)
      .then((response) => {
        this.props.onCreation(response);
      })
      .catch(() => {
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
        speedCount={batch.speed_count}
        processableCount={batch.processable_count}
        batchType={batchType}
        onCreateBatch={handleBatchCreate}
        initialValues={initialValues}
        ctaText={ctaText}
        pendingText={pendingText}
        trackSampleInterpretation={trackSampleInterpretation}
        processingOptions={this.props.processingOptions}
        onFileNameTrack={this.props.onFileNameTrack}
        onPreview={this.props.onPreview}
      >
        {renderBatchCreationForm && renderBatchCreationForm()}
      </BatchCreateModal>
    );
  }
}

export default compose(
  connect(
    (state) => state.session,
    (dispatch) => bindActionCreators({ showNotification }, dispatch),
  ),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('BatchCreate')),
)(BatchCreate);
