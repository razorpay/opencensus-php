import React from 'react';

import styles from './styles.css';

export default (LoaderOverlay = () => {
  return <div className={styles['loader-overlay']} />;
});
