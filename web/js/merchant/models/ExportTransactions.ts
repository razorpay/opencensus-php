import GenericEntity from './GenericEntity';

class ExportTransactions extends GenericEntity {
  resourceUrl = 'payments_cross_border_live/v1/merchant/documents';

  fetchAll(params = {}) {
    return super.fetchAll({ ...params, type: 'b2b_export_invoice' });
  }
}

export default ExportTransactions;
