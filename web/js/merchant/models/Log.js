import GenericEntity from './GenericEntity';

export default class Log extends GenericEntity {
  resourceUrl = 'reporting/logs';

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
