import GenericEntity from './GenericEntity';

export default class Dispute extends GenericEntity {
  resourceUrl = 'disputes';
  fetchOpen() {
    const data = { status: 'open' };
    return this.makeGenericAjaxCall({ data, url: 'disputes-aggregate' }).then(
      (res) => res.data.count,
    );
  }
}
