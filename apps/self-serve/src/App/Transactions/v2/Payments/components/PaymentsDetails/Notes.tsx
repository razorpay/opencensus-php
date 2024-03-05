import React from 'react';
import { Text } from '@razorpay/blade/components';

import { NotesKey } from './styled';
import { IPaymentDetails } from './types';

interface INotes {
  notes: IPaymentDetails['notes'];
  isStorefront: boolean;
}

const ignoredNotesKeys = ['name', 'email', 'phone'];

function getNotes({ notes, isStorefront }: INotes): JSX.Element | string {
  if (Object.keys(notes).length === 0) {
    return (
      <Text type="normal" variant="body" size="medium" weight="regular" contrast="low">
        --
      </Text>
    );
  }

  const filteredKeys = Object.keys(notes).filter((key) => !ignoredNotesKeys.includes(key));
  return (
    <Text type="normal" variant="body" size="medium" weight="regular" contrast="low">
      {filteredKeys.length !== 0
        ? filteredKeys.map((key, index) => {
            if (!(isStorefront && key === 'line_items')) {
              return (
                <Text
                  type="normal"
                  variant="body"
                  size="medium"
                  weight="regular"
                  contrast="low"
                  key={index}
                >
                  <>
                    <NotesKey>{key}:</NotesKey> {String(notes[key as keyof typeof notes] || '--')}
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
