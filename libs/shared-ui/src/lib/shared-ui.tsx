import React from 'react';
import styles from './shared-ui.module.css';
/* eslint-disable-next-line */
export interface SharedUiProps {
  app: string;
}

export function SharedUi(props: SharedUiProps): JSX.Element {
  return (
    <div className={styles.container}>
      <h1>Welcome to SharedUi! - {props.app} Hello </h1>
    </div>
  );
}

export default SharedUi;
