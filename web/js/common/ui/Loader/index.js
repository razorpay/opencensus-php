import React from 'react';
import Spinner from 'common/ui/Spinner';

const Loader = () => (
  <div class="page-spinner-container">
    <Spinner />
  </div>
);

export default React.memo(Loader);
