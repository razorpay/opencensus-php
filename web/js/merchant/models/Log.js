import GenericEntity from './GenericEntity';

export default class Log extends GenericEntity {
  resourceUrl = 'reporting/logs';

  makeGenericAjaxCall(props) {
    console.log(this.reportType);
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
