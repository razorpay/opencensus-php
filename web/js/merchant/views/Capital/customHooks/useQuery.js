import { useEffect, useState } from 'react';

const useQuery = () => {
  const [params, setParams] = useState({});

  useEffect(() => {
    const query = window.location.search.replace('?', '');
    const params = query.split('&').reduce((acc, current) => {
      const [key, value] = current.split('=');
      acc[key] = value;
      return acc;
    }, {});

    setParams(params);
  }, [window.location.search]);

  return params;
};

export default useQuery;
