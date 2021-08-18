import React from 'react';
import { Field, formValueSelector } from 'redux-form';
import { connect } from 'react-redux';
import moment from 'moment';
import RadioButton from 'common/ui/Forms/RadioButton';
import ReduxDatetime from 'common/ui/ReduxDatetime';

const selector = formValueSelector('createBatch');

class BatchProcessingOptions extends React.Component {
  render() {
    const processingType = this.props.processingType;
    return (
      <>
        <h5>
          <strong>BATCH PROCESSING SCHEDULE</strong>
        </h5>
        <div class="row">
          <div class="col-md-3 m-t">
            <Field
              name="processing"
              htmlValue="immediate"
              component={RadioButton}
              label="Process Now"
              class="form-control"
            />
          </div>
          <div class="col-md-3 m-t">
            <Field
              name="processing"
              htmlValue="scheduled"
              component={RadioButton}
              label="Schedule for Later"
              class="form-control"
            />
          </div>
          {processingType === 'scheduled' && (
            <>
              <div class="col-md-3">
                <div class="form-group">
                  <label>
                    <small>Start on</small>
                  </label>
                  <Field
                    name="scheduleDate"
                    dateFormat="DD MMM, YYYY"
                    component={ReduxDatetime}
                    timeFormat={false}
                    closeOnSelect
                    isValidDate={isValidBatchScheduleDate}
                  />
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>
                    <small>Start at</small>
                  </label>
                  <Field
                    name="scheduleTime"
                    component={ReduxDatetime}
                    closeOnSelect
                    dateFormat={false}
                  />
                </div>
              </div>
            </>
          )}
        </div>
      </>
    );
  }
}

const yesterday = moment().subtract(1, 'days');

function isValidBatchScheduleDate(date) {
  return date.isAfter(yesterday);
}

export default connect((state) => {
  return {
    processingType: selector(state, 'processing'),
  };
}, null)(BatchProcessingOptions);
