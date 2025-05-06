import { fetchUCS } from '@apps/shell/src/client/services/rest-fetch';

//TODO: Add typings
const fetchUCSData = async (requestData: unknown): Promise<any> => {
  const response = fetchUCS({
    method: 'POST',
    url: `rzp.dashboard.component.v1.ComponentService/GetComponentData`,
    data: requestData,
  });
  return response;
};

export default fetchUCSData;
