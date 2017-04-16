import React, { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import { titleCase } from 'rzp/utils/rzp-utils';
import AsyncButton from 'react-async-button';
import moment from 'moment';
import ajax from 'merchant/utils/ajax';
import { generateReport } from 'merchant/modules/reports';
import * as NotificationsActions from 'rzp/modules/notifications';

let now = moment();
let currentMonth = now.month();
let currentYear = now.year();
let currentDate = now.date();

function numberOfDays(month, year) {
  return moment(year + ' ' + month, 'YYYY M').daysInMonth();
}

const selector = formValueSelector('generateReports');

@connect(
  state => {
    return {
      mode: state.session.mode,
      user: state.session.user,
      entity: selector(state, 'entity'),
      type: selector(state, 'type'),
      month: selector(state, 'month'),
      year: selector(state, 'year'),
    };
  },
  { generateReport, ...NotificationsActions }
)
@reduxForm({
  form: 'generateReports',
  initialValues: {
    entity: 'payment',
    type: 'monthly',
    month: currentMonth,
    year: currentYear,
    day: currentDate,
  },
})
export default class ReportsContainer extends Component {
  prepareGenerateReport = values => {
    let { entity, type, month, year, day } = values;

    let data = {
      month,
      year,
    };

    if (entity === 'invoice') {
      return Promise.resolve(
        window.open(
          `/${this.props.mode}/reports/invoice?year=${year}&month=${month}`,
          '_blank'
        )
      );
    }

    if (type === 'daily') {
      data.day = day;
    }

    var ajaxParams = {
      url: '/reports/' + entity,
      data: data,
    };

    if (entity === 'broking') {
      ajaxParams.headers = {
        Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      };
    }

    return this.props
      .generateReport(ajaxParams)
      .then(data => {
        this.props.showNotification({
          type: 'success',
          message: 'Your report will download shortly',
        });

        if (entity === 'broking') {
          var blob = new Blob([data], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          });
          return saveAs(blob, 'broking_report.xlsx');
        }

        location.href = data.data.url;
      })
      .catch(e => {
        this.props.showNotification({
          type: 'error',
          message: 'No data found for given time range',
        });
      });
  };

  render() {
    let { entity, type, mode, month, year, user, handleSubmit } = this.props;

    return (
      <div class="row report-wrapper">
        <div class="col-md-4 report-list-panel">
          <span class="title">SELECT REPORT TYPE</span>
          <Field name="entity" component="radio" class="form-control">
            <input type="radio" name="asd"/> <label for="asd" />
          </Field>
        </div>
        <div class="col-md-8 report-generate-panel">
          <span class="title">SELECT ACCOUNT</span>
        </div>
      </div>
    );
  }
}
