import React, { useEffect, useState } from 'react'
import Spinner from 'common/ui/Spinner'

export function usePromise(promise) {
  const [value, setValue] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    promise.then(data => setValue(data))
      .catch((err) => setError(err))
      .finally(() => setLoading(false))
  }, [])

  return {
    value,
    loading,
    error,
  }
}

export default function Await({ promise, loading, error, children }) {

  if (promise.loading) return loading || <Spinner />

  if (promise.error) return error || <p>{promise.error}</p>

  if (promise.value) return children

  return null
}