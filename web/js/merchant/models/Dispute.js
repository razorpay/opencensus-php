import GenericEntity from './GenericEntity';

export default class Dispute extends GenericEntity {
  resourceUrl = 'disputes';
  fetchOpen() {
    const data = { status: 'open' };
    return this.makeGenericAjaxCall({ data }).then(response => {
      return response.data.count;
    });
  }
}
