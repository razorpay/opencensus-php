import React from 'react';
import { Text } from '@razorpay/blade/components';

import { NotesKey } from './styled';
import { IPaymentDetails } from './types';

interface INotes {
  notes: IPaymentDetails['notes'];
  isStorefront: boolean;
}

const ignoredNotesKeys = ['name', 'email', 'phone', 'product_type'];

function getNotes({ notes, isStorefront }: INotes): JSX.Element | string {
  if (Object.keys(notes).length === 0) {
    return (
      <Text variant="body" size="medium" weight="regular" color="surface.text.gray.normal">
        --
      </Text>
    );
  }

  const filteredKeys = Object.keys(notes).filter((key) => ignoredNotesKeys.indexOf(key) === -1);
  return (
    <Text variant="body" size="medium" weight="regular" color="surface.text.gray.normal">
      {filteredKeys.length !== 0
        ? filteredKeys.map((key, index) => {
            if (!(isStorefront && key === 'line_items')) {
              return (
                <Text
                  variant="body"
                  size="medium"
                  weight="regular"
                  key={index}
                  color="surface.text.gray.normal"
                >
                  <>
                    <NotesKey>{key}:</NotesKey> {String(notes[key] || '--')}
                  </>
                </Text>
              );
            } else {
              return '';
            }
          })
        : '--'}
    </Text>
  );
}

export default getNotes;
