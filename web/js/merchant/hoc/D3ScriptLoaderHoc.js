import React, { useEffect, useRef, useState } from 'react';

import { CenterLoader } from 'common/components/Loader';

const SCRIPT_SRC = 'https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.17/d3.min.js';
const INTEGRITY =
  'sha512-oJp0DdQuQQrRsKVly+Ww6fAN1GwJN7d1bi8UubpEbzDUh84WrJ2CFPBnT4LqBCcfqTcHR5OGXFFhaPe3g1/bzQ==';

const loadScript = () => {
  const script = document.createElement('script');
  script.src = SCRIPT_SRC;
  script.crossOrigin = 'anonymous';
  script.integrity = INTEGRITY;
  script.async = true;
  return script;
};

const D3ScriptLoaderHoc = ({ children, Fallback }) => {
  const [isScriptLoaded, setScriptLoaded] = useState(!!window?.d3);
  const [isError, setError] = useState(false);
  const scriptRef = useRef(null);

  useEffect(() => {
    const handleLoad = () => setScriptLoaded(true);
    const handleError = () => {
      document.body.removeChild(scriptRef.current);
      setError(true);
    };

    if (!isScriptLoaded) {
      scriptRef.current = loadScript();
      scriptRef.current.addEventListener('load', handleLoad);
      scriptRef.current.addEventListener('error', handleError);

      document.body.appendChild(scriptRef.current);
    }

    return () => {
      if (scriptRef.current) {
        scriptRef.current.removeEventListener('load', handleLoad);
        scriptRef.current.removeEventListener('error', handleError);
      }
    };
  }, [isScriptLoaded]);

  if (isError) {
    return Fallback ? <Fallback /> : null;
  }

  return isScriptLoaded ? children : <CenterLoader />;
};

export default D3ScriptLoaderHoc;
