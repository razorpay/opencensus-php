import React, { useEffect, useState } from 'react';
import Spinner from 'common/ui/Spinner';

export function usePromise(initialPromise) {
  const [promise, setPromise] = useState(initialPromise);
  const [value, setValue] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    setValue(null);
    setLoading(true);
    setError(false);
    promise
      .then((data) => setValue(data))
      .catch((err) => setError(err))
      .finally(() => setLoading(false));
  }, [promise]);

  return {
    value,
    loading,
    error,
    setPromise,
  };
}

export default function Await({ promise, loading, error, children }) {
  if (promise.loading)
    return (
      loading || (
        <div
          style={{
            position: 'absolute',
            top: '50%',
            left: '50%',
            transform: 'translate(-50%, -50%)',
            display: 'flex',
            flexDirection: 'column',
          }}
        >
          <Spinner />
          <p>Loading, one moment please</p>
        </div>
      )
    );

  if (promise.error) return error || <p>{promise.error}</p>;

  if (promise.value) return children;

  return null;
}
