import GenericEntity from './GenericEntity';

import sampleData from './sampledata.tmp.json';

export default class Commission extends GenericEntity {
  resourceUrl = 'commissions';

  fetchAggregateData = () => {
    return new Promise((onSuccess, onError) => {
      setTimeout(() => {
        onSuccess({
          data: { items: sampleData },
          items: sampleData,
          success: true,
        });
      }, 500);
    });
  };
}
