import GenericEntity from './GenericEntity';
import longPoll from 'common/utils/poll/longPoll';
import { isLogInProgress } from 'merchant_common/containers/ReportsAsync/utils';

export default class Log extends GenericEntity {
  resourceUrl = 'reporting/logs';

  poll(logId) {
    return longPoll({
      fetchFunc: () => this.fetch(logId),
      // when sent true from validator polling will stop
      // continue polling if status is in progress
      validator: log => !isLogInProgress(log.status),
      minWaitTime: 500,
    }).promise;
  }

  makeGenericAjaxCall(props) {
    return super.makeGenericAjaxCall({
      ...props,
      ...(this.reportType && {
        httpData: {
          headers: {
            ['X-Report-Type']: this.reportType,
          },
        },
      }),
    });
  }
}
