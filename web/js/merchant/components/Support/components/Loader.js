import React from 'react';
import Spinner from 'common/ui/Spinner';

const Loader = ({ isOpened }) =>
  isOpened && (
    <div className="support-loader support">
      <div className="support-launcher">
        <Spinner center={true} />
      </div>
    </div>
  );

export default Loader;
